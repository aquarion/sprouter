<?php

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Mastodon\MastodonFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('sets auth_failed_at when the timeline returns 401', function () {
    $user = User::factory()->create();
    $account = SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'mastodon',
        'instance_url' => 'https://mastodon.social',
        'access_token' => 'revoked-token',
        'auth_failed_at' => null,
    ]);

    Http::fake([
        'mastodon.social/api/v1/timelines/home*' => Http::response(
            ['error' => 'The access token is invalid'], 401
        ),
    ]);

    $service = new MastodonFeedService;

    expect(fn () => $service->getHomeTimeline($account))
        ->toThrow(RequestException::class);

    expect($account->fresh()->auth_failed_at)->not->toBeNull();
});

it('does not set auth_failed_at on transient 5xx errors', function () {
    $user = User::factory()->create();
    $account = SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'mastodon',
        'instance_url' => 'https://mastodon.social',
        'access_token' => 'valid-token',
        'auth_failed_at' => null,
    ]);

    Http::fake([
        'mastodon.social/api/v1/timelines/home*' => Http::response(
            ['error' => 'Internal Server Error'], 503
        ),
    ]);

    $service = new MastodonFeedService;

    expect(fn () => $service->getHomeTimeline($account))
        ->toThrow(RequestException::class);

    expect($account->fresh()->auth_failed_at)->toBeNull();
});

it('clears auth_failed_at on successful timeline fetch', function () {
    $user = User::factory()->create();
    $account = SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'mastodon',
        'instance_url' => 'https://mastodon.social',
        'access_token' => 'valid-token',
        'auth_failed_at' => now()->subHour(),
    ]);

    Http::fake([
        'mastodon.social/api/v1/timelines/home*' => Http::response([
            ['id' => '123', 'created_at' => now()->toISOString(), 'content' => 'Hello'],
        ]),
    ]);

    $service = new MastodonFeedService;
    $service->getHomeTimeline($account);

    expect($account->fresh()->auth_failed_at)->toBeNull();
});
