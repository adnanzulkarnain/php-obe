#!/usr/bin/env php
<?php

/**
 * Database Seeder Runner
 *
 * Usage: php database/seed.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Database\Seeders\DatabaseSeeder;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║       OBE System - Database Seeder                       ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    // Check if database connection is available
    if (!file_exists(__DIR__ . '/../.env')) {
        echo "❌ Error: .env file not found!\n";
        echo "   Please create .env file based on .env.example\n";
        exit(1);
    }

    // Load environment variables
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    // Run seeder
    $seeder = new DatabaseSeeder();
    $seeder->run();

    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════╗\n";
    echo "║  ✅  Database seeding completed successfully!            ║\n";
    echo "╚═══════════════════════════════════════════════════════════╝\n";
    echo "\n";

    exit(0);
} catch (\Exception $e) {
    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════╗\n";
    echo "║  ❌  Database seeding failed!                            ║\n";
    echo "╚═══════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    echo "\n";

    exit(1);
}
