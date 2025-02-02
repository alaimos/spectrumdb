<?php

namespace Database\Migrations;

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', array_column(Role::cases(), 'value'))
                ->default(Role::default()->value);
        });
    }
};
