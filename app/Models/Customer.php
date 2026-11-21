<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'name', 'phone', 'address'];

    public function sales(): HasMany { return $this->hasMany(Sale::class); }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'party_id')
            ->where('party_type', 'customer');
    }

    /** Total due: sales (total − paid) minus collections. */
    public function dueAmount(): float
    {
        $sales = $this->sales()->selectRaw('COALESCE(SUM(total),0) as t, COALESCE(SUM(paid),0) as p')->first();
        $collected = $this->payments()->where('direction', 'in')->sum('amount');

        return round(((float) $sales->t - (float) $sales->p) - (float) $collected, 2);
    }
}
