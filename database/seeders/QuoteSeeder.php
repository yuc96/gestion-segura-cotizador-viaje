<?php

namespace Database\Seeders;

use App\Application\ContractQuote;
use App\Application\CreateQuote;
use App\Application\CreateQuoteCommand;
use App\Models\Quote;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        if (Quote::query()->exists()) {
            return;
        }

        $create = app(CreateQuote::class);
        $contract = app(ContractQuote::class);

        $samples = [
            ['Camila', 'Villacís', '0923456781', 'camila.villacis@example.com', '1992-04-12', 'EC', 20, 9, false],
            ['Andrés', 'Molina', '1712345678', 'andres.molina@example.com', '1988-11-03', 'ES', 40, 9, false],
            ['Sofía', 'Herrera', '0102030405', 'sofia.herrera@example.com', '1995-01-20', 'US', 15, 6, true],
            ['Mateo', 'Ruiz', '1109876543', 'mateo.ruiz@example.com', '1984-07-08', 'BR', 25, 14, false],
            ['Valentina', 'Cruz', '0601122334', 'valentina.cruz@example.com', '1990-09-14', 'JP', 50, 11, true],
            ['Diego', 'Paredes', '1304455667', 'diego.paredes@example.com', '1979-02-28', 'AU', 18, 7, false],
            ['Lucía', 'Benítez', '0911223344', 'lucia.benitez@example.com', '1998-12-01', 'ZA', 12, 4, false],
            ['Joaquín', 'Salas', '1755667788', 'joaquin.salas@example.com', '1986-06-19', 'MX', 33, 3, true],
            ['Elena', 'Torres', '0409988776', 'elena.torres@example.com', '1975-03-30', 'FR', 22, 5, false],
            ['Martín', 'Cevallos', '1803344556', 'martin.cevallos@example.com', '1993-08-11', 'AR', 28, 20, false],
            ['Paula', 'Ríos', '1206677889', 'paula.rios@example.com', '1991-05-05', 'IT', 45, 8, true],
            ['Hugo', 'Zambrano', '0702211334', 'hugo.zambrano@example.com', '1982-10-22', 'CO', 10, 2, false],
        ];

        foreach ($samples as [$first, $last, $identification, $email, $birth, $code, $startsIn, $extraDays, $shouldContract]) {
            $departure = now()->startOfDay()->addDays($startsIn);
            $return = $departure->copy()->addDays($extraDays);

            $quote = $create->handle(new CreateQuoteCommand(
                firstName: $first,
                lastName: $last,
                identification: $identification,
                email: $email,
                birthDate: $birth,
                destinationCode: $code,
                departureDate: $departure->toDateString(),
                returnDate: $return->toDateString(),
            ));

            if ($shouldContract) {
                $contract->handle((int) $quote->id, now()->toDateTimeImmutable());
            }
        }
    }
}
