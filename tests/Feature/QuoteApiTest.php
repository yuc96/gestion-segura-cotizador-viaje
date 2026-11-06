<?php

use App\Domain\QuoteStatus;
use App\Models\Quote;
use Illuminate\Support\Facades\Log;

it('quotes a ten day trip to Spain at 36 dollars', function () {
    $response = $this->postJson('/api/quotes', quotePayload());

    $response->assertCreated()
        ->assertJsonPath('data.status', 'Cotizado')
        ->assertJsonPath('data.destination.code', 'ES')
        ->assertJsonPath('data.destination.name', 'España')
        ->assertJsonPath('data.destination.pricing_region', 'Europe')
        ->assertJsonPath('data.trip_days', 10)
        ->assertJsonPath('data.pricing.daily_rate', '3.00')
        ->assertJsonPath('data.pricing.base_amount', '30.00')
        ->assertJsonPath('data.pricing.surcharge_percent', 20)
        ->assertJsonPath('data.pricing.surcharge_amount', '6.00')
        ->assertJsonPath('data.pricing.total_amount', '36.00')
        ->assertJsonPath('data.contracted_at', null)
        ->assertJsonPath('data.insured.identification', 'AB12CD34')
        ->assertJsonPath('data.insured.email', 'maria.jose@example.com')
        ->assertJsonPath('data.insured.full_name', 'María José Pérez-García');

    $this->assertDatabaseHas('quotes', [
        'id' => $response->json('data.id'),
        'status' => 'Cotizado',
        'total_amount_cents' => 3600,
        'trip_days' => 10,
    ]);
});

it('quotes south america without a surcharge and north america with 15 percent', function () {
    $this->postJson('/api/quotes', quotePayload([
        'destination_code' => 'EC',
    ]))->assertCreated()
        ->assertJsonPath('data.pricing.surcharge_percent', 0)
        ->assertJsonPath('data.pricing.total_amount', '30.00');

    $sameDay = now()->startOfDay()->addDays(10)->toDateString();

    $this->postJson('/api/quotes', quotePayload([
        'destination_code' => 'US',
        'departure_date' => $sameDay,
        'return_date' => $sameDay,
    ]))->assertCreated()
        ->assertJsonPath('data.trip_days', 1)
        ->assertJsonPath('data.pricing.base_amount', '3.00')
        ->assertJsonPath('data.pricing.surcharge_amount', '0.45')
        ->assertJsonPath('data.pricing.total_amount', '3.45');
});

it('quotes seven days in Japan with a 25 percent surcharge', function () {
    $departure = now()->startOfDay()->addDays(20);

    $this->postJson('/api/quotes', quotePayload([
        'destination_code' => 'JP',
        'departure_date' => $departure->toDateString(),
        'return_date' => $departure->copy()->addDays(6)->toDateString(),
    ]))->assertCreated()
        ->assertJsonPath('data.trip_days', 7)
        ->assertJsonPath('data.pricing.base_amount', '21.00')
        ->assertJsonPath('data.pricing.surcharge_amount', '5.25')
        ->assertJsonPath('data.pricing.total_amount', '26.25');
});

it('rejects an incomplete quote', function () {
    $this->postJson('/api/quotes', [])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Revisa los datos de la cotización.')
        ->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'identification',
            'email',
            'birth_date',
            'destination_code',
            'departure_date',
            'return_date',
        ]);
});

