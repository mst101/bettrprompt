#!/bin/bash

# Clear Data Collection Database Script
# Removes all data from the bettrprompt_data_collection database except users
# while preserving the schema for future collection runs

set -e

echo "🗑️  Clearing bettrprompt_data_collection database..."

# Confirm before clearing
read -p "Are you sure you want to clear all data collection records? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Cancelled."
    exit 0
fi

echo "⏳ Clearing all data from bettrprompt_data_collection (except users table)..."

./vendor/bin/sail exec -T pgsql psql -U sail -d bettrprompt_data_collection << EOF
-- Disable foreign key constraints
SET CONSTRAINTS ALL DEFERRED;

-- Clear all tables except users (in reverse dependency order)
DELETE FROM feedback;
DELETE FROM prompt_runs;
DELETE FROM visitors;
DELETE FROM jobs;
DELETE FROM cache;

-- Reset ID sequences
ALTER SEQUENCE prompt_runs_id_seq RESTART WITH 1;
ALTER SEQUENCE feedback_id_seq RESTART WITH 1;
ALTER SEQUENCE visitors_id_seq RESTART WITH 1;
ALTER SEQUENCE jobs_id_seq RESTART WITH 1;
ALTER SEQUENCE cache_id_seq RESTART WITH 1;

-- Re-enable foreign key constraints
SET CONSTRAINTS ALL IMMEDIATE;
EOF

echo "✅ Data collection database cleared successfully!"
echo "📊 Database: bettrprompt_data_collection"
echo "👥 Users table: Preserved"
echo "📝 Schema preserved - ready for new data collection runs"
