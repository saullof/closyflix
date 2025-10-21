<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    public const REQUESTED_STATUS = 'requested';

    public const REJECTED_STATUS = 'rejected';

    public const APPROVED_STATUS = 'approved';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'message',
        'processed',
        'payment_identifier',
        'payment_method',
        'fee',
        'pix_key_type',
        'pix_beneficiary_name',
        'pix_document',
        'suitpay_cashout_id',
        'suitpay_cashout_status',
        'suitpay_cashout_payload',
        'suitpay_cashout_response',
        'suitpay_cashout_error',
        'suitpay_cashout_requested_at',
        'suitpay_cashout_processed_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'processed' => 'boolean',
        'suitpay_cashout_payload' => 'array',
        'suitpay_cashout_response' => 'array',
        'suitpay_cashout_requested_at' => 'datetime',
        'suitpay_cashout_processed_at' => 'datetime',
    ];

    /*
     * Relationships
     */

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
