<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_client_id',
        'plan',
        'price',
        'starts_at',
        'ends_at',
        'is_active'
    ];

    public function client()
    {
        return $this->belongsTo(SystemClient::class, 'system_client_id');
    }
}