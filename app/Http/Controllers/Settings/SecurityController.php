<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/security', [
            'passkeys' => $request->user()->passkeys()
                ->select('id', 'name', 'last_used_at', 'created_at')
                ->latest()
                ->get(),
        ]);
    }
}
