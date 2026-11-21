<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Shared behaviour for every tenant-owned record. */
trait BelongsToBusiness
{
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeForBusiness(Builder $query, Business $business): Builder
    {
        return $query->where($query->getModel()->getTable().'.business_id', $business->id);
    }
}
