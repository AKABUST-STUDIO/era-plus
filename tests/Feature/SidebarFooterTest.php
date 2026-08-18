<?php

namespace Tests\Feature;

use App\Livewire\UserFooter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SidebarFooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_footer_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Maria', 'email' => 'maria@example.test']);
        $this->actingAs($user);

        Livewire::test(UserFooter::class)
            ->assertSee('Maria')
            ->assertSee('maria@example.test');
    }
}