it('rejects dates, age and destination that break the travel rules', function () {
    $this->postJson('/api/quotes', quotePayload([
        'email' => 'no-es-un-correo',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['email']);

    $this->postJson('/api/quotes', quotePayload([
        'departure_date' => now()->subDay()->toDateString(),
        'return_date' => now()->addDays(3)->toDateString(),
    ]))->assertUnprocessable()->assertJsonValidationErrors(['departure_date']);

    $departure = now()->addDays(15)->toDateString();

    $this->postJson('/api/quotes', quotePayload([
        'departure_date' => $departure,
        'return_date' => now()->addDays(10)->toDateString(),
    ]))->assertUnprocessable()->assertJsonValidationErrors(['return_date']);

    $this->postJson('/api/quotes', quotePayload([
        'birth_date' => now()->subYears(16)->toDateString(),
    ]))->assertUnprocessable()
        ->assertJsonPath('errors.birth_date.0', 'El asegurado debe tener al menos 18 años en la fecha de salida.');

    $this->postJson('/api/quotes', quotePayload([
        'birth_date' => now()->subYears(90)->toDateString(),
    ]))->assertUnprocessable()
        ->assertJsonPath('errors.birth_date.0', 'El asegurado no puede superar los 85 años en la fecha de salida.');

    $tooLong = now()->startOfDay()->addDays(5);

    $this->postJson('/api/quotes', quotePayload([
        'departure_date' => $tooLong->toDateString(),
        'return_date' => $tooLong->copy()->addDays(365)->toDateString(),
    ]))->assertUnprocessable()->assertJsonValidationErrors(['return_date']);

    $this->postJson('/api/quotes', quotePayload([
        'destination_code' => 'ZZ',
    ]))->assertUnprocessable()
        ->assertJsonPath('errors.destination_code.0', 'El país de destino no está disponible.');
});

it('contracts a quote once and keeps the stored premium', function () {
    $id = $this->postJson('/api/quotes', quotePayload())->json('data.id');

    $this->postJson("/api/quotes/{$id}/contract")
        ->assertOk()
        ->assertJsonPath('data.status', 'Contratado')
        ->assertJsonPath('data.pricing.total_amount', '36.00');

    expect(Quote::query()->find($id)->status)->toBe(QuoteStatus::Contracted)
        ->and(Quote::query()->find($id)->contracted_at)->not->toBeNull();

    $this->postJson("/api/quotes/{$id}/contract")
        ->assertStatus(409)
        ->assertJsonPath('message', 'Esta cotización ya fue contratada.');
});

it('shows one quote and a json 404 when it does not exist', function () {
    $id = Quote::factory()->create()->id;

    $this->getJson("/api/quotes/{$id}")
        ->assertOk()
        ->assertJsonPath('data.number', sprintf('GS-%06d', $id))
        ->assertJsonPath('data.destination.name', 'España');

    $this->getJson('/api/quotes/999999')->assertNotFound();
});

it('keeps the quote list private and records the contract as json', function () {
    $this->getJson('/api/quotes', ['Origin' => 'http://localhost'])->assertUnauthorized();

    $path = storage_path('logs/structured-quote-test.log');
    @unlink($path);
    config(['logging.channels.json.handler_with.stream' => $path]);
    Log::forgetChannel('json');

    $id = $this->postJson('/api/quotes', quotePayload())->json('data.id');
    $this->postJson("/api/quotes/{$id}/contract")->assertOk();

    $raw = (string) file_get_contents($path);
    expect($raw)->toContain('"quote.created"')
        ->and($raw)->toContain('"quote.contracted"')
        ->and($raw)->toContain('"total_cents":3600');

    @unlink($path);
});

it('filters and paginates the quote list', function () {
    loginAsAdmin();
    $match = Quote::factory()->create();
    $match->insured->update([
        'first_name' => 'Camila',
        'last_name' => 'Villacís',
        'identification' => '0923456781',
    ]);
    Quote::factory()->create([
        'destination_name' => 'Japón',
        'destination_code' => 'JP',
        'departure_date' => '2027-04-01',
        'return_date' => '2027-04-08',
    ]);
    Quote::factory()->contracted()->create();
    Quote::factory()->count(9)->create();

    $this->getJson('/api/quotes?search=0923456781')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.insured.full_name', 'Camila Villacís');

    $this->getJson('/api/quotes?search=Japón')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.destination.code', 'JP');

    $this->getJson('/api/quotes?status=Contratado')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'Contratado');

    $this->getJson('/api/quotes?per_page=10')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 12)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.current_page', 1);

    $this->getJson('/api/quotes?status=NoExiste')->assertUnprocessable();
});

it('filters by departure date', function () {
    loginAsAdmin();
    $inside = Quote::factory()->create([
        'departure_date' => '2026-12-15',
        'return_date' => '2026-12-20',
    ]);
    Quote::factory()->create([
        'departure_date' => '2027-02-01',
        'return_date' => '2027-02-06',
    ]);

    $this->getJson('/api/quotes?departure_from=2026-12-01&departure_to=2026-12-31')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inside->id);
});

it('downloads the quote as a pdf', function () {
    $quote = Quote::factory()->create();

    $response = $this->get("/api/quotes/{$quote->id}/pdf");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF')
        ->and(strlen($response->getContent()))->toBeGreaterThan(1000);
});
