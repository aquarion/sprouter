<?php

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Mastodon\MastodonOAuthService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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

it('prepends https:// to a bare domain', function () {
    $user = User::factory()->create();
    $service = Mockery::mock(MastodonOAuthService::class);
    $service->shouldReceive('getAuthorizeUrl')
        ->once()
        ->with('https://fosstodon.org', Mockery::any())
        ->andReturn('https://fosstodon.org/oauth/authorize?client_id=abc');
    $this->app->instance(MastodonOAuthService::class, $service);

    $response = $this->actingAs($user)
        ->post('/auth/mastodon', ['instance_url' => 'fosstodon.org']);

    $response->assertRedirect('https://fosstodon.org/oauth/authorize?client_id=abc');
});

it('validates instance_url on redirect', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/auth/mastodon', ['instance_url' => 'not-a-url']);

    $response->assertSessionHasErrors('instance_url');
});

it('returns a validation error when the instance is unreachable', function () {
    $user = User::factory()->create();
    $service = Mockery::mock(MastodonOAuthService::class);
    $service->shouldReceive('getAuthorizeUrl')
        ->once()
        ->andThrow(new RequestException(
            new Illuminate\Http\Client\Response(
                new Response(404, [], '')
            )
        ));
    $this->app->instance(MastodonOAuthService::class, $service);

    $response = $this->actingAs($user)
        ->post('/auth/mastodon', ['instance_url' => 'https://fosstodon.org']);

    $response->assertSessionHasErrors('instance_url');
});

it('saves a new mastodon account on callback', function () {
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

it('allows connecting a second mastodon account on a different instance', function () {
    $user = User::factory()->create();
    SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'mastodon',
        'instance_url' => 'https://fosstodon.org',
        'handle' => '@alice@fosstodon.org',
    ]);

    $service = Mockery::mock(MastodonOAuthService::class);
    $service->shouldReceive('getStoredCredentials')->andReturn(['client_id' => 'cid', 'client_secret' => 'csecret']);
    $service->shouldReceive('exchangeCode')->andReturn(['access_token' => 'tok', 'handle' => '@alice@mastodon.social']);
    $this->app->instance(MastodonOAuthService::class, $service);

    $response = $this->actingAs($user)
        ->withSession(['mastodon_instance' => 'https://mastodon.social', 'mastodon_oauth_state' => 'state'])
        ->get('/auth/mastodon/callback?code=code&state=state');

    $response->assertSessionHas('status', 'mastodon-connected');
    $this->assertDatabaseCount('social_accounts', 2);
});

it('allows connecting accounts with the same handle on different instances', function () {
    $user = User::factory()->create();
    SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'mastodon',
        'instance_url' => 'https://fosstodon.org',
        'handle' => '@alice@fosstodon.org',
    ]);

    $service = Mockery::mock(MastodonOAuthService::class);
    $service->shouldReceive('getStoredCredentials')->andReturn(['client_id' => 'cid', 'client_secret' => 'csecret']);
    $service->shouldReceive('exchangeCode')->andReturn(['access_token' => 'tok', 'handle' => '@alice@fosstodon.org']);
    $this->app->instance(MastodonOAuthService::class, $service);

    // Different instance, same handle — should succeed
    $response = $this->actingAs($user)
        ->withSession(['mastodon_instance' => 'https://social.coop', 'mastodon_oauth_state' => 'state'])
        ->get('/auth/mastodon/callback?code=code&state=state');

    $response->assertSessionHas('status', 'mastodon-connected');
    $this->assertDatabaseCount('social_accounts', 2);
});

