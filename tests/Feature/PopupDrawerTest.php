<?php

namespace Tests\Feature;

use App\Livewire\CashCounter;
use App\Livewire\MoneyAccountManager;
use App\Models\Business;
use App\Models\MoneyAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Instant popups + right-side "আরও" drawer.
 *
 * Regression tests for the 2026-11-22 popup complaints: the cash-count and
 * add-account modals must be present in the DOM while closed (Alpine opens
 * them instantly, no server round-trip), the service worker must not serve
 * stale CSS, and the mobile "আরও" menu must be a right-side drawer.
 */
class PopupDrawerTest extends TestCase
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

    public function test_dashboard_contains_closed_modal_markup_in_dom(): void
    {
        [$user] = $this->makeShop();

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        // The cash modal is rendered even while closed, hidden by Alpine.
        $this->assertStringContainsString('modal-overlay', $html);
        $this->assertStringContainsString('x-cloak', $html);
        $this->assertStringContainsString('x-on:open-cash-modal.window', $html);
        $this->assertStringContainsString(".entangle('showModal')", $html, 'modal must entangle its Livewire property');
        // The note-counter form is in the DOM too, so the popup is complete.
        $this->assertStringContainsString('গোনা মোট', $html);
    }

    public function test_money_page_contains_closed_account_modal_markup_in_dom(): void
    {
        [$user] = $this->makeShop();

        $html = $this->actingAs($user)->get(route('money.index'))->assertOk()->getContent();

        $this->assertStringContainsString('modal-overlay', $html);
        $this->assertStringContainsString('x-on:open-account-modal.window', $html);
        $this->assertStringContainsString('নতুন অ্যাকাউন্ট যোগ করুন', $html);
    }

    public function test_cash_counter_open_save_close_state_still_correct(): void
    {
        [$user] = $this->makeShop();

        Livewire::actingAs($user)
            ->test(CashCounter::class)
            ->assertSet('showModal', false)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->set('counts.1000', 2)
            ->set('counts.500', 1)
            ->call('save')
            ->assertSet('showModal', true) // saving keeps the modal open, shows the result
            ->assertSee('গোনা হয়েছে')
            ->call('closeModal')
            ->assertSet('showModal', false);

        $this->assertDatabaseCount('cash_counts', 1);
    }

    public function test_money_account_modal_open_and_close_state_still_correct(): void
    {
        [$user] = $this->makeShop();

        Livewire::actingAs($user)
            ->test(MoneyAccountManager::class)
            ->assertSet('showFormModal', false)
            ->call('openAdd', 'bank')
            ->assertSet('showFormModal', true)
            ->assertSet('type', 'bank')
            ->set('label', 'ডাচ-বাংলা সেভিংস')
            ->set('balance', '5000')
            ->call('save')
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('money_accounts', ['label' => 'ডাচ-বাংলা সেভিংস']);
    }

    public function test_mobile_more_menu_is_right_side_drawer(): void
    {
        [$user] = $this->makeShop();

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('more-drawer', $html);
        $this->assertStringContainsString('more-backdrop', $html);
        $this->assertStringContainsString('more-drawer-head', $html);
        // No trace of the old bottom sheet.
        $this->assertStringNotContainsString('more-sheet', $html);
        // Drawer links are the same module links, toggles respected.
        $this->assertStringContainsString(route('settings.index'), $html);
        $this->assertStringContainsString(route('cash-count.index'), $html);
    }

    public function test_stylesheet_url_is_versioned_and_sw_uses_swr_for_css(): void
    {
        [$user] = $this->makeShop();

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#/css/app\.css\?v=\d+#', $html, 'CSS URL must carry a version query so it can never be served stale');

        $sw = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("'hisab-v3'", $sw);
        $this->assertStringContainsString('staleWhileRevalidate', $sw);
        $this->assertStringContainsString('/livewire/', $sw, 'Livewire requests must still never be cached');
    }
}
