<?php

namespace App\Http\Controllers;

use App\Services\Feed\FeedAggregator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeedController extends Controller
{
    public function __construct(private FeedAggregator $aggregator) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $result = $this->aggregator->fetch($user);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return Inertia::render('feed', [
            'initialPosts' => $result['posts'],
            'initialCursor' => $result['next_cursor'],
        ]);
    }
}
