<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'api_key',
        'api_secret',
        'api_passphrase',
        'is_active',
        'settings',
    ];

    // RELACIONAMENTO
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
