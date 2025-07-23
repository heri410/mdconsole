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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->nullable();
            $table->string('lexoffice_id')->unique()->nullable(); // Lexoffice UUID
            $table->json('lexoffice_data')->nullable();
            $table->string('web_payment_id')->nullable();
            $table->string('web_payment_status')->nullable();
            $table->date('web_payment_date')->nullable();
            $table->decimal('web_payment_amount', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
