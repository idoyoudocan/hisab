<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'name', 'phone', 'address'];

    public function purchases(): HasMany { return $this->hasMany(Purchase::class); }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'party_id')
            ->where('party_type', 'supplier');
    }

    /** Total due to supplier: purchases (total − paid) minus payments out. */
    public function dueAmount(): float
    {
        $purchases = $this->purchases()->selectRaw('COALESCE(SUM(total),0) as t, COALESCE(SUM(paid),0) as p')->first();
        $paidOut = $this->payments()->where('direction', 'out')->sum('amount');

        return round(((float) $purchases->t - (float) $purchases->p) - (float) $paidOut, 2);
    }
}
