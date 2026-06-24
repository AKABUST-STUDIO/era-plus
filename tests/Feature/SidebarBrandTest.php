<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_wrapper_view_renders(): void
    {
        $this->actingAs(User::factory()->create());

        $html = view('livewire.sidebar-brand-wrapper')->render();

        $this->assertStringContainsString('sidebar-brand', $html);
    }

    public function test_panel_labels_are_translatable(): void
    {
        $this->assertSame('Workspace', __('sidebar.panel.organization'));
        $this->assertSame('Settings', __('sidebar.panel.settings'));
        $this->assertSame('Project', __('sidebar.panel.project'));
        $this->assertSame('Account', __('sidebar.panel.user'));
    }
}
