<?php

namespace App\Model;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array
     */
    protected $fillable = [
        'coupon_code',
        'discount_type',
        'discount_percent',
        'amount_off',
        'expiration_type',
        'usage_limit',
        'expires_at',
        'creator_id',
        'times_used',
        'duration_in_months',
        'stripe_coupon_id',
        'payment_method',   // <— adicionamos aqui
    ];

    /**
     * Valores padrão para atributos (opcional).
     * Se quiser que, ao criar um cupom sem especificar payment_method,
     * já venha como "all", pode definir aqui.
     */
    protected $attributes = [
        'status'         => 'active',
        'payment_method' => 'all',  // <— valor padrão “all”
    ];

    /**
     * Conversões de tipo para colunas específicas.
     *
     * @var array
     */
    protected $casts = [
        'expires_at'      => 'datetime',
        'discount_percent'=> 'float',
        'amount_off'      => 'integer',
        'usage_limit'     => 'integer',
        'times_used'      => 'integer',
        // Não precisamos de cast para payment_method (string)
    ];

    /**
     * Verifica se o cupom está expirado, de acordo com o tipo de expiração.
     *
     * @return bool
     */
    public function isExpired()
    {
        return match ($this->expiration_type) {
            'usage' => $this->times_used >= $this->usage_limit,
            'date'  => Carbon::now()->gt($this->expires_at),
            default => false,
        };
    }

    /**
     * Retorna a duração esperada pelo Stripe para o tipo de expiração.
     * (Mantive o método original, mas atenção: na criação de cupom
     * usamos o helper do Controller em vez deste.)
     */
    protected function getStripeDuration($expirationType)
    {
        return match ($expirationType) {
            'date' => 'repeating',
            default => 'forever',
        };
    }

    /**
     * Verifica se o cupom está ativo.
     *
     * @return bool
     */
    public function isActive()
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Calcula o valor do desconto dado um preço.
     *
     * @param float $price
     * @return float
     */
    public function getDiscountAmount($price)
    {
        if ($this->discount_type === 'fixed' && $this->amount_off !== null) {
            return $this->amount_off / 100;
        } elseif ($this->discount_type === 'percent' && $this->discount_percent !== null) {
            return ($price * $this->discount_percent) / 100;
        }
        return 0;
    }

    /**
     * Incrementa o contador de uso.
     *
     * @return bool
     */
    public function incrementUsage()
    {
        $this->times_used++;
        return $this->save();
    }

    /**
     * Verifica se o cupom é válido (não expirado pelo uso ou data).
     *
     * @return bool
     */
    public function isValid()
    {
        return !$this->isExpired();
    }

    /**
     * Retorna quantos usos ainda restam (se for coupon de tipo "usage").
     *
     * @return int|null
     */
    public function getRemainingUses()
    {
        if ($this->expiration_type !== 'usage') {
            return null;
        }

        return max(0, $this->usage_limit - $this->times_used);
    }

    /*
     * RELACIONAMENTOS
     */

    /**
     * Retorna o usuário que criou o cupom.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Transações que usaram este cupom (se você tiver uma model Transaction).
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Escopo para buscar apenas cupons válidos (não expirados).
     */
    public function scopeValid($query)
    {
        return $query->where(function($query) {
            $query->where('expiration_type', 'never')
                  ->orWhere(function($query) {
                      $query->where('expiration_type', 'usage')
                            ->whereRaw('times_used < usage_limit');
                  })
                  ->orWhere(function($query) {
                      $query->where('expiration_type', 'date')
                            ->where('expires_at', '>', Carbon::now());
                  });
        });
    }
}
