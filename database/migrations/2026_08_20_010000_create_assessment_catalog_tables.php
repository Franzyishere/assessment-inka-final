<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('delivery_mode');
            $table->unsignedTinyInteger('sequence')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('assessment_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('simulation_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_type_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('participant_instructions')->nullable();
            $table->text('assessor_guidance')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('simulation_material_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_scenario_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->unsignedSmallInteger('page_order');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime_type')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['simulation_scenario_id', 'page_order'], 'scenario_page_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_material_pages');
        Schema::dropIfExists('simulation_scenarios');
        Schema::dropIfExists('assessment_programs');
        Schema::dropIfExists('simulation_types');
    }
};
