<?php

namespace App\Services\Feed;

use App\Models\User;
use App\Services\Bluesky\BlueskyFeedService;
use App\Services\Mastodon\MastodonFeedService;

class FeedAggregator
{
    public function __construct(
        private MastodonFeedService $mastodon,
        private BlueskyFeedService $bluesky,
        private PostNormalizer $normalizer,
    ) {}

    public function fetch(User $user, int $limit = 20, ?string $cursor = null): array
    {
        $cursors = $cursor ? json_decode(base64_decode($cursor), true) : [];
        $posts = collect();

        foreach ($user->socialAccounts as $account) {
            $accountCursor = $cursors[$account->id] ?? null;

            if ($account->provider === 'mastodon') {
                $host = parse_url($account->instance_url, PHP_URL_HOST);
                $statuses = $this->mastodon->getHomeTimeline($account, $limit, $accountCursor);
                $normalised = array_map(fn ($s) => $this->normalizer->fromMastodon($s, $host), $statuses);
                $nextCursor = ! empty($statuses) ? end($statuses)['id'] : null;
                $posts = $posts->concat($normalised);
                if ($nextCursor) {
                    $cursors[$account->id] = $nextCursor;
                }
            }

            if ($account->provider === 'bluesky') {
                $result = $this->bluesky->getHomeTimeline($account, $limit, $accountCursor);
                $normalised = array_map(fn ($p) => $this->normalizer->fromBluesky($p), $result['posts']);
                $posts = $posts->concat($normalised);
                if ($result['cursor']) {
                    $cursors[$account->id] = $result['cursor'];
                }
            }
        }

        $sorted = $posts->sortByDesc('created_at')->values()->take($limit)->all();
        $nextCursor = ! empty($sorted) ? base64_encode(json_encode($cursors)) : null;

        return ['posts' => $sorted, 'next_cursor' => $nextCursor];
    }
}
