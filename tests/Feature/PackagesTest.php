<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\MoneyAccount;
use App\Models\PlanRequest;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\Plans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PackagesTest extends TestCase
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

    private function makeAdmin(): User
    {
        Config::set('hisab.admin_emails', ['owner@example.com']);

        return User::factory()->create(['email' => 'owner@example.com']);
    }

    public function test_plan_math_is_consistent(): void
    {
        $plans = Plans::all();
        $this->assertCount(4, $plans);

        $discounts = array_column($plans, 'discount');
        $perMonth = array_column($plans, 'per_month');
        $this->assertSame([50, 60, 65, 70], $discounts, 'discounts must strictly increase with duration');
        $this->assertSame([150, 120, 105, 90], $perMonth, 'per-month price must strictly decrease with duration');

        foreach ($plans as $plan) {
            $this->assertSame(
                (int) round($plan['regular'] * (100 - $plan['discount']) / 100),
                $plan['payable'],
                "payable for {$plan['slug']} must equal regular × (1 − discount)"
            );
            $this->assertSame($plan['regular'] - $plan['payable'], $plan['savings']);
        }

        $this->assertSame('basic', Plans::forMonths(1)['slug']);
        $this->assertSame('standard', Plans::forMonths(3)['slug']);
        $this->assertSame('premium', Plans::forMonths(6)['slug']);
        $this->assertSame('business', Plans::forMonths(12)['slug']);
        $this->assertNull(Plans::forMonths(2));
        $this->assertNull(Plans::find('nope'));
    }

    public function test_packages_page_is_public_and_shows_all_plans(): void
    {
        $response = $this->get('/packages');
        $response->assertOk()
            ->assertSee('যত বড় প্যাকেজ, তত বেশি ছাড়')
            ->assertSee('বেসিক')->assertSee('স্ট্যান্ডার্ড')
            ->assertSee('প্রিমিয়াম')->assertSee('বিজনেস')
            ->assertSee('৳150')->assertSee('৳360')->assertSee('৳630')->assertSee('৳1,080')
            ->assertSee('সবচেয়ে জনপ্রিয়')->assertSee('সবচেয়ে লাভজনক')
            ->assertSee('এই প্ল্যান নিন');

        [$user] = $this->makeShop();
        $this->actingAs($user)->get('/packages')->assertOk()->assertSee('এই প্ল্যান নিন');
    }

    public function test_choose_requires_login_and_shows_plan(): void
    {
        $this->get('/packages/standard/choose')->assertRedirect('/login');

        [$user] = $this->makeShop();
        $this->actingAs($user)->get('/packages/standard/choose')->assertOk()
            ->assertSee('স্ট্যান্ডার্ড')->assertSee('৳360')->assertSee('অনুরোধ পাঠান');

        $this->actingAs($user)->get('/packages/nope/choose')->assertNotFound();
    }

    public function test_plan_request_created_and_second_request_does_not_duplicate(): void
    {
        [$user, $business] = $this->makeShop();
        $this->actingAs($user);

        $this->post('/packages/standard/request')->assertRedirect('/packages');
        $this->assertDatabaseCount('plan_requests', 1);
        $this->assertDatabaseHas('plan_requests', [
            'business_id' => $business->id,
            'plan_slug' => 'standard',
            'amount' => 360,
            'status' => 'pending',
        ]);

        // Second request: no duplicate, just a message about the existing one.
        $this->post('/packages/premium/request')->assertRedirect('/packages');
        $this->assertDatabaseCount('plan_requests', 1);
        $this->get('/packages')->assertSee('অনুরোধ পাঠানো হয়েছে');
    }

    public function test_plan_requests_are_isolated_per_business(): void
    {
        [$userA] = $this->makeShop(['email' => 'a@example.com']);
        [$userB] = $this->makeShop(['email' => 'b@example.com'], ['name' => 'দ্বিতীয় দোকান']);

        $this->actingAs($userA)->post('/packages/standard/request');
        $this->assertDatabaseCount('plan_requests', 1);

        // A sees its own pending notice (this also consumes the flash message,
        // which otherwise leaks into the next request's shared test session).
        $this->actingAs($userA)->get('/packages')->assertOk()->assertSee('অনুরোধ পাঠানো হয়েছে');

        // Business B sees no pending notice and can send its own request.
        $this->actingAs($userB)->get('/packages')->assertOk()->assertDontSee('অনুরোধ পাঠানো হয়েছে');
        $this->actingAs($userB)->post('/packages/basic/request');
        $this->assertDatabaseCount('plan_requests', 2);
    }

    public function test_admin_approves_request_extending_from_today_when_expired(): void
    {
        [$user, $business] = $this->makeShop([], [
            'plan' => 'trial',
            'trial_ends_at' => now()->subDay(), // trial over, never paid
        ]);
        $admin = $this->makeAdmin();

        $this->actingAs($user)->post('/packages/standard/request')->assertRedirect('/packages');
        $request = PlanRequest::firstOrFail();

        $this->actingAs($admin)->post(route('admin.plan-requests.approve', $request), ['method' => 'bkash'])
            ->assertRedirect();

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame(now()->addMonths(3)->toDateString(), $business->fresh()->paid_until->toDateString());
        $this->assertSame('standard', $business->fresh()->plan);
        $this->assertDatabaseHas('subscription_payments', [
            'business_id' => $business->id,
            'amount' => 360,
            'months' => 3,
            'plan_slug' => 'standard',
            'method' => 'bkash',
        ]);
    }

    public function test_admin_approves_request_extending_from_paid_until_when_active(): void
    {
        [$user, $business] = $this->makeShop([], [
            'plan' => 'standard',
            'paid_until' => now()->addDays(10)->toDateString(),
        ]);
        $admin = $this->makeAdmin();

        $this->actingAs($user)->post('/packages/premium/request');
        $request = PlanRequest::firstOrFail();

        $this->actingAs($admin)->post(route('admin.plan-requests.approve', $request), ['method' => 'cash']);

        $expected = now()->addDays(10)->addMonths(6)->toDateString();
        $this->assertSame($expected, $business->fresh()->paid_until->toDateString());
        $this->assertDatabaseHas('subscription_payments', [
            'business_id' => $business->id,
            'amount' => 630,
            'plan_slug' => 'premium',
        ]);
    }

    public function test_admin_rejects_request_without_changes(): void
    {
        [$user, $business] = $this->makeShop();
        $admin = $this->makeAdmin();

        $this->actingAs($user)->post('/packages/basic/request');
        $request = PlanRequest::firstOrFail();

        $this->actingAs($admin)->post(route('admin.plan-requests.reject', $request))->assertRedirect();

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertNull($business->fresh()->paid_until);
        $this->assertDatabaseCount('subscription_payments', 0);
    }

    public function test_admin_manual_extend_uses_plan_prices(): void
    {
        [$user, $business] = $this->makeShop();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.businesses.extend', $business), [
            'months' => 3, 'method' => 'cash',
        ])->assertRedirect();

        $this->assertDatabaseHas('subscription_payments', [
            'business_id' => $business->id,
            'amount' => 360,
            'months' => 3,
            'plan_slug' => 'standard',
        ]);

        $this->actingAs($admin)->post(route('admin.businesses.extend', $business), [
            'months' => 12, 'method' => 'bkash',
        ]);

        $this->assertDatabaseHas('subscription_payments', [
            'business_id' => $business->id,
            'amount' => 1080,
            'months' => 12,
            'plan_slug' => 'business',
        ]);
    }

    public function test_monthly_revenue_sums_only_this_months_payments(): void
    {
        [$user, $business] = $this->makeShop();
        $admin = $this->makeAdmin();

        SubscriptionPayment::create(['business_id' => $business->id, 'amount' => 150, 'method' => 'cash', 'months' => 1, 'paid_at' => now()->toDateString()]);
        SubscriptionPayment::create(['business_id' => $business->id, 'amount' => 360, 'method' => 'bkash', 'months' => 3, 'paid_at' => now()->toDateString()]);
        SubscriptionPayment::create(['business_id' => $business->id, 'amount' => 1080, 'method' => 'cash', 'months' => 12, 'paid_at' => now()->subMonth()->toDateString()]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('মাসিক আয় ৳510');
    }

    public function test_admin_dashboard_lists_pending_requests(): void
    {
        [$user] = $this->makeShop();
        $admin = $this->makeAdmin();

        $this->actingAs($user)->post('/packages/business/request');

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee('প্ল্যানের অনুরোধ')
            ->assertSee('বিজনেস')
            ->assertSee('৳1,080')
            ->assertSee('অনুমোদন করুন');
    }

    public function test_settings_shows_current_plan_name_after_approval(): void
    {
        [$user, $business] = $this->makeShop();
        $admin = $this->makeAdmin();

        $this->actingAs($user)->post('/packages/standard/request');
        $request = PlanRequest::firstOrFail();
        $this->actingAs($admin)->post(route('admin.plan-requests.approve', $request), ['method' => 'cash']);

        // actingAs reuses the same user model instance across requests in a
        // test, so its cached business relation is stale — refresh it first
        // (real browsers load fresh data on every request).
        $user = $user->fresh();

        $this->actingAs($user)->get('/settings')->assertOk()->assertSee('স্ট্যান্ডার্ড প্ল্যান');
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('স্ট্যান্ডার্ড প্ল্যান');
    }

    public function test_landing_links_to_packages_and_shows_from_price(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('প্যাকেজ')
            ->assertSee('/packages')
            ->assertSee('৳১৫০');
    }

    public function test_dashboard_trial_banner_links_to_packages(): void
    {
        [$user] = $this->makeShop([], ['trial_ends_at' => now()->addDays(2)]);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('প্যাকেজ দেখুন');
    }

    public function test_shopkeeper_cannot_approve_requests(): void
    {
        [$user] = $this->makeShop();
        $this->actingAs($user)->post('/packages/basic/request');
        $request = PlanRequest::firstOrFail();

        $this->actingAs($user)->post(route('admin.plan-requests.approve', $request), ['method' => 'cash'])
            ->assertForbidden();
        $this->assertSame('pending', $request->fresh()->status);
    }
}
