<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // New columns found in the controller logic
            if (!Schema::hasColumn('orders', 'total')) {
                $table->decimal('total', 10, 2)->nullable()->after('valor');
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('total');
            }
            if (!Schema::hasColumn('orders', 'external_id')) {
                $table->string('external_id')->nullable()->after('external_reference');
            }
            if (!Schema::hasColumn('orders', 'payment_url')) {
                $table->text('payment_url')->nullable()->after('external_id');
            }

            // Modify existing columns
            // plano_id needs to be nullable because digital products don't have a plan
            $table->unsignedBigInteger('plano_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['total', 'payment_method', 'external_id', 'payment_url']);
            // We can't easily revert nullable change without knowing the original state for sure, 
            // but usually we don't revert that in down() unless strictly necessary.
            // $table->unsignedBigInteger('plano_id')->nullable(false)->change(); 
        });
    }
};
