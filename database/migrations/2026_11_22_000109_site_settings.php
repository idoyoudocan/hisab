<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Site-wide settings the owner changes from /admin/settings —
        // payment number/methods and per-plan price/discount overrides.
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Owner's payment details (given 2026-11-22): one number for
        // bKash Personal (Send Money), WhatsApp, Nagad and Rocket.
        DB::table('site_settings')->insert([
            ['key' => 'payment_bkash_number', 'value' => '01724942587', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payment_whatsapp_number', 'value' => '01724942587', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payment_methods', 'value' => 'bkash,nagad,rocket', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
