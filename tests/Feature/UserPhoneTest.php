<?php

use App\Models\User;
use Propaganistas\LaravelPhone\PhoneNumber;

test('the phone attribute is cast to a phone number object', function (): void {
    $user = User::factory()->withPhone()->create();

    $this->assertInstanceOf(PhoneNumber::class, $user->fresh()->phone);
});

test('the raw number is stored exactly as entered', function (): void {
    $user = User::factory()->withPhone('612 345 678')->create();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'phone' => '612 345 678',
    ]);
});

test('a national number is parsed against the default country', function (): void {
    $user = User::factory()->withPhone('612 345 678')->create()->fresh();

    $this->assertSame('+34612345678', $user->phone->formatE164());
    $this->assertSame('ES', $user->phone->getCountry());
});

test('an international number keeps its own country', function (): void {
    $user = User::factory()->withPhone('+32 12 34 56 78')->create()->fresh();

    $this->assertSame('BE', $user->phone->getCountry());
    $this->assertSame('+3212345678', $user->phone->formatE164());
});

test('a null phone stays null', function (): void {
    $user = User::factory()->create();

    $this->assertNull($user->fresh()->phone);
});

test('a number valid in neither form throws on read', function (): void {
    $this->expectException(InvalidArgumentException::class);

    User::factory()->withPhone('12345')->create();
});

test('assigning a phone number object stores its raw value', function (): void {
    $user = User::factory()->create();

    $user->update(['phone' => new PhoneNumber('612 345 678', 'ES')]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'phone' => '612 345 678',
    ]);
});

test('the phone serializes to its raw value', function (): void {
    $user = User::factory()->withPhone('612 345 678')->create()->fresh();

    $this->assertSame('612 345 678', $user->toArray()['phone']);
});