it('redirects with mastodon-already-connected for a duplicate instance and handle', function () {
    $user = User::factory()->create();
    SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'mastodon',
        'instance_url' => 'https://fosstodon.org',
        'handle' => '@alice@fosstodon.org',
    ]);

    $service = Mockery::mock(MastodonOAuthService::class);
    $service->shouldReceive('getStoredCredentials')->andReturn(['client_id' => 'cid', 'client_secret' => 'csecret']);
    $service->shouldReceive('exchangeCode')->andReturn(['access_token' => 'tok', 'handle' => '@alice@fosstodon.org']);
    $this->app->instance(MastodonOAuthService::class, $service);

    $response = $this->actingAs($user)
        ->withSession(['mastodon_instance' => 'https://fosstodon.org', 'mastodon_oauth_state' => 'state'])
        ->get('/auth/mastodon/callback?code=code&state=state');

    $response->assertSessionHas('status', 'mastodon-already-connected');
    $this->assertDatabaseCount('social_accounts', 1);
});

it('returns a validation error when the mastodon instance connection times out', function () {
    $user = User::factory()->create();
    $service = Mockery::mock(MastodonOAuthService::class);
    $service->shouldReceive('getAuthorizeUrl')
        ->once()
        ->andThrow(new ConnectionException('Connection timed out'));
    $this->app->instance(MastodonOAuthService::class, $service);

    $response = $this->actingAs($user)
        ->post('/auth/mastodon', ['instance_url' => 'https://fosstodon.org']);

    $response->assertSessionHasErrors('instance_url');
});

it('redirects guests away from mastodon instances', function () {
    $response = $this->get('/auth/mastodon/instances?q=ma');

    $response->assertRedirect('/login');
});

it('returns empty array when query is shorter than 2 characters', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/auth/mastodon/instances?q=m');

    $response->assertOk();
    $response->assertExactJson([]);
});

it('returns filtered instances matching the query', function () {
    Http::fake([
        'api.joinmastodon.org/servers' => Http::response([
            ['domain' => 'mastodon.social', 'description' => "The original server\r\n"],
            ['domain' => 'fosstodon.org', 'description' => 'Open source focused'],
            ['domain' => 'hachyderm.io', 'description' => 'A tech community'],
        ], 200),
    ]);

    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/auth/mastodon/instances?q=ma');

    $response->assertOk();
    $response->assertJson([
        ['name' => 'mastodon.social', 'description' => 'The original server'],
    ]);
    // fosstodon.org does not contain 'ma'
    $this->assertCount(1, $response->json());
});

it('returns empty array when the upstream fetch fails', function () {
    Http::fake([
        'api.joinmastodon.org/servers' => Http::response(null, 503),
    ]);
    Cache::forget('mastodon_servers_list');

    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/auth/mastodon/instances?q=ma');

    $response->assertOk();
    $response->assertExactJson([]);
});

it('caches the server list and does not re-fetch on subsequent requests', function () {
    Http::fake([
        'api.joinmastodon.org/servers' => Http::response([
            ['domain' => 'mastodon.social', 'description' => 'The original server'],
        ], 200),
    ]);
    Cache::forget('mastodon_servers_list');

    $user = User::factory()->create();
    $this->actingAs($user)->get('/auth/mastodon/instances?q=ma');
    $this->actingAs($user)->get('/auth/mastodon/instances?q=ma');

    Http::assertSentCount(1);
});

it('returns at most 8 instances even when more match the query', function () {
    $servers = array_map(
        fn ($i) => ['domain' => "mastodon-{$i}.social", 'description' => 'A mastodon instance'],
        range(1, 12),
    );

    Http::fake(['api.joinmastodon.org/servers' => Http::response($servers, 200)]);
    Cache::forget('mastodon_servers_list');

    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/auth/mastodon/instances?q=ma');

    $response->assertOk();
    $this->assertCount(8, $response->json());
});

it('matches instances by description as well as domain', function () {
    Http::fake([
        'api.joinmastodon.org/servers' => Http::response([
            ['domain' => 'fosstodon.org', 'description' => 'Open source focused'],
            ['domain' => 'hachyderm.io', 'description' => 'A tech community'],
        ], 200),
    ]);
    Cache::forget('mastodon_servers_list');

    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/auth/mastodon/instances?q=open+source');

    $response->assertOk();
    $response->assertJson([['name' => 'fosstodon.org']]);
    $this->assertCount(1, $response->json());
});
