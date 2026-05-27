<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Backfill instance_url for existing Bluesky rows before making it non-nullable
        DB::table('social_accounts')
            ->where('provider', 'bluesky')
            ->whereNull('instance_url')
            ->update(['instance_url' => 'https://bsky.social']);

        Schema::table('social_accounts', function (Blueprint $table) {
            // 2. Make instance_url non-nullable
            $table->string('instance_url')->nullable(false)->change();

            // 3. Drop the old one-account-per-provider constraint
            $table->dropUnique(['user_id', 'provider']);

            // 4. Add per-handle uniqueness constraint
            $table->unique(['user_id', 'provider', 'instance_url', 'handle']);

            // 5. Add auth failure timestamp
            $table->timestamp('auth_failed_at')->nullable()->after('handle');
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'provider', 'instance_url', 'handle']);
            $table->dropColumn('auth_failed_at');
            $table->string('instance_url')->nullable()->change();
            $table->unique(['user_id', 'provider']);
        });
    }
};
