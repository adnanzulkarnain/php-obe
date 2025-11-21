#!/usr/bin/env php
<?php

/**
 * Database Migration CLI
 * Usage: php migrate.php [command]
 * Commands: migrate, rollback, reset, status, seed
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Migration;
use Database\Seeders\DatabaseSeeder;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get command
$command = $argv[1] ?? 'help';
$steps = isset($argv[2]) && is_numeric($argv[2]) ? (int)$argv[2] : 1;

$migration = new Migration();

echo "\n=== OBE System Migration Tool ===\n\n";

switch ($command) {
    case 'migrate':
        echo "Running migrations...\n\n";
        $result = $migration->migrate();

        if (isset($result['executed']) && count($result['executed']) > 0) {
            echo "\nMigrations executed successfully!\n";
            echo "Batch: {$result['batch']}\n";
            echo "Executed: " . count($result['executed']) . " migrations\n";
        } else {
            echo $result['message'] ?? "No migrations executed\n";
        }
        break;

    case 'rollback':
        echo "Rolling back migrations (steps: {$steps})...\n\n";
        $result = $migration->rollback($steps);

        if (isset($result['rolled_back']) && count($result['rolled_back']) > 0) {
            echo "\nRollback completed!\n";
            echo "Rolled back: " . count($result['rolled_back']) . " migrations\n";
        } else {
            echo $result['message'] ?? "Nothing to rollback\n";
        }
        break;

    case 'reset':
        echo "Resetting all migrations...\n\n";
        $result = $migration->reset();

        if (isset($result['rolled_back']) && count($result['rolled_back']) > 0) {
            echo "\nReset completed!\n";
            echo "Rolled back: " . count($result['rolled_back']) . " migrations\n";
        } else {
            echo "No migrations to reset\n";
        }
        break;

    case 'status':
        echo "Migration status:\n\n";
        $status = $migration->status();

        if (empty($status)) {
            echo "No migrations found\n";
        } else {
            printf("%-50s %-15s %-10s\n", "Migration", "Status", "Batch");
            echo str_repeat("-", 75) . "\n";

            foreach ($status as $item) {
                printf("%-50s %-15s %-10s\n",
                    $item['migration'],
                    $item['status'],
                    $item['batch'] ?? '-'
                );
            }
        }
        break;

    case 'seed':
        echo "Seeding database...\n\n";
        $seeder = new DatabaseSeeder();
        $seeder->run();
        break;

    case 'fresh':
        echo "Fresh migration (reset + migrate + seed)...\n\n";

        echo "Step 1: Resetting...\n";
        $migration->reset();

        echo "\nStep 2: Migrating...\n";
        $migration->migrate();

        echo "\nStep 3: Seeding...\n";
        $seeder = new DatabaseSeeder();
        $seeder->run();

        echo "\n✓ Fresh migration completed!\n";
        break;

    case 'help':
    default:
        echo "Available commands:\n\n";
        echo "  migrate              Run pending migrations\n";
        echo "  rollback [steps]     Rollback last batch(es) of migrations\n";
        echo "  reset                Rollback all migrations\n";
        echo "  status               Show migration status\n";
        echo "  seed                 Seed database with sample data\n";
        echo "  fresh                Reset, migrate, and seed database\n";
        echo "  help                 Show this help message\n";
        echo "\nExamples:\n";
        echo "  php migrate.php migrate\n";
        echo "  php migrate.php rollback\n";
        echo "  php migrate.php rollback 2\n";
        echo "  php migrate.php status\n";
        echo "  php migrate.php seed\n";
        echo "  php migrate.php fresh\n";
        break;
}

echo "\n";
