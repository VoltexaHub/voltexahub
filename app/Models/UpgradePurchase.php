<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpgradePurchase extends Model
{
    protected $fillable = [
        'user_id',
        'upgrade_plan_id',
        'payment_method',
        'amount_paid',
        'stripe_session_id',
        'status',
        'delivered_at',
    ];

    protected $casts = [
        'amount_paid' => 'float',
        'delivered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function upgradePlan(): BelongsTo
    {
        return $this->belongsTo(UpgradePlan::class);
    }
}
