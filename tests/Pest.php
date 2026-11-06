<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function quotePayload(array $overrides = []): array
{
    $departure = now()->startOfDay()->addDays(30);

    return array_merge([
        'first_name' => 'María José',
        'last_name' => 'Pérez-García',
        'identification' => 'ab12cd34',
        'email' => 'Maria.Jose@Example.com',
        'birth_date' => '1990-04-12',
        'destination_code' => 'es',
        'departure_date' => $departure->toDateString(),
        'return_date' => $departure->copy()->addDays(9)->toDateString(),
    ], $overrides);
}

function loginAsAdmin(): void
{
    User::factory()->create([
        'email' => 'admin@gestionsegura.test',
        'password' => 'password',
    ]);

    test()->postJson('/login', [
        'email' => 'admin@gestionsegura.test',
        'password' => 'password',
    ])->assertOk();
}
