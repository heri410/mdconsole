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
        Schema::create('positionen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit_name')->default('Stück');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 5, 2)->default(0.00); // Rabatt in Prozent
            $table->boolean('billed')->default(false); // Bereits abgerechnet
            $table->timestamp('billed_at')->nullable(); // Wann abgerechnet
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null'); // Referenz zur erstellten Rechnung
            $table->timestamps();
            
            $table->index(['customer_id', 'billed']);
            $table->index('billed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positionen');
    }
};
