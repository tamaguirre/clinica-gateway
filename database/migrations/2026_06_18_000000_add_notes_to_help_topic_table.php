<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_topic', function (Blueprint $table) {
            if (!Schema::hasColumn('help_topic', 'notes')) {
                $table->text('notes')->nullable()->after('priority_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('help_topic', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};