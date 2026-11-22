<?php

namespace Tests\Feature;

use App\Livewire\CollectionForm;
use App\Livewire\ProductManager;
use App\Livewire\PurchaseForm;
use App\Livewire\SaleForm;
use App\Livewire\SupplierPaymentForm;
use App\Livewire\TransactionManager;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression: clearing a number input sends '' to the server. Typed float
 * properties (public float $paid = 0) threw a TypeError during Livewire
 * hydration, surfacing as a 500 Server Error on the form. Every number
 * input bound to a typed float must coerce '' to 0 instead.
 */
class EmptyNumberInputTest extends TestCase
{
    use RefreshDatabase;

    private function makeShop(): array
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'টেস্ট দোকান',
            'opening_cash' => 1000,
            'plan' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);
        Business::seedCategories($business);

        return [$user, $business];
    }

    private function makeProduct(Business $business, array $overrides = []): Product
    {
        return $business->products()->create(array_merge([
            'name' => 'চিনি',
            'unit' => 'কেজি',
            'purchase_price' => 80,
            'sale_price' => 100,
            'stock' => 50,
            'low_stock_at' => 5,
        ], $overrides));
    }

    /** Fill a sale row totalling 300 (3 × 100). */
    private function saleWithRow(Business $business, Product $product)
    {
        return Livewire::test(SaleForm::class)
            ->set('rows.0.product_id', $product->id)
            ->set('rows.0.qty', 3);
    }

    public function test_sale_form_paid_empty_string_becomes_zero_and_due_stays_300(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $this->actingAs($user);

        $form = $this->saleWithRow($business, $product);
        $this->assertSame(300.0, $form->instance()->subtotal());

        $form->set('paid', '');
        $this->assertSame(0, $form->get('paid'));
        $this->assertSame(300.0, $form->instance()->due());
    }

    public function test_sale_form_discount_empty_string_becomes_zero_and_total_stays_300(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $this->actingAs($user);

        $form = $this->saleWithRow($business, $product)
            ->set('discount', '');
        $this->assertSame(0, $form->get('discount'));
        $this->assertSame(300.0, $form->instance()->total());
        $this->assertSame(300.0, $form->instance()->due());
    }

    public function test_sale_form_typing_5_paid_on_300_subtotal_gives_due_295(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $this->actingAs($user);

        // Simulate the owner's exact flow: clear the field, then type 5.
        $form = $this->saleWithRow($business, $product)
            ->set('paid', '')
            ->set('paid', 5);
        $this->assertSame(5.0, $form->get('paid'));
        $this->assertSame(295.0, $form->instance()->due());
    }

    public function test_purchase_form_paid_empty_string_becomes_zero(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $this->actingAs($user);

        $form = Livewire::test(PurchaseForm::class)
            ->set('rows.0.product_id', $product->id)
            ->set('rows.0.qty', 3)
            ->set('paid', '');
        $this->assertSame(0, $form->get('paid'));
        $this->assertSame(240.0, $form->instance()->total());
        $this->assertSame(240.0, $form->instance()->due());
    }

    public function test_collection_form_amount_empty_string_becomes_zero_and_still_saves(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $customer = $business->customers()->create(['name' => 'রহিম', 'phone' => '01700000000']);
        SaleService::createSale($business, [
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'paid' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash',
            'note' => null,
        ], [['product_id' => $product->id, 'qty' => 3, 'price' => 100]]);
        $this->actingAs($user);

        $form = Livewire::test(CollectionForm::class, ['customerId' => $customer->id])
            ->set('amount', '');
        $this->assertSame(0, $form->get('amount'));
        $this->assertSame(300.0, $form->instance()->due);

        $form->set('amount', 100)->call('save')
            ->assertRedirect(route('customers.show', $customer->id));
        $this->assertSame(200.0, $customer->fresh()->dueAmount());
    }

    public function test_supplier_payment_form_amount_empty_string_becomes_zero_and_still_saves(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $supplier = $business->suppliers()->create(['name' => 'সাপ্লায়ার', 'phone' => '01700000001']);
        PurchaseService::createPurchase($business, [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'paid' => 0,
            'note' => null,
        ], [['product_id' => $product->id, 'qty' => 3, 'price' => 80]]);
        $this->actingAs($user);

        $form = Livewire::test(SupplierPaymentForm::class, ['supplierId' => $supplier->id])
            ->set('amount', '');
        $this->assertSame(0, $form->get('amount'));
        $this->assertSame(240.0, $form->instance()->due);

        $form->set('amount', 40)->call('save')
            ->assertRedirect(route('suppliers.show', $supplier->id));
        $this->assertSame(200.0, $supplier->fresh()->dueAmount());
    }

    public function test_product_manager_number_fields_empty_string_become_zero_and_still_saves(): void
    {
        [$user, $business] = $this->makeShop();
        $this->actingAs($user);

        $manager = Livewire::test(ProductManager::class)
            ->set('purchase_price', '')
            ->set('sale_price', '')
            ->set('stock', '')
            ->set('low_stock_at', '');
        $this->assertSame(0, $manager->get('purchase_price'));
        $this->assertSame(0, $manager->get('sale_price'));
        $this->assertSame(0, $manager->get('stock'));
        $this->assertSame(0, $manager->get('low_stock_at'));

        $manager->set('name', 'চাল')
            ->set('purchase_price', 40)
            ->set('sale_price', 50)
            ->set('stock', 10)
            ->set('low_stock_at', 2)
            ->call('save')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('products', [
            'business_id' => $business->id, 'name' => 'চাল', 'sale_price' => 50,
        ]);
    }

    public function test_transaction_manager_amount_empty_string_becomes_zero_and_still_saves(): void
    {
        [$user, $business] = $this->makeShop();
        $this->actingAs($user);

        $manager = Livewire::test(TransactionManager::class)
            ->set('amount', '');
        $this->assertSame(0, $manager->get('amount'));

        $manager->set('amount', 500)->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('transactions', [
            'business_id' => $business->id, 'amount' => 500,
        ]);
    }
}
