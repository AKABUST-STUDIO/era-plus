<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Propaganistas\LaravelPhone\PhoneNumber;
use Tests\TestCase;

class UserPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_phone_attribute_is_cast_to_a_phone_number_object(): void
    {
        $user = User::factory()->withPhone()->create();

        $this->assertInstanceOf(PhoneNumber::class, $user->fresh()->phone);
    }

    public function test_the_raw_number_is_stored_exactly_as_entered(): void
    {
        $user = User::factory()->withPhone('612 345 678')->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '612 345 678',
        ]);
    }

    public function test_a_national_number_is_parsed_against_the_default_country(): void
    {
        $user = User::factory()->withPhone('612 345 678')->create()->fresh();

        $this->assertSame('+34612345678', $user->phone->formatE164());
        $this->assertSame('ES', $user->phone->getCountry());
    }

    public function test_an_international_number_keeps_its_own_country(): void
    {
        $user = User::factory()->withPhone('+32 12 34 56 78')->create()->fresh();

        $this->assertSame('BE', $user->phone->getCountry());
        $this->assertSame('+3212345678', $user->phone->formatE164());
    }

    public function test_a_null_phone_stays_null(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->fresh()->phone);
    }

    public function test_a_number_valid_in_neither_form_throws_on_read(): void
    {
        $user = User::factory()->withPhone('12345')->create();

        $this->expectException(InvalidArgumentException::class);

        $user->fresh()->phone;
    }

    public function test_assigning_a_phone_number_object_stores_its_raw_value(): void
    {
        $user = User::factory()->create();

        $user->update(['phone' => new PhoneNumber('612 345 678', 'ES')]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '612 345 678',
        ]);
    }

    public function test_the_phone_serializes_to_its_raw_value(): void
    {
        $user = User::factory()->withPhone('612 345 678')->create()->fresh();

        $this->assertSame('612 345 678', $user->toArray()['phone']);
    }
}
