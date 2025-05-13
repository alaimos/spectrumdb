<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxa', function (Blueprint $table): void {
            $table->id();
            $table->string('feature_id')->unique();
            $table->foreignId('parent_taxa_id')->nullable()->constrained('taxa')->nullOnDelete();
            $table->string('kingdom')->nullable();
            $table->string('phylum')->nullable();
            $table->string('class')->nullable();
            $table->string('order')->nullable();
            $table->string('family')->nullable();
            $table->string('genus')->nullable();
            $table->string('species')->nullable();
        });
    }
};
