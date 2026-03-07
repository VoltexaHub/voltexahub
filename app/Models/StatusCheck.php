<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusCheck extends Model
{
    protected $fillable = [
        'service',
        'status',
        'message',
        'is_override',
        'checked_at',
    ];

    protected $casts = [
        'is_override' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function scopeLatestPerService($query)
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('status_checks')
                ->groupBy('service');
        });
    }

    public function scopeOverrides($query)
    {
        return $query->where('is_override', true);
    }
}
