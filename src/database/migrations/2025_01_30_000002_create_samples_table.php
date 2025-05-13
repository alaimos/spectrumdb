<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->string('sample_code');
            $table->string('variety');
            $table->string('plant_stage');
            $table->integer('biological_replica');
            $table->text('sample_conditions');
            $table->string('plant_section');
            $table->date('sampling_date');
            $table->text('location');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};
