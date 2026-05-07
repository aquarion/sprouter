<?php

use App\Services\Feed\PostNormalizer;

it('normalises a mastodon status to unified post format', function () {
    $status = [
        'id' => '109123456789',
        'content' => '<p>hello <strong>world</strong></p>',
        'created_at' => '2024-01-15T10:00:00.000Z',
        'url' => 'https://fosstodon.org/@user/109123456789',
        'account' => [
            'display_name' => 'Test User',
            'acct' => 'user',
            'avatar' => 'https://fosstodon.org/avatars/original/user.jpg',
        ],
        'media_attachments' => [
            [
                'type' => 'image',
                'url' => 'https://fosstodon.org/media/img.jpg',
                'preview_url' => 'https://fosstodon.org/media/img_small.jpg',
                'description' => 'A photo',
            ],
        ],
    ];

    $normalizer = new PostNormalizer;
    $post = $normalizer->fromMastodon($status, 'fosstodon.org');

    expect($post['id'])->toBe('mastodon_109123456789')
        ->and($post['source'])->toBe('mastodon')
        ->and($post['body'])->toBe('hello world')
        ->and($post['author_name'])->toBe('Test User')
        ->and($post['author_handle'])->toBe('@user@fosstodon.org')
        ->and($post['media'][0]['type'])->toBe('image')
        ->and($post['media'][0]['alt_text'])->toBe('A photo');
});

it('normalises a bluesky feed view post to unified post format', function () {
    $feedPost = [
        'post' => [
            'uri' => 'at://did:plc:abc/app.bsky.feed.post/xyz',
            'record' => ['text' => 'hello bluesky', 'createdAt' => '2024-01-15T11:00:00.000Z'],
            'author' => [
                'displayName' => 'Alice',
                'handle' => 'alice.bsky.social',
                'avatar' => 'https://cdn.bsky.app/avatar.jpg',
            ],
            'embed' => [
                '$type' => 'app.bsky.embed.images#view',
                'images' => [
                    [
                        'fullsize' => 'https://cdn.bsky.app/img.jpg',
                        'thumb' => 'https://cdn.bsky.app/img_thumb.jpg',
                        'alt' => 'Sky photo',
                    ],
                ],
            ],
        ],
    ];

    $normalizer = new PostNormalizer;
    $post = $normalizer->fromBluesky($feedPost);

    expect($post['id'])->toBe('bluesky_at://did:plc:abc/app.bsky.feed.post/xyz')
        ->and($post['source'])->toBe('bluesky')
        ->and($post['body'])->toBe('hello bluesky')
        ->and($post['author_name'])->toBe('Alice')
        ->and($post['author_handle'])->toBe('@alice.bsky.social')
        ->and($post['media'][0]['type'])->toBe('image')
        ->and($post['media'][0]['alt_text'])->toBe('Sky photo');
});
