<?php

use App\Mail\PasskeyInvalidated;
use App\Models\Passkey;

test('mailable has correct subject for manual deletion', function () {
    $passkey = new Passkey(['name' => 'iPhone 15']);

    $mailable = new PasskeyInvalidated($passkey, automatic: false);

    expect($mailable->envelope()->subject)->toBe('A passkey was removed from your account');
});

test('mailable has correct subject for automatic invalidation', function () {
    $passkey = new Passkey(['name' => 'MacBook']);

    $mailable = new PasskeyInvalidated($passkey, automatic: true);

    expect($mailable->envelope()->subject)->toBe('Security alert: a passkey was disabled on your account');
});

test('mailable renders passkey name in body for manual deletion', function () {
    $passkey = new Passkey(['name' => 'iPhone 15']);

    $mailable = new PasskeyInvalidated($passkey, automatic: false);
    $rendered = $mailable->render();

    expect($rendered)->toContain('iPhone 15')
        ->and($rendered)->toContain('removed');
});

test('mailable renders passkey name and security copy for automatic invalidation', function () {
    $passkey = new Passkey(['name' => 'MacBook']);

    $mailable = new PasskeyInvalidated($passkey, automatic: true);
    $rendered = $mailable->render();

    expect($rendered)->toContain('MacBook')
        ->and($rendered)->toContain('Security alert');
});
