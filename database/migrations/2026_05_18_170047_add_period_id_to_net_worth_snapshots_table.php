<?php

use App\Models\Period;
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
        Schema::table('net_worth_snapshots', function (Blueprint $table) {
            $table->foreignId('period_id')->nullable()->constrained()->nullOnDelete()->after('net_worth_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('net_worth_snapshots', function (Blueprint $table) {
            $table->dropForeignIdFor(Period::class);
            $table->dropColumn('period_id');
        });
    }
};
