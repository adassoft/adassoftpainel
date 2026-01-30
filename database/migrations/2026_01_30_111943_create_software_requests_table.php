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
        Schema::create('software_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();

            // Project Details
            $table->string('project_name')->nullable();
            $table->string('project_type')->nullable(); // Web, Mobile, Desktop, Integration
            $table->string('budget_range')->nullable(); // <5k, 5-10k, 10-30k, 30k+
            $table->string('deadline')->nullable(); // ex: 3 months

            $table->text('description')->nullable();
            $table->text('features_list')->nullable(); // Specific features

            $table->string('status')->default('novo'); // novo, em_analise, contactado, fechado, arquivado
            $table->text('admin_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_requests');
    }
};
