<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if an index exists on a table.
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $indexes = \DB::select('SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?', [$table, $indexName]);

        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            // Check if index doesn't exist before creating
            $indexName = 'visitors_last_visit_at_index';
            if (! $this->indexExists('visitors', $indexName)) {
                $table->index('last_visit_at');
            }

            $indexName = 'visitors_user_id_converted_at_index';
            if (! $this->indexExists('visitors', $indexName)) {
                $table->index(['user_id', 'converted_at']);
            }

            $indexName = 'visitors_utm_source_last_visit_at_index';
            if (! $this->indexExists('visitors', $indexName)) {
                $table->index(['utm_source', 'last_visit_at']);
            }
        });

        Schema::table('analytics_sessions', function (Blueprint $table) {
            $indexName = 'analytics_sessions_ended_at_index';
            if (! $this->indexExists('analytics_sessions', $indexName)) {
                $table->index('ended_at');
            }

            $indexName = 'analytics_sessions_started_at_index';
            if (! $this->indexExists('analytics_sessions', $indexName)) {
                $table->index('started_at');
            }
        });

        Schema::table('experiments', function (Blueprint $table) {
            $indexName = 'experiments_ended_at_index';
            if (! $this->indexExists('experiments', $indexName)) {
                $table->index('ended_at');
            }

            $indexName = 'experiments_status_index';
            if (! $this->indexExists('experiments', $indexName)) {
                $table->index('status');
            }
        });

        Schema::table('funnel_progress', function (Blueprint $table) {
            $indexName = 'funnel_progress_updated_at_index';
            if (! $this->indexExists('funnel_progress', $indexName)) {
                $table->index('updated_at');
            }

            $indexName = 'funnel_progress_is_converted_index';
            if (! $this->indexExists('funnel_progress', $indexName)) {
                $table->index('is_converted');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropIndex(['last_visit_at']);
            $table->dropIndex(['user_id', 'converted_at']);
            $table->dropIndex(['utm_source', 'last_visit_at']);
        });

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex(['occurred_at']);
        });

        Schema::table('analytics_sessions', function (Blueprint $table) {
            $table->dropIndex(['ended_at']);
            $table->dropIndex(['started_at']);
        });

        Schema::table('experiments', function (Blueprint $table) {
            $table->dropIndex(['ended_at']);
            $table->dropIndex(['status']);
        });

        Schema::table('funnel_progress', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['is_converted']);
        });
    }
};
