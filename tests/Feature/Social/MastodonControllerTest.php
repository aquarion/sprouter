<?php

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Mastodon\MastodonOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests away from mastodon connect', function () {
    $response = $this->post('/auth/mastodon', ['instance_url' => 'https://fosstodon.org']);

    $response->assertRedirect('/login');
});

it('redirects to the mastodon oauth authorize url', function () {
    $user = User::factory()->create();
    $service = Mockery::mock(MastodonOAuthService::class);
    $service->shouldReceive('getAuthorizeUrl')
        ->once()
        ->andReturn('https://fosstodon.org/oauth/authorize?client_id=abc');
    $this->app->instance(MastodonOAuthService::class, $service);

    $response = $this->actingAs($user)
        ->post('/auth/mastodon', ['instance_url' => 'https://fosstodon.org']);

    $response->assertRedirect('https://fosstodon.org/oauth/authorize?client_id=abc');
    $this->assertEquals('https://fosstodon.org', session('mastodon_instance'));
});

it('validates instance_url on redirect', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/auth/mastodon', ['instance_url' => 'not-a-url']);

    $response->assertSessionHasErrors('instance_url');
});

it('saves the mastodon account on callback and redirects', function () {
    $user = User::factory()->create();
    $service = Mockery::mock(MastodonOAuthService::class);
    $service->shouldReceive('getStoredCredentials')
        ->once()
        ->with('https://fosstodon.org')
        ->andReturn(['client_id' => 'cid', 'client_secret' => 'csecret']);
    $service->shouldReceive('exchangeCode')
        ->once()
        ->andReturn(['access_token' => 'tok', 'handle' => '@testuser@fosstodon.org']);
    $this->app->instance(MastodonOAuthService::class, $service);

    $response = $this->actingAs($user)
        ->withSession(['mastodon_instance' => 'https://fosstodon.org', 'mastodon_oauth_state' => 'teststate'])
        ->get('/auth/mastodon/callback?code=authcode&state=teststate');

    $response->assertRedirect(route('connections.edit'));
    $response->assertSessionHas('status', 'mastodon-connected');

    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => 'mastodon',
        'handle' => '@testuser@fosstodon.org',
        'instance_url' => 'https://fosstodon.org',
    ]);
});

it('updates an existing mastodon account on re-connect', function () {
    $user = User::factory()->create();
    SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'mastodon',
        'handle' => '@old@fosstodon.org',
        'instance_url' => 'https://fosstodon.org',
    ]);

    $service = Mockery::mock(MastodonOAuthService::class);
    $service->shouldReceive('getStoredCredentials')->andReturn(['client_id' => 'cid', 'client_secret' => 'csecret']);
    $service->shouldReceive('exchangeCode')->andReturn(['access_token' => 'newtok', 'handle' => '@new@fosstodon.org']);
    $this->app->instance(MastodonOAuthService::class, $service);

    $this->actingAs($user)
        ->withSession(['mastodon_instance' => 'https://fosstodon.org', 'mastodon_oauth_state' => 'teststate'])
        ->get('/auth/mastodon/callback?code=authcode&state=teststate');

    $this->assertDatabaseCount('social_accounts', 1);
    $this->assertDatabaseHas('social_accounts', ['handle' => '@new@fosstodon.org']);
});

it('disconnects mastodon and redirects', function () {
    $user = User::factory()->create();
    SocialAccount::factory()->create(['user_id' => $user->id, 'provider' => 'mastodon']);

    $response = $this->actingAs($user)->delete('/auth/mastodon');

    $response->assertRedirect(route('connections.edit'));
    $response->assertSessionHas('status', 'mastodon-disconnected');
    $this->assertDatabaseMissing('social_accounts', ['user_id' => $user->id, 'provider' => 'mastodon']);
});

it('only disconnects the authenticated users mastodon account', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    SocialAccount::factory()->create(['user_id' => $user->id, 'provider' => 'mastodon']);
    $othersAccount = SocialAccount::factory()->create(['user_id' => $other->id, 'provider' => 'mastodon']);

    $this->actingAs($user)->delete('/auth/mastodon');

    $this->assertDatabaseHas('social_accounts', ['id' => $othersAccount->id]);
});
