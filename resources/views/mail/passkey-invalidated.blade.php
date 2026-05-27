<x-mail::message>
@if ($automatic)
# Security alert

A passkey named **{{ $passkey->name }}** on your account was automatically disabled due to a potential security issue.

If you did not trigger this, please contact support immediately and consider changing your password.
@else
# Passkey removed

The passkey named **{{ $passkey->name }}** was removed from your account.

If you did not do this, please contact support immediately.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
