<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasskeyRecoveryToken extends Model
{
    protected $fillable = ['user_id', 'token', 'used_at'];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->created_at->addHour()->isPast();
    }

    public function isValid(): bool
    {
        return $this->used_at === null && ! $this->isExpired();
    }
}
