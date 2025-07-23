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
        Schema::create('domain_prices', function (Blueprint $table) {
            $table->id();
            $table->string('tld'); // z.B. '.de', '.com'
            $table->foreignId('provider_id')->constrained('domain_providers')->onDelete('cascade');
            $table->decimal('price_renew', 10, 2)->nullable();
            $table->decimal('price_transfer', 10, 2)->nullable();
            $table->decimal('price_update', 10, 2)->nullable();
            $table->decimal('price_create', 10, 2)->nullable();
            $table->decimal('price_restore', 10, 2)->nullable();
            $table->decimal('price_change_owner', 10, 2)->nullable();
            $table->timestamps();
            
            $table->unique(['tld', 'provider_id']);
            $table->index('tld');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_prices');
    }
};
