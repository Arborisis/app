<?php

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\EchoTransaction;
use App\Models\User;

it('redirects guests from wallet page', function () {
    $response = $this->get('/wallet');

    $response->assertRedirect('/login');
});

it('creates a wallet for new users', function () {
    $user = User::factory()->create();

    expect($user->wallet)->not->toBeNull();
    expect((float) $user->wallet->balance)->toBe(0.0);
});

it('records wallet transactions immutably', function () {
    $user = User::factory()->create();

    EchoTransaction::create([
        'user_id' => $user->id,
        'type' => TransactionType::Purchase,
        'status' => TransactionStatus::Completed,
        'amount' => 10.00,
        'currency' => 'EUR',
        'echo_amount' => 1000,
    ]);

    expect(EchoTransaction::count())->toBe(1);
    expect((float) EchoTransaction::first()->echo_amount)->toBe(1000.0);
});
