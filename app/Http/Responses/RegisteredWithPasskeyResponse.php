<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse;

class RegisteredWithPasskeyResponse implements RegisterResponse
{
    public function toResponse($request)
    {
        return redirect()->route('passkey.setup');
    }
}
