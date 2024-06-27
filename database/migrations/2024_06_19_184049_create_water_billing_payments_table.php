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
        Schema::create('water_billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id');
            $table->text('prev_read');
            $table->text('pres_read');
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
        Schema::dropIfExists('water_billing_payments');
    }
};
