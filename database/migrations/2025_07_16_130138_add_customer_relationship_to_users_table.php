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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('email');
            $table->string('role')->default('admin')->after('customer_id');
            
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->index(['email', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['email', 'role']);
            $table->dropColumn(['customer_id', 'role']);
        });
    }
};
