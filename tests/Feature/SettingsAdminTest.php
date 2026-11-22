<?php

namespace Tests\Feature;

use App\Livewire\CashCounter;
use App\Livewire\MoneyAccountManager;
use App\Models\Business;
use App\Models\MoneyAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    private function makeShop(array $userOverrides = [], array $businessOverrides = []): array
    {
        $user = User::factory()->create($userOverrides);
        $business = Business::create(array_merge([
            'user_id' => $user->id,
            'name' => 'টেস্ট দোকান',
            'type' => 'মুদি দোকান',
            'opening_cash' => 1000,
            'plan' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ], $businessOverrides));
        Business::seedCategories($business);
        MoneyAccount::ensureRegister($business);

        return [$user, $business];
    }

    private function settingsPayload(array $overrides = []): array
    {
        $modules = [];
        foreach (array_keys(Business::MODULES) as $key) {
            $modules[$key] = '1';
        }

        return array_merge([
            'owner_name' => 'করিম উদ্দিন',
            'name' => 'করিম স্টোর',
            'type' => 'মুদি দোকান',
            'phone' => '01711111111',
            'address' => 'ঢাকা',
            'opening_cash' => 1000,
            'modules' => $modules,
        ], $overrides);
    }

    public function test_settings_profile_update_and_type_change(): void
    {
        [$user, $business] = $this->makeShop();
        $this->actingAs($user);

        $this->put('/settings', $this->settingsPayload([
            'owner_name' => 'রহিম উদ্দিন',
            'name' => 'রহিম টেলিকম',
            'type' => 'মোবাইল রিচার্জ ও লোড',
        ]))->assertRedirect();

        $this->assertSame('রহিম উদ্দিন', $user->fresh()->name);
        $this->assertSame('রহিম টেলিকম', $business->fresh()->name);
        $this->assertSame('মোবাইল রিচার্জ ও লোড', $business->fresh()->type);
        // Changing to the recharge type seeds its extra categories.
        $this->assertDatabaseHas('categories', ['business_id' => $business->id, 'name' => 'রিচার্জ কমিশন']);
        $this->assertDatabaseHas('categories', ['business_id' => $business->id, 'name' => 'সিম ব্যালেন্স কেনা']);
    }

    public function test_module_toggle_hides_dashboard_action_and_shows_notice_on_page(): void
    {
        [$user, $business] = $this->makeShop();
        $this->actingAs($user);

        // Turn cash_count off (leave the rest on).
        $payload = $this->settingsPayload();
        unset($payload['modules']['cash_count']);
        $this->put('/settings', $payload)->assertRedirect();

        $this->assertFalse($business->fresh()->moduleEnabled('cash_count'));
        $this->assertTrue($business->fresh()->moduleEnabled('capital'));

        // Dashboard no longer offers the cash-count quick action.
        $this->get('/dashboard')->assertOk()->assertDontSee('ক্যাশ মেলান', false);
        // Opening the module page shows the friendly notice, not a 404.
        $this->get('/cash-count')->assertOk()
            ->assertSee('ক্যাশ মেলান বন্ধ আছে')
            ->assertSee('সেটিংসে যান');
        // Other modules still work.
        $this->get('/capital')->assertOk();
    }

    public function test_modal_cash_count_saves_and_reports_match(): void
    {
        [$user, $business] = $this->makeShop();
        $this->actingAs($user);

        // System cash is 1000 (opening cash, no movements) — count exactly that.
        Livewire::test(CashCounter::class)
            ->set('showModal', true)
            ->set('counts.1000', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('lastResult', 'ক্যাশ মিলেছে ✓ — হিসাবের সাথে গোনা টাকা পুরোপুরি মিলেছে।');

        $this->assertDatabaseHas('cash_counts', [
            'business_id' => $business->id,
            'total' => 1000,
            'system_cash' => 1000,
            'difference' => 0,
        ]);
    }

    public function test_money_account_add_and_edit_from_settings(): void
    {
        [$user, $business] = $this->makeShop();
        $this->actingAs($user);

        // The settings page embeds the account manager.
        $this->get('/settings')->assertOk()->assertSee('আমার অ্যাকাউন্ট');

        Livewire::test(MoneyAccountManager::class)
            ->call('openAdd', 'sim')
            ->assertSet('showFormModal', true)
            ->assertSet('type', 'sim')
            ->set('provider', 'গ্রামীণফোন')
            ->set('label', 'জিপি রিচার্জ')
            ->set('identifier', '01722222222')
            ->set('balance', '2500')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $account = MoneyAccount::where('business_id', $business->id)->where('type', 'sim')->first();
        $this->assertNotNull($account);
        $this->assertSame('গ্রামীণফোন', $account->provider);
        $this->assertSame(2500.0, (float) $account->balance);

        // Edit the balance through the same modal flow.
        Livewire::test(MoneyAccountManager::class)
            ->call('editAccount', $account->id)
            ->assertSet('showFormModal', true)
            ->set('balance', '3000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3000.0, (float) $account->fresh()->balance);
    }

    public function test_money_page_hides_empty_type_sections(): void
    {
        [$user, $business] = $this->makeShop();
        $this->actingAs($user);

        // No bank account yet: no bank card, only the compact add affordance.
        $this->get('/money-accounts')->assertOk()
            ->assertSee('ব্যাংক')
            ->assertDontSee('ব্যাংক — মোট');

        $business->moneyAccounts()->create([
            'type' => 'bank', 'provider' => 'সোনালী ব্যাংক',
            'label' => 'সোনালী সেভিংস', 'balance' => 5000,
        ]);
        $this->get('/money-accounts')->assertOk()->assertSee('ব্যাংক — মোট');
    }

    public function test_admin_forbidden_for_shopkeeper_and_guest(): void
    {
        Config::set('hisab.admin_emails', ['owner@example.com']);

        // Guest is redirected to login.
        $this->get('/admin')->assertRedirect('/login');

        // A logged-in shopkeeper (not in the admin list) gets 403.
        [$user] = $this->makeShop(['email' => 'shop@example.com']);
        $this->actingAs($user);
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/businesses')->assertForbidden();
    }

    public function test_admin_dashboard_lists_businesses_for_config_admin(): void
    {
        [$user, $business] = $this->makeShop(['email' => 'owner@example.com']);
        Config::set('hisab.admin_emails', ['owner@example.com']);
        $this->actingAs($user);

        $this->get('/admin')->assertOk()
            ->assertSee('অ্যাডমিন ড্যাশবোর্ড')
            ->assertSee('টেস্ট দোকান');
        $this->get('/admin/businesses')->assertOk()->assertSee('টেস্ট দোকান');
        $this->get('/admin/businesses/'.$business->id)->assertOk()->assertSee('টেস্ট দোকান');
    }

    public function test_admin_search_and_status_filter(): void
    {
        [$owner] = $this->makeShop(['email' => 'owner@example.com', 'name' => 'মালিক']);
        $this->makeShop(['name' => 'সুমন'], ['name' => 'সুমন স্টোর', 'plan' => 'trial', 'trial_ends_at' => now()->addDays(5)]);
        $this->makeShop(['name' => 'জামাল'], ['name' => 'জামাল স্টোর', 'plan' => 'pro', 'paid_until' => now()->addMonths(2)]);
        Config::set('hisab.admin_emails', ['owner@example.com']);
        $this->actingAs($owner);

        $this->get('/admin/businesses?q=সুমন')->assertOk()
            ->assertSee('সুমন স্টোর')->assertDontSee('জামাল স্টোর');
        $this->get('/admin/businesses?status=paid')->assertOk()
            ->assertSee('জামাল স্টোর')->assertDontSee('সুমন স্টোর');
        $this->get('/admin/businesses?status=trial')->assertOk()
            ->assertSee('সুমন স্টোর')->assertDontSee('জামাল স্টোর');
    }

    public function test_admin_extend_from_active_paid_until(): void
    {
        [$owner] = $this->makeShop(['email' => 'owner@example.com', 'name' => 'মালিক']);
        [, $biz] = $this->makeShop(['name' => 'পেইড দোকান'], [
            'name' => 'পেইড দোকান', 'plan' => 'pro',
            'paid_until' => now()->addDays(10)->toDateString(),
        ]);
        Config::set('hisab.admin_emails', ['owner@example.com']);
        $this->actingAs($owner);

        $this->post('/admin/businesses/'.$biz->id.'/extend', [
            'months' => 3, 'method' => 'bkash', 'note' => 'বিকাশে পেয়েছি',
        ])->assertRedirect();

        $fresh = $biz->fresh();
        // Extends from the still-active paid_until, not from today.
        $this->assertSame(
            now()->addDays(10)->addMonths(3)->toDateString(),
            $fresh->paid_until->toDateString()
        );
        $this->assertDatabaseHas('subscription_payments', [
            'business_id' => $biz->id, 'amount' => 360, 'months' => 3, 'method' => 'bkash',
            'plan_slug' => 'standard', // manual extension now charges the plan price
        ]);
    }

    public function test_admin_extend_from_today_when_expired(): void
    {
        [$owner] = $this->makeShop(['email' => 'owner@example.com', 'name' => 'মালিক']);
        [, $biz] = $this->makeShop(['name' => 'মেয়াদ শেষ দোকান'], [
            'name' => 'মেয়াদ শেষ দোকান', 'plan' => 'trial',
            'trial_ends_at' => now()->subDays(5),
        ]);
        Config::set('hisab.admin_emails', ['owner@example.com']);
        $this->actingAs($owner);

        $this->post('/admin/businesses/'.$biz->id.'/extend', [
            'months' => 1, 'method' => 'cash',
        ])->assertRedirect();

        $this->assertSame(
            now()->addMonth()->toDateString(),
            $biz->fresh()->paid_until->toDateString()
        );
    }

    public function test_admin_record_payment_without_extension(): void
    {
        [$owner] = $this->makeShop(['email' => 'owner@example.com', 'name' => 'মালিক']);
        [, $biz] = $this->makeShop(['name' => 'আংশিক দোকান'], ['name' => 'আংশিক দোকান']);
        Config::set('hisab.admin_emails', ['owner@example.com']);
        $this->actingAs($owner);

        $this->post('/admin/businesses/'.$biz->id.'/payments', [
            'amount' => 150, 'months' => 0, 'method' => 'nagad',
            'paid_at' => now()->toDateString(), 'note' => 'আংশিক',
        ])->assertRedirect();

        $this->assertDatabaseHas('subscription_payments', [
            'business_id' => $biz->id, 'amount' => 150, 'months' => 0, 'method' => 'nagad',
        ]);
        $this->assertNull($biz->fresh()->paid_until);
    }
}
