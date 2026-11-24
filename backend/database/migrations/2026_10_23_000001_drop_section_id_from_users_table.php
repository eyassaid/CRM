<?php

use App\Models\Section;
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
        // A user's sections are the sections of their teams (teams.section_id).
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Section::class);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Section::class)->nullable()->after('image')->constrained()->nullOnDelete();
        });
    }
};
