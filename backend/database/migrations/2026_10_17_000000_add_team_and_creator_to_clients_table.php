<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A separate migration because `teams` is created after `clients`.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Nullable so deleting a team keeps its clients (then only admins see them).
            $table->foreignId('team_id')->nullable()->after('image')->constrained()->nullOnDelete();
            // The user who brought the client.
            $table->foreignId('created_by')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
