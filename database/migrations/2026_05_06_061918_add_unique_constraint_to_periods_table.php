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
        Schema::table('periods', function (Blueprint $table): void {
            $table->unique('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('periods', function (Blueprint $table): void {
            $table->dropUnique(['start_date']);
        });
    }
};
