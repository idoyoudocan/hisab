<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Payment;
use InvalidArgumentException;

class PaymentService
{
    /**
     * Record a collection from a customer (direction "in") or a payment to
     * a supplier (direction "out"). Amount may not exceed the party's due.
     */
    public static function record(Business $business, string $partyType, int $partyId, array $data): Payment
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('টাকার পরিমাণ ০-এর বেশি হতে হবে।');
        }

        if ($partyType === 'customer') {
            $party = $business->customers()->findOrFail($partyId);
            $due = $party->dueAmount();
            $direction = 'in';
        } else {
            $party = $business->suppliers()->findOrFail($partyId);
            $due = $party->dueAmount();
            $direction = 'out';
        }

        if ($amount > $due) {
            throw new InvalidArgumentException('পরিমাণ বাকির চেয়ে বেশি হতে পারবে না (বাকি: ৳'.number_format($due, 0).')।');
        }

        return Payment::create([
            'business_id' => $business->id,
            'party_type' => $partyType,
            'party_id' => $party->id,
            'amount' => $amount,
            'direction' => $direction,
            'method' => $data['method'] ?? 'cash',
            'date' => $data['date'] ?? now()->toDateString(),
            'note' => $data['note'] ?? null,
        ]);
    }
}
