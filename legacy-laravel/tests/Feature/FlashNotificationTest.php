<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FlashNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_flash_modal_renders_with_safe_close_handler(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin'));

        $response = $this->actingAs($user)
            ->withSession(['success' => 'Data berhasil disimpan.'])
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('flash-overlay', false)
            ->assertSee('flash-modal', false)
            ->assertSee('closeFlashModal(this)', false)
            ->assertSee('Data berhasil disimpan.');
    }

    public function test_no_overlay_rendered_without_flash(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('flash-overlay', false);
    }
}
