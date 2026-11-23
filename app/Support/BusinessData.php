<?php

namespace App\Support;

use App\Models\Business;
use App\Models\CashCount;
use App\Models\Category;
use App\Models\Customer;
use App\Models\MoneyAccount;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * One place for everything about a business's data as a whole:
 * reset (fresh start), backup (JSON export) and restore (JSON import),
 * plus deleting a business completely (admin only).
 *
 * Everything here is scoped to ONE business (business_id) and runs
 * inside a DB transaction, so a failure leaves the old data untouched.
 * No new composer packages — plain PHP json_encode/json_decode only.
 */
class BusinessData
{
    public const BACKUP_APP = 'hisab';
    public const BACKUP_VERSION = 1;

    /** Tables in the backup file, in dependency order (parents first). */
    private const TABLES = [
        'categories' => Category::class,
        'products' => Product::class,
        'customers' => Customer::class,
        'suppliers' => Supplier::class,
        'money_accounts' => MoneyAccount::class,
        'sales' => Sale::class,
        'sale_items' => SaleItem::class,
        'purchases' => Purchase::class,
        'purchase_items' => PurchaseItem::class,
        'payments' => Payment::class,
        'transactions' => Transaction::class,
        'cash_counts' => CashCount::class,
    ];

    /**
     * Item tables have no business_id of their own — they belong to a
     * business through their parent (sale / purchase).
     * Key => [parent table, foreign key on the item table].
     */
    private const CHILD_TABLES = [
        'sale_items' => ['sales', 'sale_id'],
        'purchase_items' => ['purchases', 'purchase_id'],
    ];

    /**
     * Wipe every piece of shop data of this business (sales, purchases,
     * customers, suppliers, payments, transactions, categories, money
     * accounts, cash counts) — in one transaction.
     *
     * Kept untouched: business profile, login, subscription/trial/plan,
     * subscription payments, plan requests, module settings, admin notices.
     * When $reseed is true, the default categories and the cash-register
     * account are created again and opening cash is set to 0 (fresh start).
     */
    public static function wipeData(Business $business, bool $reseed = true): void
    {
        DB::transaction(function () use ($business, $reseed) {
            $bid = $business->id;

            // Children first (FKs cascade too, but explicit order is safer).
            SaleItem::whereIn('sale_id', Sale::where('business_id', $bid)->select('id'))->delete();
            Sale::where('business_id', $bid)->delete();
            PurchaseItem::whereIn('purchase_id', Purchase::where('business_id', $bid)->select('id'))->delete();
            Purchase::where('business_id', $bid)->delete();
            Payment::where('business_id', $bid)->delete();
            Transaction::where('business_id', $bid)->delete();
            Category::where('business_id', $bid)->delete();
            MoneyAccount::where('business_id', $bid)->delete();
            CashCount::where('business_id', $bid)->delete();
            Product::where('business_id', $bid)->delete();
            Customer::where('business_id', $bid)->delete();
            Supplier::where('business_id', $bid)->delete();

            if ($reseed) {
                Business::seedCategories($business);
                MoneyAccount::ensureRegister($business);
                $business->update(['opening_cash' => 0]);
            }
        });
    }

    /** Reset = wipe + reseed, the shopkeeper's "start from scratch". */
    public static function resetData(Business $business): void
    {
        self::wipeData($business, true);
    }

    /** Full backup of one business as a plain array (JSON-ready). */
    public static function export(Business $business): array
    {
        $data = [];
        foreach (self::TABLES as $key => $model) {
            if (isset(self::CHILD_TABLES[$key])) {
                [$parentTable, $parentKey] = self::CHILD_TABLES[$key];
                $parentModel = self::TABLES[$parentTable];
                $rows = $model::whereIn(
                    $parentKey,
                    $parentModel::where('business_id', $business->id)->select('id')
                )->orderBy('id')->get();
            } else {
                $rows = $model::where('business_id', $business->id)
                    ->orderBy('id')
                    ->get();
            }
            $data[$key] = $rows->map(fn ($row) => $row->toArray())->all();
        }

        return [
            'app' => self::BACKUP_APP,
            'version' => self::BACKUP_VERSION,
            'business_id' => $business->id,
            'business_name' => $business->name,
            'exported_at' => now()->toIso8601String(),
            // Profile fields that restore may bring back. Subscription fields
            // (plan / trial_ends_at / paid_until) are exported for reference
            // only — restore deliberately ignores them (see restore()).
            'profile' => [
                'name' => $business->name,
                'type' => $business->type,
                'phone' => $business->phone,
                'address' => $business->address,
                'opening_cash' => $business->opening_cash,
                'settings' => $business->settings,
            ],
            'data' => $data,
        ];
    }

