<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();
        $uuid = (string) Str::uuid();
        $slug = (Str::slug($name) ?: 'user').'-'.Str::substr($uuid, 0, 8);

        return [
            'uuid' => $uuid,
            'slug' => $slug,
            'username' => $slug,
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withPhone(string $phone = '612 345 678'): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => $phone,
        ]);
    }
}
