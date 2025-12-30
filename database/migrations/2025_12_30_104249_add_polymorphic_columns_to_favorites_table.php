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
        Schema::table('favorites', function (Blueprint $table) {
            $table->unsignedBigInteger('favoritable_id')->nullable()->after('post_id');
            $table->string('favoritable_type')->nullable()->after('favoritable_id');
            $table->unsignedBigInteger('post_id')->nullable()->change();
            
            // index for polymorphic relationship queries
            $table->index(['favoritable_id', 'favoritable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropIndex(['favoritable_id', 'favoritable_type']);
            $table->dropColumn(['favoritable_id', 'favoritable_type']);
        });
    }
};
