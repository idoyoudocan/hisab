<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('money_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // cash_register | bank | mobile_banking | sim | other
            $table->string('provider', 100)->nullable();
            $table->string('label', 150);
            $table->string('identifier', 100)->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['business_id', 'type']);
        });

        Schema::create('cash_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->dateTime('counted_at');
            $table->json('denominations');
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('system_cash', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['business_id', 'counted_at']);
        });

        // Every existing business gets its one cash-register account.
        // Its stored balance stays 0 — the displayed balance is always
        // the derived register cash (see MoneyAccount::displayBalance).
        foreach (DB::table('businesses')->select('id')->get() as $business) {
            DB::table('money_accounts')->insert([
                'business_id' => $business->id,
                'type' => 'cash_register',
                'provider' => null,
                'label' => 'ক্যাশ রেজিস্টার',
                'identifier' => null,
                'balance' => 0,
                'note' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_counts');
        Schema::dropIfExists('money_accounts');
    }
};
