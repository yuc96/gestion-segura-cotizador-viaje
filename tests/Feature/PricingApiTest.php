<?php

it('publishes the daily rate and the regional surcharge table', function () {
    $this->getJson('/api/pricing')
        ->assertOk()
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.daily_rate', '3.00')
        ->assertJsonPath('data.max_days', 365)
        ->assertJsonFragment(['code' => 'South America', 'label' => 'Sudamérica', 'surcharge_percent' => 0])
        ->assertJsonFragment(['code' => 'Europe', 'label' => 'Europa', 'surcharge_percent' => 20])
        ->assertJsonFragment(['code' => 'Asia', 'label' => 'Asia', 'surcharge_percent' => 25]);
});
