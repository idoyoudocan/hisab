<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

/**
 * Creates a purchase and increments product stock, inside one transaction.
 *
 * $items: [['product_id' => int, 'qty' => float, 'price' => float], ...]
 */
class PurchaseService
{
    public static function createPurchase(Business $business, array $data, array $items): Purchase
    {
        return DB::transaction(function () use ($business, $data, $items) {
            $total = 0.0;
            $lines = [];

            foreach ($items as $item) {
                $product = $business->products()->lockForUpdate()->findOrFail($item['product_id']);
                $qty = (float) $item['qty'];
                $price = (float) ($item['price'] ?? $product->purchase_price);
                $lineTotal = round($qty * $price, 2);
                $total += $lineTotal;
                $lines[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'price' => $price,
                    'total' => $lineTotal,
                ];
            }

            $total = round($total, 2);
            $paid = round((float) ($data['paid'] ?? 0), 2);

            $purchase = Purchase::create([
                'business_id' => $business->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'total' => $total,
                'paid' => min($paid, $total),
                'note' => $data['note'] ?? null,
            ]);

            foreach ($lines as $line) {
                $purchase->items()->create([
                    'product_id' => $line['product']->id,
                    'qty' => $line['qty'],
                    'price' => $line['price'],
                    'total' => $line['total'],
                ]);
                $line['product']->increment('stock', $line['qty']);
            }

            return $purchase->fresh();
        });
    }
}
