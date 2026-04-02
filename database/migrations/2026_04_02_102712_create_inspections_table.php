<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hive_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('inspected_at');
            $table->text('raw_notes');

            // Queen
            $table->boolean('queen_seen')->nullable();
            $table->enum('queen_status', ['laying', 'not_laying', 'swarm_cells', 'supersedure_cells'])->nullable();

            // Brood
            $table->boolean('eggs_present')->nullable();
            $table->boolean('larvae_present')->nullable();
            $table->boolean('capped_brood_present')->nullable();
            $table->unsignedTinyInteger('brood_pattern_score')->nullable(); // 1–5

            // Population & stores
            $table->unsignedTinyInteger('frames_of_brood')->nullable();
            $table->unsignedTinyInteger('frames_of_bees')->nullable();
            $table->unsignedTinyInteger('frames_of_honey')->nullable();
            $table->unsignedTinyInteger('honey_stores_score')->nullable(); // 1–5

            // Varroa
            $table->unsignedSmallInteger('varroa_count')->nullable(); // mites per 100 bees
            $table->enum('varroa_method', ['sugar_roll', 'alcohol_wash', 'sticky_board'])->nullable();

            // Behaviour & health
            $table->unsignedTinyInteger('temperament_score')->nullable(); // 1–5
            $table->json('disease_observations')->nullable();
            $table->unsignedTinyInteger('overall_health_score')->nullable(); // 1–5

            // Actions taken
            $table->boolean('feeding_done')->nullable();
            $table->string('feeding_notes')->nullable();
            $table->text('treatment_applied')->nullable();
            $table->unsignedTinyInteger('supers_added')->nullable();
            $table->unsignedTinyInteger('supers_removed')->nullable();

            // Conditions
            $table->string('weather')->nullable();

            // AI processing
            $table->json('followup_questions')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
