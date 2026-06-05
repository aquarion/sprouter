<x-mail::message>
# Recover access to your account

Hi {{ $user->name }},

We received a request to recover access to your account. Click the button below to add a new passkey.

<x-mail::button :url="$recoveryUrl">
Recover my account
</x-mail::button>

This link expires in **1 hour** and can only be used once.

If you did not request this, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
