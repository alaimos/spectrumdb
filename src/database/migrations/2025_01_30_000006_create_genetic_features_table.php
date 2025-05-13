<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genetic_features', function (Blueprint $table): void {
            $table->id();
            $table->string('feature_id')->unique();
            $table->string('feature_type');
            $table->string('feature_name');
            $table->timestamps();
        });
    }
};
