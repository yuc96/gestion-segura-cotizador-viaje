<?php

use App\Models\Quote;
use App\Models\User;

it('rejects a guest from the quote list and still lets them quote', function () {
    $this->getJson('/api/quotes', ['Origin' => 'http://localhost'])->assertUnauthorized();
    $this->getJson('/api/user', ['Origin' => 'http://localhost'])->assertUnauthorized();

    $this->postJson('/api/quotes', quotePayload())->assertCreated();
});

it('logs an administrator in and out', function () {
    User::factory()->create([
        'name' => 'Administración',
        'email' => 'admin@gestionsegura.test',
        'password' => 'password',
    ]);

    $this->postJson('/login', [
        'email' => 'admin@gestionsegura.test',
        'password' => 'no-es',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Estas credenciales no coinciden con nuestros registros.');

    $this->postJson('/login', [
        'email' => 'admin@gestionsegura.test',
        'password' => 'password',
    ], ['Origin' => 'http://localhost'])
        ->assertOk()
        ->assertJsonPath('data.email', 'admin@gestionsegura.test')
        ->assertJsonPath('data.name', 'Administración');

    $this->getJson('/api/user', ['Origin' => 'http://localhost'])
        ->assertOk()
        ->assertJsonPath('data.email', 'admin@gestionsegura.test');

    Quote::factory()->create();

    $this->getJson('/api/quotes', ['Origin' => 'http://localhost'])
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->postJson('/logout')->assertOk();
    auth()->forgetGuards();

    $this->getJson('/api/quotes', ['Origin' => 'http://localhost'])->assertUnauthorized();
    $this->assertGuest();
});