    /**
     * Restore a backup into the SAME business it was exported from.
     * The current data is wiped first and the backup rows are inserted
     * with fresh ids (old ids are remapped, so other businesses' rows
     * can never collide). One transaction: an invalid file changes nothing.
     *
     * @return array Counts per table, e.g. ['products' => 3, 'sales' => 12]
     *
     * @throws RuntimeException When the file is not a valid backup of this business.
     */
    public static function restore(Business $business, mixed $decoded): array
    {
        if (! is_array($decoded)) {
            throw new RuntimeException('ফাইলটি সঠিক JSON নয়।');
        }
        if (($decoded['app'] ?? null) !== self::BACKUP_APP) {
            throw new RuntimeException('এটি হিসাবের ব্যাকআপ ফাইল নয়।');
        }
        if ((int) ($decoded['version'] ?? 0) !== self::BACKUP_VERSION) {
            throw new RuntimeException('এই ব্যাকআপের সংস্করণটি সাপোর্ট করে না।');
        }
        if ((int) ($decoded['business_id'] ?? 0) !== (int) $business->id) {
            throw new RuntimeException('এই ব্যাকআপটি অন্য দোকানের — শুধু নিজের দোকানের ব্যাকআপ ফেরানো যাবে।');
        }
        $data = $decoded['data'] ?? null;
        if (! is_array($data)) {
            throw new RuntimeException('ব্যাকআপ ফাইলে কোনো ডেটা পাওয়া যায়নি।');
        }

        $counts = [];
        DB::transaction(function () use ($business, $data, $decoded, &$counts) {
            // Wipe without reseeding — the backup rows replace everything.
            self::wipeData($business, false);

            // Restore the profile (never the subscription: plan, trial and
            // paid_until stay exactly as they are now).
            $profile = $decoded['profile'] ?? [];
            if (is_array($profile) && $profile !== []) {
                $business->update([
                    'name' => $profile['name'] ?? $business->name,
                    'type' => $profile['type'] ?? $business->type,
                    'phone' => $profile['phone'] ?? $business->phone,
                    'address' => $profile['address'] ?? $business->address,
                    'opening_cash' => $profile['opening_cash'] ?? $business->opening_cash,
                    'settings' => $profile['settings'] ?? $business->settings,
                ]);
            }

            $maps = [];
            foreach (self::TABLES as $key => $model) {
                $rows = $data[$key] ?? [];
                if (! is_array($rows)) {
                    throw new RuntimeException('ব্যাকআপ ফাইলটি ভাঙা (অংশ: '.$key.')।');
                }
                $maps[$key] = [];
                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        throw new RuntimeException('ব্যাকআপ ফাইলটি ভাঙা (অংশ: '.$key.')।');
                    }
                    $oldId = (int) ($row['id'] ?? 0);
                    unset($row['id']);
                    // Item tables have no business_id column (scoped via parent).
                    if (! isset(self::CHILD_TABLES[$key])) {
                        $row['business_id'] = $business->id;
                    }
                    $row = self::remapRow($key, $row, $maps);
                    $newId = $model::query()->insertGetId($row);
                    if ($oldId > 0) {
                        $maps[$key][$oldId] = $newId;
                    }
                }
                $counts[$key] = count($rows);
            }
        });

        return $counts;
    }

    /** Rewrite foreign keys of one backup row to the newly inserted ids. */
    private static function remapRow(string $table, array $row, array $maps): array
    {
        // Cash-count denominations come back as a PHP array — store as JSON.
        if ($table === 'cash_counts' && isset($row['denominations']) && is_array($row['denominations'])) {
            $row['denominations'] = json_encode($row['denominations']);
        }
        $remap = [
            'sales' => ['customer_id' => 'customers'],
            'sale_items' => ['sale_id' => 'sales', 'product_id' => 'products'],
            'purchases' => ['supplier_id' => 'suppliers'],
            'purchase_items' => ['purchase_id' => 'purchases', 'product_id' => 'products'],
            'payments' => [],
        ];
        foreach ($remap[$table] ?? [] as $column => $targetTable) {
            if (isset($row[$column]) && $row[$column] !== null) {
                $row[$column] = $maps[$targetTable][(int) $row[$column]] ?? null;
            }
        }
        if ($table === 'payments' && isset($row['party_id']) && $row['party_id'] !== null) {
            $target = $row['party_type'] === 'supplier' ? 'suppliers' : 'customers';
            $row['party_id'] = $maps[$target][(int) $row['party_id']] ?? null;
        }

        return $row;
    }

    /**
     * Delete a business completely: the owner user, the business row and
     * every related record (shop data, notices, subscription history and
     * plan requests). One transaction. Use from the admin panel only.
     */
    public static function deleteBusinessCompletely(Business $business): void
    {
        DB::transaction(function () use ($business) {
            $user = $business->owner;
            self::wipeData($business, false);
            $business->subscriptionPayments()->delete();
            $business->planRequests()->delete();
            $business->adminNotices()->delete();
            $business->delete();
            $user?->delete();
        });
    }
}
