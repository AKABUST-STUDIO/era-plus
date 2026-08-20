<?php

use App\Livewire\UserFooter;
use App\Models\User;
use Livewire\Livewire;

test('user footer component renders for authenticated user', function (): void {
    $user = User::factory()->create(['name' => 'Maria', 'email' => 'maria@example.test']);
    $this->actingAs($user);

    Livewire::test(UserFooter::class)
        ->assertSee('Maria')
        ->assertSee('maria@example.test');
});
