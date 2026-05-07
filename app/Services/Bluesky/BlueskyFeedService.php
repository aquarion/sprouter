<?php

namespace App\Services\Bluesky;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;

class BlueskyFeedService
{
    private const BASE = 'https://bsky.social/xrpc';

    public function getHomeTimeline(SocialAccount $account, int $limit = 20, ?string $cursor = null): array
    {
        $params = ['limit' => $limit];
        if ($cursor !== null) {
            $params['cursor'] = $cursor;
        }

        $response = Http::withToken($account->access_token)
            ->get(self::BASE.'/app.bsky.feed.getTimeline', $params)
            ->throw()
            ->json();

        return [
            'posts' => $response['feed'] ?? [],
            'cursor' => $response['cursor'] ?? null,
        ];
    }
}
