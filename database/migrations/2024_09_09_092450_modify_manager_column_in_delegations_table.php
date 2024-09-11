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
        Schema::table('delegations', function (Blueprint $table) {
            $table->unsignedBigInteger('manager')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delegations', function (Blueprint $table) {
            $table->unsignedBigInteger('manager')->nullable(false)->change();
        });
    }
};
