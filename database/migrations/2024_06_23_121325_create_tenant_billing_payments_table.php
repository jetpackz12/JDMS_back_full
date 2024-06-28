<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id');
            $table->foreignId('water_billing_payment_id')->nullable();
            $table->date('water_billing_date_issue')->nullable();
            $table->foreignId('electricity_billing_payment_id')->nullable();
            $table->date('electricity_billing_date_issue')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_billing_payments');
    }
};
