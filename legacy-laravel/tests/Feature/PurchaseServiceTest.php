<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makePurchase(string $invoice, string $tanggal, int $total): Purchase
    {
        $supplier = Supplier::create(['name' => 'Supplier Tes', 'code' => 'SUP-'.uniqid(), 'phone' => '081234567890']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin-'.uniqid().'@stockku.com', 'password' => 'password']);

        return Purchase::create([
            'invoice_number' => $invoice,
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'tanggal' => $tanggal,
            'total' => $total,
            'status' => 'received',
        ]);
    }

    public function test_purchases_ordered_by_tanggal_desc(): void
    {
        $old = $this->makePurchase('INV-OLD-1', '2024-01-01 00:00:00', 10000);
        $new = $this->makePurchase('INV-NEW-1', '2026-08-20 00:00:00', 20000);

        $purchases = app(PurchaseService::class)->getPurchases();

        $this->assertSame($new->id, $purchases->first()->id);
        $this->assertSame($old->id, $purchases->last()->id);
    }

    public function test_purchases_with_same_tanggal_ordered_by_created_at_desc(): void
    {
        $first = $this->makePurchase('INV-SAME-1', '2026-08-20 00:00:00', 10000);
        $second = $this->makePurchase('INV-SAME-2', '2026-08-20 00:00:00', 20000);

        Purchase::query()->where('id', $first->id)->update(['created_at' => '2026-08-20 10:00:00']);
        Purchase::query()->where('id', $second->id)->update(['created_at' => '2026-08-20 10:00:01']);

        $purchases = app(PurchaseService::class)->getPurchases();

        $this->assertSame($second->id, $purchases->first()->id);
        $this->assertSame($first->id, $purchases->last()->id);
    }
}