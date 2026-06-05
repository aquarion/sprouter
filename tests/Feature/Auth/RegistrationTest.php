<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $this->get(route('register'))->assertOk();
});

test('new users can register with name and email', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('passkey.setup'));
});

test('registration requires name and email', function () {
    $this->post(route('register.store'), [])
        ->assertSessionHasErrors(['name', 'email']);
});

test('registration rejects duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Another User',
        'email' => 'taken@example.com',
    ])->assertSessionHasErrors('email');
});

test('passkey setup page is accessible to authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withoutVite()->get(route('passkey.setup'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/passkey-setup'));
});
