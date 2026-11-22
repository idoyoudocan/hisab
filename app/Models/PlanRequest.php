<?php

namespace App\Models;

use App\Support\Plans;
use Illuminate\Database\Eloquent\Model;

class PlanRequest extends Model
{
    use BelongsToBusiness;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'business_id', 'plan_slug', 'amount', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /** The requested plan's details (from the single source of truth). */
    public function plan(): ?array
    {
        return Plans::find($this->plan_slug);
    }

    public function planName(): string
    {
        return $this->plan()['name'] ?? $this->plan_slug;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
