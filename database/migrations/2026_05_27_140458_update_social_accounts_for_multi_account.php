<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill instance_url for existing Bluesky rows (created before instance_url was required)
        DB::table('social_accounts')
            ->where('provider', 'bluesky')
            ->whereNull('instance_url')
            ->update(['instance_url' => 'https://bsky.social']);

        // Add new index and column first, so user_id FK is covered before we drop the old index.
        // MySQL refuses to drop an index if it's the only one covering a FK column.
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->unique(['user_id', 'provider', 'instance_url', 'handle']);
            $table->timestamp('auth_failed_at')->nullable()->after('handle');
        });

        // Now safe to drop: the new index already covers user_id for the FK constraint.
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        // Re-add the old index before dropping the new one (same FK coverage logic).
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->unique(['user_id', 'provider']);
        });

        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'provider', 'instance_url', 'handle']);
            $table->dropColumn('auth_failed_at');
        });
    }
};
