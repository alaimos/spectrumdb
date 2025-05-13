<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Enums\DatasetPermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dataset_user_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('permission', array_column(DatasetPermission::cases(), 'value'));
            $table->timestamps();

            $table->unique(['dataset_id', 'user_id', 'permission']);
        });
    }
};
