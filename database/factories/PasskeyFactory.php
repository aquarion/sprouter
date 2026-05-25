<?php

namespace Database\Factories;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Passkey>
 */
class PasskeyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'credential_id' => base64_encode(random_bytes(32)),
            'public_key' => base64_encode(random_bytes(64)),
            'sign_count' => 0,
            'transports' => ['internal'],
            'last_used_at' => null,
        ];
    }
}
