<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

/**
 * Creates a sale and decrements product stock, inside one transaction.
 *
 * $items: [['product_id' => int, 'qty' => float, 'price' => float], ...]
 */
class SaleService
{
    public static function createSale(Business $business, array $data, array $items): Sale
    {
        return DB::transaction(function () use ($business, $data, $items) {
            $total = 0.0;
            $lines = [];

            foreach ($items as $item) {
                $product = $business->products()->lockForUpdate()->findOrFail($item['product_id']);
                $qty = (float) $item['qty'];
                $price = (float) ($item['price'] ?? $product->sale_price);
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
            $discount = round((float) ($data['discount_amount'] ?? 0), 2);
            $discount = max(0, min($discount, $total));
            $total = round($total - $discount, 2);
            $paid = round((float) ($data['paid'] ?? 0), 2);

            $sale = Sale::create([
                'business_id' => $business->id,
                'customer_id' => $data['customer_id'] ?? null,
                'sale_date' => $data['sale_date'] ?? now()->toDateString(),
                'total' => $total,
                'discount_amount' => $discount,
                'paid' => min($paid, $total),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'note' => $data['note'] ?? null,
            ]);

            foreach ($lines as $line) {
                $sale->items()->create([
                    'product_id' => $line['product']->id,
                    'qty' => $line['qty'],
                    'price' => $line['price'],
                    'total' => $line['total'],
                ]);
                $line['product']->decrement('stock', $line['qty']);
            }

            return $sale->fresh();
        });
    }
}
