<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Services\Bluesky\BlueskyAuthService;
use Illuminate\Http\Request;

class BlueskyController extends Controller
{
    public function __construct(private BlueskyAuthService $auth) {}

    public function store(Request $request)
    {
        $request->validate([
            'handle' => 'required|string',
            'app_password' => 'required|string', // pragma: allowlist secret
        ]);

        $result = $this->auth->createSession(
            $request->input('handle'),
            $request->input('app_password'),
        );

        SocialAccount::updateOrCreate(
            ['user_id' => $request->user()->id, 'provider' => 'bluesky'],
            [
                'access_token' => $result['access_token'],
                'token_secret' => $result['refresh_token'],
                'handle' => $result['handle'],
            ]
        );

        return redirect()->route('connections.edit')
            ->with('status', 'bluesky-connected');
    }

    public function destroy(Request $request)
    {
        $request->user()->socialAccounts()
            ->where('provider', 'bluesky')
            ->delete();

        return redirect()->route('connections.edit')
            ->with('status', 'bluesky-disconnected');
    }
}
