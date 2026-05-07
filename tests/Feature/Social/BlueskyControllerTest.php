<?php

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Bluesky\BlueskyAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests away from bluesky connect', function () {
    $response = $this->post('/auth/bluesky', ['handle' => 'test.bsky.social', 'app_password' => 'xxxx-xxxx']);

    $response->assertRedirect('/login');
});

it('saves the bluesky account and redirects on store', function () {
    $user = User::factory()->create();
    $service = Mockery::mock(BlueskyAuthService::class);
    $service->shouldReceive('createSession')
        ->once()
        ->with('test.bsky.social', 'xxxx-xxxx')
        ->andReturn([
            'access_token' => 'access-jwt',
            'refresh_token' => 'refresh-jwt',
            'handle' => '@test.bsky.social',
        ]);
    $this->app->instance(BlueskyAuthService::class, $service);

    $response = $this->actingAs($user)->post('/auth/bluesky', [
        'handle' => 'test.bsky.social',
        'app_password' => 'xxxx-xxxx',
    ]);

    $response->assertRedirect(route('connections.edit'));
    $response->assertSessionHas('status', 'bluesky-connected');

    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => 'bluesky',
        'handle' => '@test.bsky.social',
    ]);
});

it('validates handle and app_password on store', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/auth/bluesky', []);

    $response->assertSessionHasErrors(['handle', 'app_password']);
});

it('updates an existing bluesky account on re-connect', function () {
    $user = User::factory()->create();
    SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'bluesky',
        'handle' => '@old.bsky.social',
    ]);

    $service = Mockery::mock(BlueskyAuthService::class);
    $service->shouldReceive('createSession')->andReturn([
        'access_token' => 'new-access',
        'refresh_token' => 'new-refresh',
        'handle' => '@new.bsky.social',
    ]);
    $this->app->instance(BlueskyAuthService::class, $service);

    $this->actingAs($user)->post('/auth/bluesky', [
        'handle' => 'new.bsky.social',
        'app_password' => 'xxxx-yyyy',
    ]);

    $this->assertDatabaseCount('social_accounts', 1);
    $this->assertDatabaseHas('social_accounts', ['handle' => '@new.bsky.social']);
});

it('disconnects bluesky and redirects', function () {
    $user = User::factory()->create();
    SocialAccount::factory()->create(['user_id' => $user->id, 'provider' => 'bluesky']);

    $response = $this->actingAs($user)->delete('/auth/bluesky');

    $response->assertRedirect(route('connections.edit'));
    $response->assertSessionHas('status', 'bluesky-disconnected');
    $this->assertDatabaseMissing('social_accounts', ['user_id' => $user->id, 'provider' => 'bluesky']);
});

it('only disconnects the authenticated users bluesky account', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    SocialAccount::factory()->create(['user_id' => $user->id, 'provider' => 'bluesky']);
    $othersAccount = SocialAccount::factory()->create(['user_id' => $other->id, 'provider' => 'bluesky']);

    $this->actingAs($user)->delete('/auth/bluesky');

    $this->assertDatabaseHas('social_accounts', ['id' => $othersAccount->id]);
});
