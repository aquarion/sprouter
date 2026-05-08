<?php

namespace App\Services\Mastodon;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;

class MastodonFeedService
{
    public function getStatus(SocialAccount $account, string $id): ?array
    {
        try {
            return Http::timeout(15)->withToken($account->access_token)
                ->get("{$account->instance_url}/api/v1/statuses/{$id}")
                ->throw()
                ->json();
        } catch (\Throwable) {
            return null;
        }
    }

    public function getHomeTimeline(SocialAccount $account, int $limit = 20, ?string $maxId = null): array
    {
        $params = ['limit' => $limit];
        if ($maxId !== null) {
            $params['max_id'] = $maxId;
        }

        return Http::timeout(15)->withToken($account->access_token)
            ->get("{$account->instance_url}/api/v1/timelines/home", $params)
            ->throw()
            ->json();
    }
}
