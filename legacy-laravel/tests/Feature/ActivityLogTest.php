<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $role): User
    {
        Role::findOrCreate($role);

        $user = User::create([
            'name' => ucfirst($role).' Log',
            'email' => $role.'-log-'.uniqid().'@stockku.com',
            'password' => 'password',
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeProduct(int $stok = 10, float $hargaJual = 5000): Product
    {
        $category = Category::create(['name' => 'Kategori Log', 'slug' => 'kategori-log-'.uniqid()]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Log',
            'sku' => 'LOG-'.uniqid(),
            'harga_beli' => 3000,
            'harga_jual' => $hargaJual,
            'stok' => $stok,
            'min_stok' => 2,
            'satuan' => 'pcs',
            'is_active' => true,
        ]);
    }

    public function test_logger_records_user_role_and_ip(): void
    {
        $user = $this->createUserWithRole('kasir');
        $this->actingAs($user);

        app(ActivityLogger::class)->log('test.action', 'Deskripsi uji.');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'role' => 'kasir',
            'action' => 'test.action',
            'description' => 'Deskripsi uji.',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_login_success_is_logged(): void
    {
        $user = $this->createUserWithRole('admin');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
        ]);
    }

    public function test_failed_login_is_logged_without_user(): void
    {
        $user = $this->createUserWithRole('karyawan');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'salah-password',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => null,
            'role' => null,
            'action' => 'auth.login_failed',
            'description' => 'Percobaan login gagal untuk email: '.$user->email,
        ]);
    }

    public function test_sale_creation_is_logged(): void
    {
        $this->actingAs($this->createUserWithRole('kasir'));
        $product = $this->makeProduct();

        app(SaleService::class)->createSale(
            [['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]],
            0,
            10000
        );

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'sale.create',
        ]);
        $this->assertStringContainsString('Penjualan INV-', ActivityLog::latest('id')->first()->description);
    }

    public function test_prune_keeps_newest_500(): void
    {
        for ($i = 1; $i <= 520; $i++) {
            ActivityLog::create([
                'action' => 'test.bulk',
                'description' => 'Entri ke-'.$i,
            ]);
        }

        app(ActivityLogger::class)->prune();

        $this->assertSame(500, ActivityLog::count());
        $this->assertDatabaseMissing('activity_logs', ['description' => 'Entri ke-1']);
        $this->assertDatabaseHas('activity_logs', ['description' => 'Entri ke-520']);
    }

    public function test_logging_trims_automatically_when_exceeding_limit(): void
    {
        for ($i = 1; $i <= 500; $i++) {
            ActivityLog::create([
                'action' => 'test.bulk',
                'description' => 'Entri ke-'.$i,
            ]);
        }

        app(ActivityLogger::class)->log('test.after', 'Entri terakhir.');

        $this->assertSame(500, ActivityLog::count());
        $this->assertDatabaseMissing('activity_logs', ['description' => 'Entri ke-1']);
        $this->assertDatabaseHas('activity_logs', ['description' => 'Entri terakhir.']);
    }

    public function test_admin_can_view_activity_logs(): void
    {
        $this->actingAs($this->createUserWithRole('admin'));

        $response = $this->get(route('activity-logs.index'));

        $response->assertOk();
        $response->assertSee('Log Aktivitas');
    }

    public function test_non_admin_cannot_view_activity_logs(): void
    {
        $this->actingAs($this->createUserWithRole('kasir'));

        $this->get(route('activity-logs.index'))->assertForbidden();

    }

    public function test_filter_by_action_and_user(): void
    {
        $admin = $this->createUserWithRole('admin');
        $kasir = $this->createUserWithRole('kasir');

        app(ActivityLogger::class)->log('auth.login', 'Login admin.', $admin);
        app(ActivityLogger::class)->log('auth.login', 'Login kasir.', $kasir);
        app(ActivityLogger::class)->log('auth.logout', 'Logout kasir.', $kasir);

        $this->actingAs($admin);

        $this->get(route('activity-logs.index', ['action' => 'auth.login']))
            ->assertSee('Login admin.')
            ->assertSee('Login kasir.')
            ->assertDontSee('Logout kasir.');

        $this->get(route('activity-logs.index', ['user_id' => $kasir->id]))
            ->assertSee('Login kasir.')
            ->assertSee('Logout kasir.')
            ->assertDontSee('Login admin.');
    }
}
