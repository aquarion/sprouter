<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'provider', 'instance_url',
        'access_token', 'token_secret', 'handle',
        'auth_failed_at',
    ];

    protected $hidden = ['access_token', 'token_secret'];

    protected $casts = [
        'access_token' => 'encrypted',  // pragma: allowlist secret
        'token_secret' => 'encrypted',
        'auth_failed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
