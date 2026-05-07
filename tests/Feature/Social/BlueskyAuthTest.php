<?php

use App\Services\Bluesky\BlueskyAuthService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

it('creates a session with an app password', function () {
    Http::fake([
        'bsky.social/xrpc/com.atproto.server.createSession' => Http::response([
            'accessJwt' => 'access-jwt-token',
            'refreshJwt' => 'refresh-jwt-token',
            'handle' => 'alice.bsky.social',
            'did' => 'did:plc:abc123',
        ]),
    ]);

    $service = new BlueskyAuthService;
    $result = $service->createSession('alice.bsky.social', 'app-password-here');

    expect($result['access_token'])->toBe('access-jwt-token')
        ->and($result['refresh_token'])->toBe('refresh-jwt-token')
        ->and($result['handle'])->toBe('@alice.bsky.social');
});

it('throws on invalid credentials', function () {
    Http::fake([
        'bsky.social/xrpc/com.atproto.server.createSession' => Http::response(
            ['error' => 'AuthenticationRequired'],
            401
        ),
    ]);

    $service = new BlueskyAuthService;

    expect(fn () => $service->createSession('bad@bsky.social', 'wrong'))
        ->toThrow(RequestException::class);
});
