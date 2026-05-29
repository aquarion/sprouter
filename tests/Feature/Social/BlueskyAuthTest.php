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

it('refreshes a session with a refresh token', function () {
    Http::fake([
        'bsky.social/xrpc/com.atproto.server.refreshSession' => Http::response([
            'accessJwt' => 'new-access-jwt',
            'refreshJwt' => 'new-refresh-jwt',
        ]),
    ]);

    $service = new BlueskyAuthService;
    $result = $service->refreshSession('old-refresh-jwt');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://bsky.social/xrpc/com.atproto.server.refreshSession'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer old-refresh-jwt')
            && $request->data() === [];
    });

    expect($result['access_token'])->toBe('new-access-jwt')
        ->and($result['refresh_token'])->toBe('new-refresh-jwt');
});

it('creates a session using a custom PDS url', function () {
    Http::fake([
        'mypds.example.com/xrpc/com.atproto.server.createSession' => Http::response([
            'accessJwt' => 'access-jwt-token',
            'refreshJwt' => 'refresh-jwt-token',
            'handle' => 'alice.example.com',
            'did' => 'did:plc:abc123',
        ]),
    ]);

    $service = new BlueskyAuthService;
    $result = $service->createSession('alice.example.com', 'app-password-here', 'https://mypds.example.com');

    expect($result['handle'])->toBe('@alice.example.com');

    Http::assertSent(fn ($req) => $req->url() === 'https://mypds.example.com/xrpc/com.atproto.server.createSession'
    );
});

it('refreshes a session using a custom PDS url', function () {
    Http::fake([
        'mypds.example.com/xrpc/com.atproto.server.refreshSession' => Http::response([
            'accessJwt' => 'new-access-jwt',
            'refreshJwt' => 'new-refresh-jwt',
        ]),
    ]);

    $service = new BlueskyAuthService;
    $result = $service->refreshSession('old-refresh-jwt', 'https://mypds.example.com');

    Http::assertSent(fn ($req) => $req->url() === 'https://mypds.example.com/xrpc/com.atproto.server.refreshSession'
    );

    expect($result['access_token'])->toBe('new-access-jwt');
});
