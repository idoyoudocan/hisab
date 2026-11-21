<?php

namespace Tests\Feature;

use App\Livewire\SaleForm;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Support\Hisab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HisabTest extends TestCase
{
    use RefreshDatabase;

    private function makeShop(array $overrides = []): array
    {
        $user = User::factory()->create($overrides);
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

    public function test_register_creates_a_business_with_trial_and_categories(): void
    {
        $response = $this->post('/register', [
            'name' => 'করিম',
            'email' => 'karim@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'করিম স্টোর',
            'business_type' => 'মুদি দোকান',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $business = Business::where('name', 'করিম স্টোর')->firstOrFail();
        $this->assertSame('trial', $business->plan);
        $this->assertTrue($business->trial_ends_at->isFuture());
        $this->assertSame(0.0, (float) $business->opening_cash);
        $this->assertTrue($business->categories()->where('name', 'বিক্রয়')->exists());
        $this->assertTrue($business->categories()->where('name', 'ব্যক্তিগত')->exists());
    }

    public function test_sale_decrements_stock_and_creates_due(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $customer = $business->customers()->create(['name' => 'রহিম', 'phone' => '01700000000']);

        $sale = SaleService::createSale($business, [
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'paid' => 150,
            'payment_method' => 'cash',
        ], [
            ['product_id' => $product->id, 'qty' => 2, 'price' => 100],
        ]);

        $this->assertSame(200.0, (float) $sale->total);
        $this->assertSame(50.0, $sale->dueAmount());
        $this->assertSame(48.0, (float) $product->fresh()->stock);
        $this->assertSame(50.0, $customer->fresh()->dueAmount());
    }

    public function test_collecting_payment_reduces_customer_due(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $customer = $business->customers()->create(['name' => 'রহিম']);

        SaleService::createSale($business, [
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'paid' => 100,
        ], [
            ['product_id' => $product->id, 'qty' => 2, 'price' => 100],
        ]);
        $this->assertSame(100.0, $customer->fresh()->dueAmount());

        PaymentService::record($business, 'customer', $customer->id, ['amount' => 60]);

        $this->assertSame(40.0, $customer->fresh()->dueAmount());
    }

    public function test_purchase_increments_stock(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business, ['stock' => 10]);
        $supplier = $business->suppliers()->create(['name' => 'পাইকারি বাজার']);

        $purchase = PurchaseService::createPurchase($business, [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'paid' => 400,
        ], [
            ['product_id' => $product->id, 'qty' => 5, 'price' => 80],
        ]);

        $this->assertSame(400.0, (float) $purchase->total);
        $this->assertSame(15.0, (float) $product->fresh()->stock);
        $this->assertSame(0.0, $supplier->fresh()->dueAmount());
    }

    public function test_tenant_isolation_user_b_cannot_access_user_a_sale_or_customer(): void
    {
        [$userA, $businessA] = $this->makeShop(['email' => 'a@example.com']);
        [$userB, $businessB] = $this->makeShop(['email' => 'b@example.com']);
        $product = $this->makeProduct($businessA);
        $customer = $businessA->customers()->create(['name' => 'রহিম']);
        $sale = SaleService::createSale($businessA, [
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'paid' => 100,
        ], [
            ['product_id' => $product->id, 'qty' => 1, 'price' => 100],
        ]);

        // User B must not see A's sale or customer.
        $this->actingAs($userB)->get(route('sales.show', $sale->id))->assertNotFound();
        $this->actingAs($userB)->get(route('customers.show', $customer->id))->assertNotFound();

        // And B's sales list must be empty.
        $this->actingAs($userB)->get(route('sales.index'))
            ->assertOk()
            ->assertDontSee('বিক্রি #'.$sale->id);
    }

    public function test_dashboard_shows_todays_numbers(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);

        SaleService::createSale($business, [
            'sale_date' => now()->toDateString(),
            'paid' => 200,
        ], [
            ['product_id' => $product->id, 'qty' => 2, 'price' => 100],
        ]);
        $business->transactions()->create([
            'type' => 'expense', 'category' => 'যাতায়াত', 'amount' => 50,
            'date' => now()->toDateString(), 'method' => 'cash', 'scope' => 'shop',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('৳200'); // today's sale total
        $response->assertSee('৳50'); // today's expense
        $response->assertSee('৳1,150'); // cash: 1000 opening + 200 sale − 50 expense
        $response->assertSee('টেস্ট দোকান');
    }

    public function test_cash_balance_is_derived_not_stored(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $customer = $business->customers()->create(['name' => 'রহিম']);
        $supplier = $business->suppliers()->create(['name' => 'পাইকারি']);

        SaleService::createSale($business, [
            'customer_id' => $customer->id, 'sale_date' => now()->toDateString(), 'paid' => 150,
        ], [['product_id' => $product->id, 'qty' => 2, 'price' => 100]]);
        PaymentService::record($business, 'customer', $customer->id, ['amount' => 50]);
        PurchaseService::createPurchase($business, [
            'supplier_id' => $supplier->id, 'purchase_date' => now()->toDateString(), 'paid' => 80,
        ], [['product_id' => $product->id, 'qty' => 1, 'price' => 80]]);

        // 1000 opening + 150 sale + 50 collection − 80 purchase = 1120
        $this->assertSame(1120.0, Hisab::cashBalance($business));
    }

    public function test_sale_form_component_creates_a_sale(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $this->actingAs($user);

        Livewire::test(SaleForm::class)
            ->set('rows.0.product_id', $product->id)
            ->set('rows.0.qty', 3)
            ->set('paid', 250)
            ->call('save')
            ->assertRedirect(route('sales.show', 1));

        $this->assertSame(47.0, (float) $product->fresh()->stock);
        $this->assertSame(300.0, (float) $business->sales()->first()->total);
    }

    public function test_deploy_migrate_route_returns_404_without_token(): void
    {
        config(['app.env' => 'testing']);
        $this->post('/deploy/migrate')->assertNotFound();
    }

    public function test_landing_and_login_pages_load(): void
    {
        $this->get('/')->assertOk()->assertSee('হিসাব');
        $this->get('/login')->assertOk()->assertSee('লগইন');
        $this->get('/register')->assertOk()->assertSee('ফ্রি অ্যাকাউন্ট');
    }
}
