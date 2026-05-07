<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Services\Mastodon\MastodonOAuthService;
use Illuminate\Http\Request;

class MastodonController extends Controller
{
    public function __construct(private MastodonOAuthService $oauth) {}

    public function redirect(Request $request)
    {
        $request->validate(['instance_url' => 'required|url']);

        $instance = rtrim($request->input('instance_url'), '/');
        $redirectUri = route('mastodon.callback');

        session(['mastodon_instance' => $instance]);

        return redirect($this->oauth->getAuthorizeUrl($instance, $redirectUri));
    }

    public function callback(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $instance = session('mastodon_instance');
        $clientId = session("mastodon_client_id_{$instance}");
        $clientSecret = session("mastodon_client_secret_{$instance}");

        $result = $this->oauth->exchangeCode(
            instance: $instance,
            code: $request->input('code'),
            clientId: $clientId,
            clientSecret: $clientSecret,
            redirectUri: route('mastodon.callback'),
        );

        SocialAccount::updateOrCreate(
            ['user_id' => $request->user()->id, 'provider' => 'mastodon'],
            [
                'instance_url' => $instance,
                'access_token' => $result['access_token'],
                'handle' => $result['handle'],
            ]
        );

        return redirect()->route('connections.edit')
            ->with('status', 'mastodon-connected');
    }

    public function destroy(Request $request)
    {
        $request->user()->socialAccounts()
            ->where('provider', 'mastodon')
            ->delete();

        return redirect()->route('connections.edit')
            ->with('status', 'mastodon-disconnected');
    }
}
