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
        Schema::table('quotes', function (Blueprint $table) {
            // Rename the 'quote' column to 'danish_quote'
            $table->renameColumn('quote', 'danish_quote');

            // Add a new column for 'english_quote'
            $table->text('english_quote')->nullable(); // Nullable to avoid issues with existing rows
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Revert changes
            $table->renameColumn('danish_quote', 'quote');
            $table->dropColumn('english_quote');
        });
    }
};
