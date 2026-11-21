<?php

namespace Tests\Feature;

use App\Livewire\CustomerManager;
use App\Livewire\SaleForm;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PremiumFeaturesTest extends TestCase
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

    private function makeSale(Business $business, Product $product, array $data = [], array $items = []): \App\Models\Sale
    {
        return SaleService::createSale($business, array_merge([
            'sale_date' => now()->toDateString(),
            'paid' => 0,
        ], $data), $items ?: [
            ['product_id' => $product->id, 'qty' => 2, 'price' => 100],
        ]);
    }

    public function test_discount_reduces_total_due_and_profit(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);

        $sale = $this->makeSale($business, $product, ['paid' => 100, 'discount_amount' => 50]);

        $this->assertSame(200.0, $sale->subtotalAmount());
        $this->assertSame(50.0, (float) $sale->discount_amount);
        $this->assertSame(150.0, (float) $sale->total);
        $this->assertSame(50.0, $sale->dueAmount());
        // Profit after discount: total 150 − COGS 160 = −10
        $this->assertSame(-10.0, $sale->profitAmount());
    }

    public function test_discount_is_clamped_to_subtotal(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);

        $sale = $this->makeSale($business, $product, ['paid' => 0, 'discount_amount' => 500]);

        $this->assertSame(200.0, (float) $sale->discount_amount);
        $this->assertSame(0.0, (float) $sale->total);
    }

    public function test_sale_form_warns_when_credit_limit_would_be_exceeded(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $customer = $business->customers()->create([
            'name' => 'রহিম', 'phone' => '01700000000', 'credit_limit' => 100,
        ]);
        // Existing due of 80 (total 200, paid 120).
        $this->makeSale($business, $product, ['customer_id' => $customer->id, 'paid' => 120]);

        $this->actingAs($user);
        Livewire::test(SaleForm::class)
            ->assertSee('ছাড়')
            ->assertSee('উপমোট')
            ->set('customer_id', (string) $customer->id)
            ->set('rows.0.product_id', $product->id)
            ->set('rows.0.qty', 1)
            ->set('paid', 50)
            ->assertSee('সতর্কতা')
            ->assertSee('ক্রেডিট সীমা')
            // The warning does not block the sale; saving pushes due to 130 > 100.
            ->call('save')
            ->assertRedirect();

        $this->assertSame(130.0, $customer->fresh()->dueAmount());

        // Over-limit flag shows on the customer list.
        $this->get(route('customers.index'))->assertOk()->assertSee('সীমা ছাড়িয়েছে');
    }

    public function test_credit_limit_can_be_saved_and_edited(): void
    {
        [$user, $business] = $this->makeShop();
        $this->actingAs($user);

        Livewire::test(CustomerManager::class)
            ->set('name', 'করিম')
            ->set('phone', '01800000000')
            ->set('credit_limit', '5000')
            ->call('save');

        $customer = Customer::where('name', 'করিম')->firstOrFail();
        $this->assertSame(5000.0, (float) $customer->credit_limit);

        Livewire::test(CustomerManager::class)
            ->call('editCustomer', $customer->id)
            ->set('credit_limit', '8000')
            ->call('save');

        $this->assertSame(8000.0, (float) $customer->fresh()->credit_limit);
    }

    public function test_sales_csv_export_has_bom_and_bangla_headers(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $customer = $business->customers()->create(['name' => 'রহিম']);
        $this->makeSale($business, $product, ['customer_id' => $customer->id, 'paid' => 150, 'discount_amount' => 20]);

        $response = $this->actingAs($user)->get(route('sales.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('তারিখ', $content);
        $this->assertStringContainsString('রহিম', $content);
        $this->assertStringContainsString('180', $content); // total after discount
    }

    public function test_transactions_csv_export_and_customer_ledger_export(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $customer = $business->customers()->create(['name' => 'রহিম']);
        $this->makeSale($business, $product, ['customer_id' => $customer->id, 'paid' => 100]);
        $business->transactions()->create([
            'type' => 'expense', 'category' => 'যাতায়াত', 'amount' => 60,
            'date' => now()->toDateString(), 'method' => 'cash', 'scope' => 'shop',
        ]);

        $tx = $this->actingAs($user)->get(route('transactions.export', ['month' => now()->format('Y-m')]));
        $tx->assertOk();
        $this->assertStringContainsString('যাতায়াত', $tx->streamedContent());

        $ledger = $this->actingAs($user)->get(route('customers.export', $customer));
        $ledger->assertOk();
        $this->assertStringContainsString('বিক্রি', $ledger->streamedContent());
        $this->assertStringContainsString('মোট বাকি', $ledger->streamedContent());
    }

    public function test_customer_ledger_export_is_tenant_scoped(): void
    {
        [$userA, $businessA] = $this->makeShop(['email' => 'a@example.com']);
        [$userB, $businessB] = $this->makeShop(['email' => 'b@example.com']);
        $customer = $businessA->customers()->create(['name' => 'রহিম']);

        $this->actingAs($userB)->get(route('customers.export', $customer))->assertNotFound();
    }

    public function test_day_close_numbers(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $customer = $business->customers()->create(['name' => 'রহিম']);
        $this->makeSale($business, $product, ['customer_id' => $customer->id, 'paid' => 150]);
        PaymentService::record($business, 'customer', $customer->id, ['amount' => 50]);
        $business->transactions()->create([
            'type' => 'expense', 'category' => 'যাতায়াত', 'amount' => 30,
            'date' => now()->toDateString(), 'method' => 'cash', 'scope' => 'shop',
        ]);

        // Opening 1000 + sale paid 150 + collection 50 − expense 30 = 1170.
        // (No purchases today; supplier payments 0.)
        $response = $this->actingAs($user)->get(route('day-close.index'));

        $response->assertOk();
        $response->assertSee('দিন শেষে ক্যাশ');
        $response->assertSee('৳1,170');
        $response->assertSee('৳200'); // total sales
        $response->assertSee('৳50'); // due + collection lines contain 50
    }

    public function test_profit_and_loss_numbers(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $this->makeSale($business, $product, ['paid' => 200]); // revenue 200, COGS 160
        $business->transactions()->create([
            'type' => 'expense', 'category' => 'দোকান ভাড়া', 'amount' => 50,
            'date' => now()->toDateString(), 'method' => 'cash', 'scope' => 'shop',
        ]);
        $business->transactions()->create([
            'type' => 'expense', 'category' => 'ব্যক্তিগত', 'amount' => 500,
            'date' => now()->toDateString(), 'method' => 'cash', 'scope' => 'personal',
        ]);

        $response = $this->actingAs($user)
            ->get(route('profit-loss.index', ['month' => now()->format('Y-m')]));

        $response->assertOk();
        $response->assertSee('৳200'); // revenue
        $response->assertSee('৳160'); // COGS
        $response->assertSee('৳40'); // gross profit
        $response->assertSee('৳-10'); // net profit 40 − 50
        $response->assertSee('৳500'); // personal expense shown separately
    }

    public function test_whatsapp_reminder_and_receipt_links(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $customer = $business->customers()->create(['name' => 'রহিম', 'phone' => '01700000000']);
        $sale = $this->makeSale($business, $product, ['customer_id' => $customer->id, 'paid' => 100]);

        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('WhatsApp-এ তাগাদা')
            ->assertSee('wa.me/8801700000000', false);

        $this->actingAs($user)->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee('WhatsApp-এ রসিদ')
            ->assertSee('wa.me', false);
    }

    public function test_due_aging_shows_oldest_unpaid_days(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $customer = $business->customers()->create(['name' => 'রহিম', 'phone' => '01700000000']);
        $this->makeSale($business, $product, [
            'customer_id' => $customer->id,
            'sale_date' => now()->subDays(40)->toDateString(),
            'paid' => 0,
        ]);

        $this->assertSame(40, $customer->fresh()->oldestDueDays());

        $this->actingAs($user)->get(route('customers.index', ['sort' => 'age']))
            ->assertOk()
            ->assertSee('40 দিনের বাকি');
    }

    public function test_dashboard_shows_insight_and_low_stock(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business, ['stock' => 2, 'low_stock_at' => 5]);
        $this->makeSale($business, $product, ['paid' => 200]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('আজকের সারাংশ')
            ->assertSee('চিনি')
            ->assertSee('কম স্টক')
            ->assertSee('কিনুন');
    }

    public function test_new_pages_load(): void
    {
        [$user, $business] = $this->makeShop();

        $this->actingAs($user)->get(route('day-close.index'))->assertOk();
        $this->actingAs($user)->get(route('profit-loss.index'))->assertOk();
        $this->actingAs($user)->get(route('sales.index'))->assertOk();
        $this->actingAs($user)->get(route('customers.index'))->assertOk();
    }
}
