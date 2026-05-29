<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use Illuminate\Http\Request;

class ConnectionsController extends Controller
{
    public function destroy(Request $request, SocialAccount $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        $provider = $account->provider;

        $account->delete();

        return redirect()->route('connections.edit')
            ->with('status', $provider.'-disconnected');
    }
}
