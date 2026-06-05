<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasskeyRecoveryToken extends Model
{
    protected $fillable = ['user_id'];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    public static function createForUser(User $user, string $rawToken): self
    {
        $record = new self;
        $record->user_id = $user->id;
        $record->token = hash('sha256', $rawToken);
        $record->save();

        return $record;
    }

    public function consume(): void
    {
        $this->used_at = now();
        $this->save();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
