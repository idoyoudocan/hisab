<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\MoneyAccount;
use App\Models\PlanRequest;
use App\Models\SiteSetting;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\Plans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PackageSettingsTest extends TestCase
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

    public function test_payment_number_is_seeded_by_migration(): void
    {
        $this->assertSame('01724942587', SiteSetting::paymentBkashNumber());
        $this->assertSame('01724942587', SiteSetting::paymentWhatsappNumber());
        $this->assertSame(['bkash', 'nagad', 'rocket'], SiteSetting::paymentMethods());
    }

    public function test_plan_override_changes_payable_and_per_month_everywhere(): void
    {
        SiteSetting::set('plan.standard.regular', '1000');
        SiteSetting::set('plan.standard.discount', '70');

        $standard = Plans::find('standard');
        $this->assertSame(1000, $standard['regular']);
        $this->assertSame(70, $standard['discount']);
        $this->assertSame(300, $standard['payable']);
        $this->assertSame(100, $standard['per_month']);
        $this->assertSame(700, $standard['savings']);

        // The public packages page reflects the override (৳100/month is
        // unique to the overridden standard plan).
        $this->get(route('packages.index'))->assertOk()->assertSee('৳100');
    }

    public function test_admin_extension_uses_overridden_plan_price(): void
    {
        $admin = $this->makeAdmin();
        [$user, $business] = $this->makeShop();

        SiteSetting::set('plan.standard.regular', '1000');
        SiteSetting::set('plan.standard.discount', '70'); // payable ৳300

        $this->actingAs($admin)->post(route('admin.businesses.extend', $business), [
            'months' => 3,
            'method' => 'bkash',
        ])->assertRedirect();

        $payment = SubscriptionPayment::where('business_id', $business->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(300, (float) $payment->amount);
        $this->assertSame('standard', $payment->plan_slug);
    }

    public function test_admin_approval_uses_overridden_plan_price(): void
    {
        $admin = $this->makeAdmin();
        [$user, $business] = $this->makeShop();

        SiteSetting::set('plan.business.regular', '4000');
        SiteSetting::set('plan.business.discount', '75'); // payable ৳1000

        $this->actingAs($user)->post(route('packages.request', 'business'))->assertRedirect();
        $request = PlanRequest::where('business_id', $business->id)->first();
        $this->assertNotNull($request);

        $this->actingAs($admin)->post(route('admin.plan-requests.approve', $request), [
            'method' => 'bkash',
        ])->assertRedirect();

        $payment = SubscriptionPayment::where('business_id', $business->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(1000, (float) $payment->amount);
        $this->assertSame('business', $payment->plan_slug);
        $this->assertSame(PlanRequest::STATUS_APPROVED, $request->fresh()->status);
    }

    public function test_admin_can_view_and_save_settings(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('01724942587')
            ->assertSee('বেসিক')
            ->assertSee('পেমেন্ট সেটিংস');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'bkash_number' => '01711111111',
            'whatsapp_number' => '01722222222',
            'methods' => ['bkash', 'nagad'],
            'plans' => [
                'basic' => ['regular' => 400, 'discount' => 25],
                'standard' => ['regular' => 900, 'discount' => 60],
                'premium' => ['regular' => 1800, 'discount' => 65],
                'business' => ['regular' => 3600, 'discount' => 70],
            ],
        ])->assertRedirect();

        $this->assertSame('01711111111', SiteSetting::paymentBkashNumber());
        $this->assertSame('01722222222', SiteSetting::paymentWhatsappNumber());
        $this->assertSame(['bkash', 'nagad'], SiteSetting::paymentMethods());
        $this->assertSame(300, Plans::find('basic')['payable']);
        $this->assertSame(300, Plans::find('basic')['per_month']);
    }

    public function test_non_admin_cannot_access_admin_settings(): void
    {
        [$user] = $this->makeShop();

        $this->actingAs($user)->get(route('admin.settings'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.settings.update'), [
            'bkash_number' => '01711111111',
            'whatsapp_number' => '01711111111',
            'methods' => ['bkash'],
            'plans' => [],
        ])->assertForbidden();
    }

    public function test_admin_settings_validation_rejects_bad_phone_and_discount(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'bkash_number' => '12345',
            'whatsapp_number' => '01722222222',
            'methods' => ['bkash'],
            'plans' => [
                'basic' => ['regular' => 300, 'discount' => 95],
                'standard' => ['regular' => 900, 'discount' => 60],
                'premium' => ['regular' => 1800, 'discount' => 65],
                'business' => ['regular' => 3600, 'discount' => 70],
            ],
        ])->assertSessionHasErrors(['bkash_number', 'plans.basic.discount']);

        // Nothing was saved.
        $this->assertSame('01724942587', SiteSetting::paymentBkashNumber());
        $this->assertSame(50, Plans::find('basic')['discount']);
    }

    public function test_payment_number_shown_to_logged_in_shopkeeper_only(): void
    {
        // Public page (logged out): the number must never appear.
        $this->get(route('packages.index'))->assertOk()->assertDontSee('01724942587');

        [$user, $business] = $this->makeShop();

        // Plan-choose page (logged in): number + tappable tel: link.
        $this->actingAs($user)->get(route('packages.choose', 'standard'))
            ->assertOk()
            ->assertSee('01724942587')
            ->assertSee('tel:01724942587')
            ->assertSee('ট্রানজেকশন আইডিসহ স্ক্রিনশট');

        // Pending-request card on the packages page (logged in).
        $this->actingAs($user)->post(route('packages.request', 'standard'))->assertRedirect();
        $this->actingAs($user)->get(route('packages.index'))
            ->assertOk()
            ->assertSee('01724942587')
            ->assertSee('অনুরোধ পাঠানো হয়েছে');
    }

    public function test_packages_page_shows_real_price_savings_and_limited_time_banner(): void
    {
        $this->get(route('packages.index'))
            ->assertOk()
            ->assertSee('সীমিত সময়ের অফার')
            ->assertSee('এই ছাড়ের দাম যেকোনো সময় শেষ হতে পারে')
            ->assertSee('আসল দাম')
            ->assertSee('আপনি দেবেন')
            ->assertSee('আপনার সাশ্রয়')
            ->assertSee('৳2,520') // business plan savings: 3600 − 1080
            ->assertSee('70% ছাড়');
    }
}
