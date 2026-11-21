<?php

namespace Tests\Feature;

use App\Livewire\CashCounter;
use App\Livewire\MoneyAccountManager;
use App\Models\Business;
use App\Models\MoneyAccount;
use App\Models\Product;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Support\Hisab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MoneyCapitalTest extends TestCase
{
    use RefreshDatabase;

    private function makeShop(array $overrides = []): array
    {
        $user = User::factory()->create($overrides);
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'টেস্ট দোকান',
            'type' => 'মুদি দোকান',
            'opening_cash' => 1000,
            'plan' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);
        Business::seedCategories($business);
        MoneyAccount::ensureRegister($business);

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

    public function test_register_with_recharge_type_seeds_recharge_categories(): void
    {
        $this->post('/register', [
            'name' => 'করিম',
            'email' => 'karim@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'করিম টেলিকম',
            'business_type' => 'মোবাইল রিচার্জ ও লোড',
        ])->assertRedirect(route('dashboard'));

        $business = Business::first();
        $this->assertSame('মোবাইল রিচার্জ ও লোড', $business->type);
        $this->assertDatabaseHas('categories', ['business_id' => $business->id, 'name' => 'রিচার্জ কমিশন', 'type' => 'income']);
        $this->assertDatabaseHas('categories', ['business_id' => $business->id, 'name' => 'সিম ব্যালেন্স কেনা', 'type' => 'expense']);
        // And the register account was auto-created.
        $this->assertDatabaseHas('money_accounts', ['business_id' => $business->id, 'type' => 'cash_register']);
    }

    public function test_register_with_grocery_type_has_no_recharge_categories(): void
    {
        $this->post('/register', [
            'name' => 'করিম',
            'email' => 'karim@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'করিম স্টোর',
            'business_type' => 'মুদি দোকান',
        ])->assertRedirect(route('dashboard'));

        $business = Business::first();
        $this->assertDatabaseMissing('categories', ['business_id' => $business->id, 'name' => 'রিচার্জ কমিশন']);
    }

    public function test_register_rejects_unknown_business_type(): void
    {
        $this->post('/register', [
            'name' => 'করিম',
            'email' => 'karim@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_type' => 'মহাকাশ দোকান',
        ])->assertSessionHasErrors('business_type');
    }

    public function test_business_type_changeable_in_settings_and_seeds_recharge_categories(): void
    {
        [$user, $business] = $this->makeShop();

        $this->actingAs($user)->put(route('settings.update'), [
            'name' => 'টেস্ট দোকান',
            'type' => 'মোবাইল রিচার্জ ও লোড',
            'opening_cash' => 1000,
        ])->assertRedirect();

        $this->assertSame('মোবাইল রিচার্জ ও লোড', $business->fresh()->type);
        $this->assertDatabaseHas('categories', ['business_id' => $business->id, 'name' => 'রিচার্জ কমিশন']);
    }

    public function test_money_account_crud_and_register_is_read_only_derived(): void
    {
        [$user, $business] = $this->makeShop();
        $this->actingAs($user);

        // Register account exists and shows derived cash (opening 1000).
        $register = $business->moneyAccounts()->where('type', 'cash_register')->first();
        $this->assertNotNull($register);
        $this->assertSame(1000.0, $register->displayBalance($business));

        // Create a bank account via the Livewire manager.
        Livewire::test(MoneyAccountManager::class)
            ->set('type', 'bank')
            ->set('provider', 'ডাচ-বাংলা ব্যাংক')
            ->set('label', 'সেভিংস অ্যাকাউন্ট')
            ->set('identifier', '123456')
            ->set('balance', 5000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('money_accounts', [
            'business_id' => $business->id, 'type' => 'bank',
            'label' => 'সেভিংস অ্যাকাউন্ট', 'balance' => 5000,
        ]);
        $this->assertSame(5000.0, Hisab::accountTotal($business, 'bank'));

        // Register account cannot be edited or deleted through the manager.
        Livewire::test(MoneyAccountManager::class)
            ->call('editAccount', $register->id)
            ->assertSet('editId', null);
        Livewire::test(MoneyAccountManager::class)
            ->call('delete', $register->id);
        $this->assertDatabaseHas('money_accounts', ['id' => $register->id]);

        // Money page shows the bank balance and the derived register cash.
        $this->get(route('money.index'))->assertOk()
            ->assertSee('সেভিংস অ্যাকাউন্ট')
            ->assertSee('৳5,000')
            ->assertSee('৳1,000');
    }

    public function test_cash_count_matches_system_cash_and_records_difference(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        SaleService::createSale($business, [
            'sale_date' => now()->toDateString(), 'paid' => 500,
        ], [['product_id' => $product->id, 'qty' => 5, 'price' => 100]]);
        // System cash: 1000 opening + 500 sale = 1500.
        $this->assertSame(1500.0, Hisab::cashBalance($business));
        $this->actingAs($user);

        // Exact count matches.
        Livewire::test(CashCounter::class)
            ->set('counts.1000', 1)
            ->set('counts.500', 1)
            ->call('save')
            ->assertHasNoErrors();
        $count = $business->cashCounts()->latest('id')->first();
        $this->assertSame(1500.0, (float) $count->total);
        $this->assertSame(1500.0, (float) $count->system_cash);
        $this->assertSame(0.0, (float) $count->difference);
        $this->assertTrue($count->isMatched());

        // Short count records a negative difference.
        Livewire::test(CashCounter::class)
            ->set('counts.1000', 1)
            ->call('save')
            ->assertHasNoErrors();
        $count = $business->cashCounts()->latest('id')->first();
        $this->assertSame(1000.0, (float) $count->total);
        $this->assertSame(-500.0, (float) $count->difference);
        $this->assertFalse($count->isMatched());

        $this->get(route('cash-count.index'))->assertOk()
            ->assertSee('মিলেছে')
            ->assertSee('৳500');
    }

    public function test_capital_page_totals(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business, ['stock' => 10, 'purchase_price' => 100]);
        $customer = $business->customers()->create(['name' => 'রহিম']);
        $supplier = $business->suppliers()->create(['name' => 'পাইকারি']);

        $business->moneyAccounts()->create([
            'type' => 'bank', 'provider' => 'সোনালী ব্যাংক',
            'label' => 'সেভিংস', 'balance' => 5000,
        ]);
        // Sale: total 1000, paid 400 → due 600. Purchase: total 2000, paid 500 → due 1500.
        SaleService::createSale($business, [
            'customer_id' => $customer->id, 'sale_date' => now()->toDateString(), 'paid' => 400,
        ], [['product_id' => $product->id, 'qty' => 10, 'price' => 100]]);
        PurchaseService::createPurchase($business, [
            'supplier_id' => $supplier->id, 'purchase_date' => now()->toDateString(), 'paid' => 500,
        ], [['product_id' => $product->id, 'qty' => 20, 'price' => 100]]);

        // Cash: 1000 + 400 − 500 = 900. Stock value: (10−10+20) × 100 = 2000.
        // Capital: 900 + 5000 + 0 + 0 + 0 + 2000 + 600 − 1500 = 7000.
        $capital = Hisab::capitalBreakdown($business);
        $this->assertSame(900.0, $capital['cash']);
        $this->assertSame(2000.0, $capital['stock_value']);
        $this->assertSame(600.0, $capital['customer_due']);
        $this->assertSame(1500.0, $capital['supplier_due']);
        $this->assertSame(7000.0, $capital['total']);

        $this->actingAs($user)->get(route('capital.index'))->assertOk()
            ->assertSee('মোট মূলধন')
            ->assertSee('৳7,000')
            ->assertSee('৳2,000');
    }

    public function test_new_pages_require_login(): void
    {
        $this->get(route('money.index'))->assertRedirect(route('login'));
        $this->get(route('cash-count.index'))->assertRedirect(route('login'));
        $this->get(route('capital.index'))->assertRedirect(route('login'));
    }

    public function test_tenant_isolation_for_money_accounts_and_cash_counts(): void
    {
        [$userA, $businessA] = $this->makeShop(['email' => 'a@example.com']);
        [$userB, $businessB] = $this->makeShop(['email' => 'b@example.com']);

        $businessA->moneyAccounts()->create([
            'type' => 'bank', 'provider' => 'সোনালী ব্যাংক',
            'label' => 'গোপন সেভিংস', 'balance' => 7777,
        ]);
        $businessA->cashCounts()->create([
            'counted_at' => now(), 'denominations' => ['1000' => 1],
            'total' => 1000, 'system_cash' => 1000, 'difference' => 0,
            'note' => 'গোপন গোনা',
        ]);

        $this->actingAs($userB)->get(route('money.index'))->assertOk()
            ->assertDontSee('গোপন সেভিংস')
            ->assertDontSee('৳7,777');
        $this->actingAs($userB)->get(route('cash-count.index'))->assertOk()
            ->assertDontSee('গোপন গোনা');
        $this->actingAs($userB)->get(route('capital.index'))->assertOk()
            ->assertDontSee('৳7,777');
    }

    public function test_dashboard_shows_quick_actions_and_checklist(): void
    {
        [$user, $business] = $this->makeShop();

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $response->assertSee('ক্যাশ মেলান');
        $response->assertSee('টাকার হিসাব');
        $response->assertSee('আমার মূলধন');
        $response->assertSee('তিন ধাপে দোকান চালু করুন');
        $response->assertSee('মাল যোগ করুন');
    }

    public function test_sale_save_and_add_another_stays_on_page(): void
    {
        [$user, $business] = $this->makeShop();
        $product = $this->makeProduct($business);
        $this->actingAs($user);

        Livewire::test(\App\Livewire\SaleForm::class)
            ->set('rows.0.product_id', $product->id)
            ->set('rows.0.qty', 2)
            ->set('paid', 200)
            ->call('saveAndAddAnother')
            ->assertHasNoErrors()
            ->assertSet('paid', 0.0)
            ->assertSet('rows.0.product_id', '');

        $this->assertSame(1, $business->sales()->count());
        $this->assertSame(200.0, (float) $business->sales()->first()->total);
    }
}
