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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('tld'); // z.B. '.de', '.com'
            $table->string('fqdn'); // Vollständiger Domain-Name
            $table->date('register_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('provider_id')->nullable()->constrained('domain_providers')->onDelete('set null');
            $table->enum('status', ['created', 'deleted', 'expired', 'pending'])->default('created');
            $table->integer('billing_interval')->default(12); // Monate
            $table->timestamps();
            
            $table->index(['customer_id', 'status']);
            $table->index('due_date');
            $table->index('fqdn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
