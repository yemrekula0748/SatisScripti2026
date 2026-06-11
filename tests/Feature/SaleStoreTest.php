<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SaleStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_payment_sale_can_be_completed_and_is_visible_in_history(): void
    {
        $this->withoutMiddleware();
        Gate::before(static fn () => true);

        $company = Company::create([
            'name' => 'Demo Company',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('sales.store'), [
            'discount_percent' => 0,
            'items' => [
                [
                    'product_id' => null,
                    'product_name' => 'Test Product',
                    'product_barcode' => null,
                    'unit_price' => 100,
                    'quantity' => 1,
                    'unit' => 'adet',
                ],
            ],
            'payments' => [
                [
                    'payment_type' => 'TL',
                    'amount' => 97,
                    'exchange_rate' => 1,
                ],
            ],
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
        ]);

        $sale = Sale::query()->with('payments')->sole();

        $this->assertEquals(100.0, (float) $sale->total);
        $this->assertCount(1, $sale->payments);
        $this->assertEquals(97.0, (float) $sale->payments->first()->amount_in_tl);

        $historyResponse = $this->actingAs($user)->get(route('sales.history'));

        $historyResponse->assertOk();
        $historyResponse->assertSeeText('Eksik Tahsilat');
        $historyResponse->assertSeeText('3,00');
    }

    public function test_sale_can_be_completed_without_any_payment_rows(): void
    {
        $this->withoutMiddleware();
        Gate::before(static fn () => true);

        $company = Company::create([
            'name' => 'Demo Company',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('sales.store'), [
            'discount_percent' => 0,
            'items' => [
                [
                    'product_id' => null,
                    'product_name' => 'No Payment Product',
                    'product_barcode' => null,
                    'unit_price' => 45,
                    'quantity' => 1,
                    'unit' => 'adet',
                ],
            ],
            'payments' => [],
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
        ]);

        $sale = Sale::query()->with('payments')->sole();

        $this->assertEquals(45.0, (float) $sale->total);
        $this->assertCount(0, $sale->payments);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_payments', 0);
    }
}
