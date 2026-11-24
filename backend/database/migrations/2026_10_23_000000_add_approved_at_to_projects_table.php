<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Projects requested by employees stay pending (null) until the team leader or an admin approves them.
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('image');
        });

        // Projects that already exist were created before approvals.
        DB::table('projects')->update(['approved_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
