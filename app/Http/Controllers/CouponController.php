<?php

namespace App\Http\Controllers;

use App\Model\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Coupon as StripeCoupon;

class CouponController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        Stripe::setApiKey(getSetting('payments.stripe_secret_key'));
    }

    public function index(Request $request)
    {
        $activeTab = $request->input('type', 'active');
        
        $query = Coupon::where('creator_id', Auth::id());
    
        if ($activeTab === 'active') {
            $query->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })->where(function($q) {
                $q->whereNull('usage_limit')
                  ->orWhereRaw('times_used < usage_limit');
            });
        } else {
            $query->where(function($q) {
                $q->where('expires_at', '<=', now())
                  ->orWhereRaw('times_used >= usage_limit');
            });
        }
    
        $coupons = $query->latest()->paginate(10);
    
        return view('pages.coupons', [
            'coupons'   => $coupons,
            'activeTab' => $activeTab
        ]);
    }

    public function create()
    {
        return view('pages.coupons.form');
    }

    public function store(Request $request)
    {  
        $validated = $request->validate([
            'coupon_code' => [
                'required',
                'max:20',
                Rule::unique('coupons')->where(function ($query) {
                    return $query->where('creator_id', Auth::id());
                }),
            ],
            'discount_type'    => 'required|in:percent,fixed',
            // Para desconto percentual, o valor já deve estar convertido (ex.: 0.10 para 10%)
            'discount_percent' => 'required_if:discount_type,percent|nullable|numeric|between:0.01,99.99',
            // Para desconto fixo, o valor é enviado em centavos (inteiro)
            'amount_off'       => 'required_if:discount_type,fixed|nullable|integer|min:1',
            'expiration_type'  => ['required', Rule::in(['never', 'date', 'usage'])],
            'duration_in_months' => 'required_if:expiration_type,date|integer|min:1|max:12',
            'expires_at'       => 'required_if:expiration_type,date|date|after:now|nullable',
            'usage_limit'      => 'required_if:expiration_type,usage|integer|min:1|nullable',
            'payment_method'    => 'required|in:credit_card,pix,all',
        ]);
    
        try {
            // Define a chave do Stripe
            Stripe::setApiKey(getSetting('payments.stripe_secret_key'));
    
            // Monta os parâmetros iniciais para o Stripe (removemos a chave 'id' para permitir que o Stripe gere o ID automaticamente)
            $stripeParams = [
                'duration' => $this->getStripeDuration($validated['expiration_type']),
                'name'     => $validated['coupon_code']
            ];
    
            if ($validated['discount_type'] === 'percent') {
                // Converte o valor (ex.: 0.10) para inteiro (0.10 * 100 = 10)
                $stripeParams['percent_off'] = (int) ($validated['discount_percent'] * 100);
            } elseif ($validated['discount_type'] === 'fixed') {
                // Certifica-se de que amount_off seja um inteiro
                $stripeParams['amount_off'] = (int) $validated['amount_off'];
                $stripeParams['currency'] = config('app.site.currency_code');
                // Se o cupom fixo for do tipo "never", o Stripe não permite duration "forever"
                // Forçamos a duração para "once"
                if ($validated['expiration_type'] === 'never') {
                    $stripeParams['duration'] = 'once';
                }
            }
    
            if ($validated['expiration_type'] === 'date') {
                $stripeParams['duration_in_months'] = $validated['duration_in_months'];
                $stripeParams['redeem_by'] = strtotime($validated['expires_at']);
            } elseif ($validated['expiration_type'] === 'usage') {
                $stripeParams['max_redemptions'] = $validated['usage_limit'];
            }
    
            // Log para depuração
            \Log::info('Stripe coupon parameters:', $stripeParams);
    
            // Cria o cupom no Stripe e permite que ele gere o ID automaticamente
            $stripeCoupon = StripeCoupon::create($stripeParams);
    
            // Prepara os dados para salvar no banco, utilizando o ID gerado pelo Stripe
            $couponData = [
                'coupon_code'       => $validated['coupon_code'],
                'discount_type'     => $validated['discount_type'],
                'discount_percent'  => $validated['discount_type'] === 'percent' ? $validated['discount_percent'] : null,
                'amount_off'        => $validated['discount_type'] === 'fixed' ? $validated['amount_off'] : null,
                'expiration_type'   => $validated['expiration_type'],
                'duration_in_months'=> $validated['expiration_type'] === 'date' ? $validated['duration_in_months'] : null,
                'expires_at'        => $validated['expiration_type'] === 'date' ? $validated['expires_at'] : null,
                'usage_limit'       => $validated['expiration_type'] === 'usage' ? $validated['usage_limit'] : null,
                'creator_id'        => Auth::id(),
                'stripe_coupon_id'  => $stripeCoupon->id,
                'payment_method'     => $validated['payment_method'],
            ];
    
            Coupon::create($couponData);
    
            return redirect()->route('coupons.index')
                ->with('success', __('Cupom criado com sucesso!'));
        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Log::error('Erro ao criar cupom no Stripe:', ['error' => $e->getMessage()]);
    
            return redirect()->back()
                ->withInput()
                ->withErrors(['stripe_error' => 'Erro ao criar cupom no Stripe: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::error('Erro ao criar cupom no banco de dados:', ['error' => $e->getMessage()]);
    
            return redirect()->back()
                ->withInput()
                ->withErrors(['db_error' => 'Erro ao salvar cupom no banco de dados: ' . $e->getMessage()]);
        }
    }
    
    
    
    public function edit($id)
    {
        $coupon = Coupon::where('creator_id', Auth::id())->findOrFail($id);
        return view('pages.coupons.form', [
            'coupon' => $coupon
        ]);
    }

    public function update(Request $request, $id)
    {
        // Atenção: aqui usamos 'coupon_code' de forma consistente em vez de 'code'
        $coupon = Coupon::where('creator_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'coupon_code'      => [
                'required',
                Rule::unique('coupons')->ignore($coupon->id),
                'max:20'
            ],
            'discount_type'    => 'required|in:percent,fixed',
            'discount_percent' => 'required_if:discount_type,percent|nullable|numeric|between:0.01,99.99',
            'amount_off'       => 'required_if:discount_type,fixed|nullable|integer|min:1',
            'expiration_type'  => ['required', Rule::in(['never', 'usage', 'date'])],
            'usage_limit'      => 'required_if:expiration_type,usage|integer|min:1|nullable',
            'expires_at'       => 'required_if:expiration_type,date|date|after:now|nullable',
            'payment_method'   => 'required|in:credit_card,pix,all',
        ]);

        try {
            // Excluir o cupom antigo no Stripe
            StripeCoupon::retrieve($coupon->stripe_coupon_id)->delete();

            $stripeParams = [
                'duration' => $this->getStripeDuration($validated['expiration_type']),
                'id'       => $validated['coupon_code'],
            ];

            if ($validated['discount_type'] === 'percent') {
                $stripeParams['percent_off'] = (int) ($validated['discount_percent'] * 100);
            } elseif ($validated['discount_type'] === 'fixed') {
                $stripeParams['amount_off'] = $validated['amount_off'];
                $stripeParams['currency'] = config('app.site.currency_code');
            }
    
            $stripeParams['max_redemptions'] = $validated['expiration_type'] === 'usage' ? $validated['usage_limit'] : null;
            $stripeParams['redeem_by'] = $validated['expiration_type'] === 'date' && $validated['expires_at'] ? strtotime($validated['expires_at']) : null;
    
            $stripeCoupon = StripeCoupon::create($stripeParams);

            $updateData = [
                'coupon_code'      => $validated['coupon_code'],
                'discount_type'    => $validated['discount_type'],
                'discount_percent' => $validated['discount_type'] === 'percent' ? $validated['discount_percent'] : null,
                'amount_off'       => $validated['discount_type'] === 'fixed' ? $validated['amount_off'] : null,
                'expiration_type'  => $validated['expiration_type'],
                'usage_limit'      => $validated['expiration_type'] === 'usage' ? $validated['usage_limit'] : null,
                'expires_at'       => $validated['expiration_type'] === 'date' ? $validated['expires_at'] : null,
                'stripe_coupon_id' => $stripeCoupon->id,
                'payment_method'   => $validated['payment_method'], // <— adiciona aqui
            ];
    
            if ($validated['expiration_type'] === 'date') {
                $updateData['duration_in_months'] = $request->input('duration_in_months');
            }
    
            $coupon->update($updateData);

            return redirect()->route('coupons.index')
                ->with('success', __('Cupom atualizado com sucesso!'));
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['stripe_error' => 'Erro ao atualizar cupom no Stripe: ' . $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        $coupon = Coupon::where('creator_id', Auth::id())->findOrFail($id);
    
        try {
            if ($coupon->stripe_coupon_id) {
                // Recupera o cupom do Stripe usando o campo correto
                $stripeCoupon = StripeCoupon::retrieve($coupon->stripe_coupon_id);
                $deletedCoupon = $stripeCoupon->delete();
                // Verifica se o cupom foi deletado com sucesso no Stripe
                if (!$deletedCoupon->deleted) {
                    \Log::warning("Falha ao deletar o cupom no Stripe: " . $coupon->stripe_coupon_id);
                }
            }
            
            $coupon->delete();
    
            return redirect()->route('coupons.index')
                ->with('success', __('Cupom excluído com sucesso!'));
    
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return redirect()->back()
                ->withErrors(['stripe_error' => 'Erro ao excluir cupom: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['db_error' => 'Erro ao excluir cupom: ' . $e->getMessage()]);
        }
    }
    
    
    private function getStripeDuration($expirationType)
    {
        switch ($expirationType) {
            case 'never':
                return 'forever';
            case 'usage':
                return 'once';
            case 'date':
                return 'repeating';
            default:
                return 'once';
        }
    }

    public function validateCoupon(Request $request)
    {
        // Validação básica dos campos necessários
        $data = $request->validate([
            'coupon'   => 'required|string',
            'username' => 'required|string',
        ]);
    
        // Normaliza o código do cupom (opcionalmente, pode converter para maiúsculo)
        $couponCode = strtoupper(trim($data['coupon']));
        $username   = trim($data['username']);
    
        // Recupera o perfil (ajuste o namespace se necessário)
        $profile = \App\User::where('username', $username)->first();
        if (!$profile) {
            return response()->json([
                'valid'   => false,
                'message' => __('Perfil não encontrado.')
            ]);
        }
    
        // Busca o cupom que pertence ao perfil informado
        $coupon = Coupon::where('coupon_code', $couponCode)
                        ->where('creator_id', $profile->id)
                        ->first();
        
        if (!$coupon) {
            return response()->json([
                'valid'   => false,
                'message' => __('Cupom não encontrado para este perfil.')
            ]);
        }
        
        // Verifica se o cupom possui data de expiração e se já expirou
        if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
            return response()->json([
                'valid'   => false,
                'message' => __('Este cupom está expirado.')
            ]);
        }
        
        // Verifica se há um limite de uso e se já foi atingido
        if ($coupon->usage_limit && $coupon->times_used >= $coupon->usage_limit) {
            return response()->json([
                'valid'   => false,
                'message' => __('Este cupom já atingiu o limite de usos.')
            ]);
        }
    
        // Prepara as informações de desconto para o frontend
        $discount = null;
        if ($coupon->discount_type === 'percent') {
            // Se discount_percent estiver armazenado como 0.10 (10%), converte para 10
            $discount = [
                'type'  => 'percent',
                'value' => $coupon->discount_percent * 100,
            ];
        } elseif ($coupon->discount_type === 'fixed') {
            // Converte de centavos para reais (exemplo: 1200 vira 12)
            $discount = [
                'type'  => 'fixed',
                'value' => $coupon->amount_off / 100,
            ];
        }
        
    
        // Se todas as validações forem aprovadas, retorna sucesso com os dados do desconto
        return response()->json([
            'valid'       => true,
            'message'     => __('Cupom aplicado com sucesso!'),
            'coupon_code' => $coupon->stripe_coupon_id,
            'discount'    => $discount,
            'payment_method'  => $coupon->payment_method,
        ]);
    }
    
}

