<?php

namespace App\Http\Controllers;

use App\Helpers\PaymentHelper;
use App\Model\Subscription;
use App\Model\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class SubscriptionsController extends Controller
{
    protected $paymentHelper;

    public function __construct(PaymentHelper $paymentHelper)
    {
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Method used for canceling an active subscription.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancelSubscription(Request $request)
    {
        try {
            $subscriptionId = $request->subscriptionId;
            if ($subscriptionId != null) {

                $subscription = Subscription::query()->where('id', intval($subscriptionId))
                    ->where(function ($query) {
                        $query->where('sender_user_id', '=', Auth::user()->id)
                            ->orWhere('recipient_user_id', '=', Auth::user()->id);
                    })
                    ->first();

                if ($subscription != null) {
                    if ($subscription->status === Subscription::CANCELED_STATUS) {
                        return Redirect::route('my.settings', ['type' => 'subscriptions'])
                            ->with('error', __('This subscription is already canceled.'));
                    }

                    $cancelSubscription = $this->paymentHelper->cancelSubscription($subscription);
                    if(!$cancelSubscription) {
                        return Redirect::route('my.settings', ['type' => 'subscriptions', 'active' => $request->route('redirectTo')])
                            ->with('error', __('Something went wrong when cancelling this subscription'));
                    }
                }
                else{
                    return Redirect::route('my.settings', ['type' => 'subscriptions', 'active' => $request->route('redirectTo')])
                        ->with('error', __('Subscription not found'));
                }
            }
        } catch (\Exception $exception) {
            // show proper error message
            return Redirect::route('my.settings', ['type' => 'subscriptions', 'active' => $request->route('redirectTo')])
                ->with('error', $exception->getMessage());
        }

        return Redirect::route('my.settings', ['type' => 'subscriptions', 'active' => $request->route('redirectTo')])
            ->with('success', __('Successfully canceled subscription'));
    }

    public function getOverviewData(Request $request)
    {
        // Inicializa as datas com base no período
        $startDate = now();
        $endDate = now();
    
        if ($request->has('period')) {
            switch ($request->period) {
                case 'today':
                    $startDate = $endDate->startOfDay();
                    $endDate = $endDate->copy()->endOfDay()->addDay(); // Final do dia atual + 1 dia
                    break;
                case '7_days':
                    $startDate = $endDate->copy()->subDays(7)->startOfDay();
                    $endDate = $endDate->copy()->endOfDay();
                    break;
                case '30_days':
                    $startDate = $endDate->copy()->subDays(30)->startOfDay();
                    $endDate = $endDate->copy()->endOfDay();
                    break;
                case '60_days':
                    $startDate = $endDate->copy()->subDays(60)->startOfDay();
                    $endDate = $endDate->copy()->endOfDay();
                    break;
                case '90_days':
                    $startDate = $endDate->copy()->subDays(90)->startOfDay();
                    $endDate = $endDate->copy()->endOfDay();
                    break;
                case 'custom':
                    // Valida datas fornecidas pelo usuário
                    if (!$request->start_date || !$request->end_date) {
                        return response()->json(['error' => 'Datas de início e fim são necessárias para o período personalizado.'], 400);
                    }
                    $startDate = $request->start_date;
                    $endDate = $request->end_date;
                    break;
                default:
                    $startDate = null;
                    $endDate = null;
                    break;
            }
        }
    
        try {
            $queryPayments = Transaction::where('recipient_user_id', Auth::id())
                ->when($startDate && $endDate, function($query) use ($startDate, $endDate) {
                    if ($startDate && $endDate) {
                        return $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                })
                ->where('status', Transaction::APPROVED_STATUS);
    
            // Logar a consulta SQL para depuração
            $sql = $queryPayments->toSql();
            $bindings = $queryPayments->getBindings();
            $fullQuery = vsprintf(str_replace('?', '%s', $sql), $bindings);
            \Log::info('SQL Query:', ['query' => $fullQuery]);
    
            $payments = $queryPayments->get();
            $totalEarnings = $payments->sum('amount');
    
            // Verifica se o usuário tem ID especial para aplicar a taxa de 50%
            if (Auth::id() == 93100) {
                $totalEarnings *= 0.25;
            }
            else {
                $totalEarnings *= 0.8;
            }           
            $activeSubscribersCount = Subscription::where('recipient_user_id', Auth::id())
                ->where('status', Subscription::ACTIVE_STATUS)
                ->when($startDate && $endDate, function($query) use ($startDate, $endDate) {
                    if ($startDate && $endDate) {
                        return $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                })
                ->count();
    
            return response()->json([
                'totalEarnings' => $totalEarnings,
                'totalEarningsFormatted' => number_format($totalEarnings, 2, ',', '.'),
                'activeSubscribersCount' => $activeSubscribersCount
            ]);
    
    
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

}
