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
        $provider = fake()->randomElement(['mastodon', 'bluesky']);

        return [
            'user_id' => User::factory(),
            'provider' => $provider,
            'instance_url' => $provider === 'bluesky' ? 'https://bsky.social' : 'https://'.fake()->domainName(),
            'access_token' => fake()->sha256(),
            'token_secret' => null,
            'handle' => $provider === 'bluesky'
                ? '@'.fake()->userName().'.bsky.social'
                : '@'.fake()->userName().'@'.fake()->domainName(),
            'auth_failed_at' => null,
        ];
    }
}
