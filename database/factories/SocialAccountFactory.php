<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(['mastodon', 'bluesky']),
            'instance_url' => null,
            'access_token' => fake()->sha256(),
            'token_secret' => null,
            'handle' => '@'.fake()->userName().'@example.social',
        ];
    }
}
