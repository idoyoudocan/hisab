<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CashCount;
use App\Models\Customer;
use App\Models\MoneyAccount;
use App\Models\Payment;
use App\Models\PlanRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SubscriptionPayment;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DataResetBackupAdminTest extends TestCase
{
    use RefreshDatabase;

    private function makeShop(array $userOverrides = [], array $businessOverrides = []): array
    {
        $user = User::factory()->create($userOverrides);
        $business = Business::create(array_merge([
            'user_id' => $user->id,
            'name' => 'টেস্ট দোকান',
            'type' => 'মুদি দোকান',
            'phone' => '01711111111',
            'opening_cash' => 1000,
            'plan' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ], $businessOverrides));
        Business::seedCategories($business);
        MoneyAccount::ensureRegister($business);

        return [$user, $business];
    }

    private function makeAdmin(string $email = 'owner@example.com'): User
    {
        Config::set('hisab.admin_emails', [$email, 'boss@example.com']);

        return User::factory()->create(['email' => $email]);
    }

    /** Fill a shop with one of everything, so reset/backup/restore have data to work on. */
    private function seedShopData(Business $business): array
    {
        $product = $business->products()->create([
            'name' => 'চিনি', 'unit' => 'কেজি',
            'purchase_price' => 80, 'sale_price' => 100, 'stock' => 50, 'low_stock_at' => 5,
        ]);
        $customer = $business->customers()->create(['name' => 'রহিম', 'phone' => '01722222222']);
        $supplier = $business->suppliers()->create(['name' => 'পাইকারি বাজার', 'phone' => '01733333333']);

        $sale = $business->sales()->create([
            'customer_id' => $customer->id, 'sale_date' => now()->toDateString(),
            'total' => 1000, 'discount_amount' => 0, 'paid' => 600, 'payment_method' => 'cash',
        ]);
        $sale->items()->create(['product_id' => $product->id, 'qty' => 10, 'price' => 100, 'total' => 1000]);

        $purchase = $business->purchases()->create([
            'supplier_id' => $supplier->id, 'purchase_date' => now()->toDateString(),
            'total' => 800, 'paid' => 800,
        ]);
        $purchase->items()->create(['product_id' => $product->id, 'qty' => 10, 'price' => 80, 'total' => 800]);

        $business->payments()->create([
            'party_type' => 'customer', 'party_id' => $customer->id,
            'amount' => 200, 'direction' => 'in', 'method' => 'cash', 'date' => now()->toDateString(),
        ]);
        $business->transactions()->create([
            'type' => 'expense', 'category' => 'দোকান ভাড়া', 'amount' => 500,
            'date' => now()->toDateString(), 'scope' => 'shop',
        ]);
        $business->moneyAccounts()->create([
            'type' => 'bank', 'provider' => 'সোনালী ব্যাংক',
            'label' => 'সোনালী সেভিংস', 'balance' => 5000,
        ]);
        CashCount::create([
            'business_id' => $business->id, 'counted_at' => now(),
            'denominations' => ['1000' => 1], 'total' => 1000,
            'system_cash' => 1000, 'difference' => 0,
        ]);
        $business->subscriptionPayments()->create([
            'amount' => 150, 'method' => 'bkash', 'months' => 1,
            'plan_slug' => 'basic', 'paid_at' => now()->toDateString(),
        ]);
        $business->planRequests()->create([
            'plan_slug' => 'standard', 'amount' => 360, 'status' => PlanRequest::STATUS_PENDING,
        ]);

        return compact('product', 'customer', 'supplier', 'sale', 'purchase');
    }

    private function assertShopDataPresent(Business $business): void
    {
        $this->assertSame(1, $business->sales()->count());
        $this->assertSame(1, $business->purchases()->count());
        $this->assertSame(1, $business->products()->count());
        $this->assertSame(1, $business->customers()->count());
        $this->assertSame(1, $business->suppliers()->count());
        $this->assertSame(1, $business->payments()->count());
        $this->assertSame(1, $business->transactions()->count());
        $this->assertSame(1, $business->cashCounts()->count());
        $this->assertSame(1, Sale::where('business_id', $business->id)->first()->items()->count());
    }

    private function assertShopDataWiped(Business $business): void
    {
        $this->assertSame(0, $business->sales()->count());
        $this->assertSame(0, $business->purchases()->count());
        $this->assertSame(0, $business->products()->count());
        $this->assertSame(0, $business->customers()->count());
        $this->assertSame(0, $business->suppliers()->count());
        $this->assertSame(0, $business->payments()->count());
        $this->assertSame(0, $business->transactions()->count());
        $this->assertSame(0, $business->cashCounts()->count());
    }

    public function test_reset_wipes_data_keeps_profile_and_reseeds(): void
    {
        [$user, $business] = $this->makeShop();
        $this->seedShopData($business);
        $this->actingAs($user);

        // Wrong name: nothing happens.
        $this->post('/settings/reset', ['confirm_name' => 'ভুল নাম'])
            ->assertSessionHasErrors('confirm_name');
        $this->assertShopDataPresent($business);

        $this->post('/settings/reset', ['confirm_name' => 'টেস্ট দোকান'])
            ->assertRedirect();

        $fresh = $business->fresh();
        $this->assertShopDataWiped($fresh);
        // Profile, subscription and billing history survive.
        $this->assertSame('টেস্ট দোকান', $fresh->name);
        $this->assertSame('মুদি দোকান', $fresh->type);
        $this->assertSame('01711111111', $fresh->phone);
        $this->assertSame('trial', $fresh->plan);
        $this->assertSame(0.0, (float) $fresh->opening_cash);
        $this->assertSame(1, $fresh->subscriptionPayments()->count());
        $this->assertSame(1, $fresh->planRequests()->count());
        // Default categories are seeded again, cash register recreated.
        $this->assertDatabaseHas('categories', ['business_id' => $business->id, 'name' => 'বিক্রয়']);
        $this->assertSame(1, $fresh->moneyAccounts()->where('type', 'cash_register')->count());
        $this->assertSame(1, $fresh->moneyAccounts()->count());
    }

    public function test_backup_download_contains_own_data_only(): void
    {
        [$user, $business] = $this->makeShop();
        $this->seedShopData($business);
        [$otherUser, $otherBusiness] = $this->makeShop(['email' => 'other@example.com'], ['name' => 'অন্য দোকান']);
        $otherBusiness->products()->create([
            'name' => 'অন্যের মাল', 'unit' => 'পিস',
            'purchase_price' => 10, 'sale_price' => 20, 'stock' => 1, 'low_stock_at' => 0,
        ]);
        $this->actingAs($user);

        $response = $this->get('/settings/backup')->assertOk();
        $decoded = json_decode($response->streamedContent(), true);

        $this->assertSame('hisab', $decoded['app']);
        $this->assertSame(1, $decoded['version']);
        $this->assertSame($business->id, $decoded['business_id']);
        $this->assertCount(1, $decoded['data']['products']);
        $this->assertSame('চিনি', $decoded['data']['products'][0]['name']);
        $this->assertCount(1, $decoded['data']['sales']);
        $this->assertCount(1, $decoded['data']['sale_items']);
        $this->assertSame('টেস্ট দোকান', $decoded['profile']['name']);
    }

    public function test_backup_restore_round_trip(): void
    {
        [$user, $business] = $this->makeShop();
        $this->seedShopData($business);
        $this->actingAs($user);

        $backupJson = $this->get('/settings/backup')->assertOk()->streamedContent();

        // Wipe everything first (as if starting fresh).
        $this->post('/settings/reset', ['confirm_name' => 'টেস্ট দোকান'])->assertRedirect();
        $this->assertShopDataWiped($business->fresh());

        $file = UploadedFile::fake()->createWithContent('backup.json', $backupJson);
        $this->post('/settings/restore', ['backup' => $file])
            ->assertRedirect()
            ->assertSessionHas('status');

        $fresh = $business->fresh();
        $this->assertShopDataPresent($fresh);
        $this->assertSame('চিনি', $fresh->products()->first()->name);
        $this->assertSame('রহিম', $fresh->customers()->first()->name);
        $this->assertSame(5000.0, (float) $fresh->moneyAccounts()->where('type', 'bank')->first()->balance);
        $this->assertSame(['1000' => 1], $fresh->cashCounts()->first()->denominations);
        // Sale still points at the restored customer and product.
        $sale = $fresh->sales()->first();
        $this->assertSame($fresh->customers()->first()->id, (int) $sale->customer_id);
        $this->assertSame($fresh->products()->first()->id, (int) $sale->items()->first()->product_id);
        // Subscription was NOT rolled back by the restore.
        $this->assertSame('trial', $fresh->plan);
        $this->assertSame(1, $fresh->subscriptionPayments()->count());
    }

    public function test_restore_with_wrong_or_invalid_file_changes_nothing(): void
    {
        [$user, $business] = $this->makeShop();
        $this->seedShopData($business);
        $this->actingAs($user);

        // Not JSON at all.
        $this->post('/settings/restore', [
            'backup' => UploadedFile::fake()->createWithContent('bad.json', 'this is not json'),
        ])->assertSessionHasErrors('backup');
        $this->assertShopDataPresent($business);

        // Valid JSON but another app.
        $this->post('/settings/restore', [
            'backup' => UploadedFile::fake()->createWithContent('bad.json', json_encode(['app' => 'other', 'version' => 1])),
        ])->assertSessionHasErrors('backup');
        $this->assertShopDataPresent($business);

        // A backup of a DIFFERENT business (tenant isolation).
        [, $otherBusiness] = $this->makeShop(['email' => 'other@example.com'], ['name' => 'অন্য দোকান']);
        $otherBackup = json_encode([
            'app' => 'hisab', 'version' => 1, 'business_id' => $otherBusiness->id,
            'profile' => [], 'data' => [],
        ]);
        $this->post('/settings/restore', [
            'backup' => UploadedFile::fake()->createWithContent('other.json', $otherBackup),
        ])->assertSessionHasErrors('backup');
        $this->assertShopDataPresent($business);
    }

    public function test_disabled_business_cannot_login_and_enable_restores_login(): void
    {
        $admin = $this->makeAdmin();
        [$user, $business] = $this->makeShop(['email' => 'shop@example.com', 'password' => bcrypt('password123')]);
        $this->actingAs($admin);

        $this->post('/admin/businesses/'.$business->id.'/disable', ['reason' => 'পেমেন্ট বাকি'])
            ->assertRedirect();
        $fresh = $business->fresh();
        $this->assertTrue($fresh->isDisabled());
        $this->assertSame('পেমেন্ট বাকি', $fresh->disabled_reason);

        // Logged-out shopkeeper cannot log in; the reason is shown.
        $this->post('/logout');
        $this->post('/login', ['email' => 'shop@example.com', 'password' => 'password123'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // A logged-in (pre-disable) session is bounced to the disabled page.
        $this->actingAs($user);
        $this->get('/dashboard')->assertRedirect(route('account.disabled'));
        $this->get('/account-disabled')->assertOk()->assertSee('পেমেন্ট বাকি');

        // Re-enable: login works again.
        $this->actingAs($admin);
        $this->post('/admin/businesses/'.$business->id.'/enable')->assertRedirect();
        $this->assertTrue($business->fresh()->isActive());

        $this->post('/logout');
        $this->post('/login', ['email' => 'shop@example.com', 'password' => 'password123'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_delete_removes_user_business_and_all_data(): void
    {
        $admin = $this->makeAdmin();
        [$user, $business] = $this->makeShop(['email' => 'shop@example.com']);
        $this->seedShopData($business);
        $this->actingAs($admin);

        // Wrong name: nothing is deleted.
        $this->post('/admin/businesses/'.$business->id.'/delete', ['confirm_name' => 'ভুল নাম'])
            ->assertSessionHasErrors('confirm_name');
        $this->assertDatabaseHas('businesses', ['id' => $business->id]);

        $this->post('/admin/businesses/'.$business->id.'/delete', ['confirm_name' => 'টেস্ট দোকান'])
            ->assertRedirect(route('admin.businesses'));

        $this->assertDatabaseMissing('businesses', ['id' => $business->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('sales', ['business_id' => $business->id]);
        $this->assertDatabaseMissing('products', ['business_id' => $business->id]);
        $this->assertDatabaseMissing('customers', ['business_id' => $business->id]);
        $this->assertDatabaseMissing('subscription_payments', ['business_id' => $business->id]);
        $this->assertDatabaseMissing('plan_requests', ['business_id' => $business->id]);
    }

    public function test_admin_cannot_delete_an_admin_account(): void
    {
        $actor = $this->makeAdmin('boss@example.com');
        // The target's owner is an admin too — but the actor is a different admin.
        Config::set('hisab.admin_emails', ['boss@example.com', 'owner@example.com']);
        [$user, $business] = $this->makeShop(['email' => 'owner@example.com']);
        $this->actingAs($actor);

        $this->post('/admin/businesses/'.$business->id.'/delete', ['confirm_name' => 'টেস্ট দোকান'])
            ->assertForbidden();
        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_admin_notice_shows_on_dashboard_and_can_be_dismissed(): void
    {
        $admin = $this->makeAdmin();
        [$user, $business] = $this->makeShop();
        $this->actingAs($admin);

        $this->post('/admin/businesses/'.$business->id.'/notices', ['message' => 'আগামীকালের মধ্যে পেমেন্ট করুন'])
            ->assertRedirect();
        $this->assertDatabaseHas('admin_notices', [
            'business_id' => $business->id,
            'message' => 'আগামীকালের মধ্যে পেমেন্ট করুন',
        ]);
        $noticeId = $business->adminNotices()->first()->id;

        // Shopkeeper sees the banner on the dashboard.
        $this->actingAs($user);
        $this->get('/dashboard')->assertOk()->assertSee('আগামীকালের মধ্যে পেমেন্ট করুন');

        // Dismiss it: gone from the dashboard afterwards.
        $this->post('/notices/'.$noticeId.'/read')->assertRedirect();
        $this->assertNotNull($business->adminNotices()->first()->fresh()->read_at);
        $this->get('/dashboard')->assertOk()->assertDontSee('আগামীকালের মধ্যে পেমেন্ট করুন');

        // Another business's notice cannot be dismissed by this shopkeeper.
        [, $otherBusiness] = $this->makeShop(['email' => 'other@example.com'], ['name' => 'অন্য দোকান']);
        $otherNotice = $otherBusiness->adminNotices()->create(['message' => 'অন্যের বার্তা']);
        $this->post('/notices/'.$otherNotice->id.'/read')->assertNotFound();
    }

    public function test_admin_businesses_disabled_filter_and_dashboard_count(): void
    {
        $admin = $this->makeAdmin();
        $this->makeShop(['email' => 'a@example.com'], ['name' => 'চালু দোকান']);
        [, $disabledBusiness] = $this->makeShop(['email' => 'b@example.com'], ['name' => 'বন্ধ দোকান']);
        $this->actingAs($admin);

        $this->post('/admin/businesses/'.$disabledBusiness->id.'/disable', ['reason' => 'পরীক্ষা'])
            ->assertRedirect();

        $this->get('/admin')->assertOk()->assertSee('বন্ধ অ্যাকাউন্ট');
        $this->get('/admin/businesses?status=disabled')->assertOk()
            ->assertSee('বন্ধ দোকান')->assertDontSee('চালু দোকান');
        $this->get('/admin/businesses/'.$disabledBusiness->id)->assertOk()
            ->assertSee('বন্ধ আছে')->assertSee('পরীক্ষা');
    }
}
