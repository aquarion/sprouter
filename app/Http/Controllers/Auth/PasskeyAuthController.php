<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasskeyInvalidated;
use App\Models\Passkey;
use App\Models\User;
use App\Services\WebAuthn\WebAuthnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\Denormalizer\WebauthnSerializerFactory;

class PasskeyAuthController extends Controller
{
    public function __construct(private readonly WebAuthnService $webAuthn) {}

    public function options(): Response
    {
        $options = $this->webAuthn->generateAuthenticationOptions();
        session(['passkey.auth.options' => serialize($options)]);

        $serializer = (new WebauthnSerializerFactory(
            new AttestationStatementSupportManager([new NoneAttestationStatementSupport])
        ))->create();

        return response($serializer->serialize($options, 'json'), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function authenticate(Request $request): JsonResponse
    {
        $serialized = session('passkey.auth.options');
        if (! $serialized) {
            return response()->json(['message' => 'No active challenge. Please try again.'], 422);
        }

        $options = unserialize($serialized);
        session()->forget('passkey.auth.options');

        // The browser sends credential_id as base64url; normalize to standard base64 for DB lookup
        $rawId = $request->input('rawId');
        $credentialId = base64_encode(
            base64_decode(strtr($rawId, '-_', '+/'))
        );

        $passkey = Passkey::where('credential_id', $credentialId)->first();
        if (! $passkey) {
            return response()->json(['message' => 'Passkey not recognised.'], 401);
        }

        try {
            $source = $this->webAuthn->verifyAuthentication(
                json_encode($request->all()),
                $options,
                $passkey,
            );
        } catch (Throwable) {
            return response()->json(['message' => 'Passkey verification failed.'], 401);
        }

        if ($source->counter < $passkey->sign_count) {
            /** @var User $user */
            $user = $passkey->user;
            Mail::to($user->email)->send(new PasskeyInvalidated($passkey, automatic: true));
            $passkey->delete();

            return response()->json(['message' => 'Passkey invalidated due to replay attack.'], 401);
        }

        $passkey->update([
            'sign_count' => $source->counter,
            'last_used_at' => now(),
        ]);

        Auth::login($passkey->user, remember: true);

        return response()->json(['redirect' => route('dashboard')]);
    }
}
