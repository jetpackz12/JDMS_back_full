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
        Schema::create('electricity_billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->text('unit_con');
            $table->decimal('amount', 9, 2);
            $table->date('due_date');
            $table->date('date_issue');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electricity_billing_payments');
    }
};
