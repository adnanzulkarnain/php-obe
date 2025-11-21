<?php

namespace App\Core;

use App\Config\Database;
use PDO;

/**
 * Database Migration Manager
 * Handles database schema migrations
 */
class Migration
{
    private PDO $pdo;
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';

    public function __construct()
    {
        Database::connect();
        $this->pdo = Database::getConnection();
        $this->migrationsPath = __DIR__ . '/../../database/migrations';
        $this->ensureMigrationsTable();
    }

    /**
     * Ensure migrations table exists
     */
    private function ensureMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id SERIAL PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->pdo->exec($sql);
    }

    /**
     * Run pending migrations
     */
    public function migrate(): array
    {
        $executed = [];
        $batch = $this->getNextBatchNumber();
        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            return ['message' => 'No pending migrations'];
        }

        foreach ($pending as $migration) {
            echo "Migrating: {$migration}\n";

            try {
                $this->pdo->beginTransaction();

                // Execute migration
                $this->executeMigration($migration);

                // Record migration
                $this->recordMigration($migration, $batch);

                $this->pdo->commit();

                $executed[] = $migration;
                echo "Migrated: {$migration}\n";
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                echo "Failed: {$migration} - " . $e->getMessage() . "\n";
                break;
            }
        }

        return [
            'executed' => $executed,
            'batch' => $batch
        ];
    }

    /**
     * Rollback last batch of migrations
     */
    public function rollback(int $steps = 1): array
    {
        $rolledBack = [];
        $batches = $this->getLastBatches($steps);

        if (empty($batches)) {
            return ['message' => 'Nothing to rollback'];
        }

        foreach ($batches as $batch) {
            $migrations = $this->getMigrationsByBatch($batch);

            foreach (array_reverse($migrations) as $migration) {
                echo "Rolling back: {$migration}\n";

                try {
                    $this->pdo->beginTransaction();

                    // Execute rollback if exists
                    $this->executeMigrationRollback($migration);

                    // Remove migration record
                    $this->removeMigrationRecord($migration);

                    $this->pdo->commit();

                    $rolledBack[] = $migration;
                    echo "Rolled back: {$migration}\n";
                } catch (\Exception $e) {
                    $this->pdo->rollBack();
                    echo "Failed to rollback: {$migration} - " . $e->getMessage() . "\n";
                    break 2;
                }
            }
        }

        return ['rolled_back' => $rolledBack];
    }

    /**
     * Get migration status
     */
    public function status(): array
    {
        $all = $this->getAllMigrationFiles();
        $executed = $this->getExecutedMigrations();

        $status = [];

        foreach ($all as $migration) {
            $status[] = [
                'migration' => $migration,
                'status' => in_array($migration, $executed) ? 'Migrated' : 'Pending',
                'batch' => $this->getMigrationBatch($migration)
            ];
        }

        return $status;
    }

    /**
     * Reset all migrations
     */
    public function reset(): array
    {
        $migrations = $this->getExecutedMigrations();
        $rolledBack = [];

        foreach (array_reverse($migrations) as $migration) {
            echo "Rolling back: {$migration}\n";

            try {
                $this->executeMigrationRollback($migration);
                $this->removeMigrationRecord($migration);
                $rolledBack[] = $migration;
            } catch (\Exception $e) {
                echo "Failed: {$migration} - " . $e->getMessage() . "\n";
            }
        }

        return ['rolled_back' => $rolledBack];
    }

    /**
     * Get pending migrations
     */
    private function getPendingMigrations(): array
    {
        $all = $this->getAllMigrationFiles();
        $executed = $this->getExecutedMigrations();

        return array_diff($all, $executed);
    }

    /**
     * Get all migration files
     */
    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
            return [];
        }

        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            if (preg_match('/\.sql$/', $file)) {
                $migrations[] = $file;
            }
        }

        sort($migrations);

        return $migrations;
    }

    /**
     * Get executed migrations
     */
    private function getExecutedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM {$this->migrationsTable} ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Execute migration
     */
    private function executeMigration(string $migration): void
    {
        $filePath = $this->migrationsPath . '/' . $migration;

        if (!file_exists($filePath)) {
            throw new \Exception("Migration file not found: {$migration}");
        }

        $sql = file_get_contents($filePath);

        // Split by -- ROLLBACK separator
        $parts = preg_split('/^-- ROLLBACK$/m', $sql);
        $upSql = trim($parts[0]);

        if (!empty($upSql)) {
            $this->pdo->exec($upSql);
        }
    }

    /**
     * Execute migration rollback
     */
    private function executeMigrationRollback(string $migration): void
    {
        $filePath = $this->migrationsPath . '/' . $migration;

        if (!file_exists($filePath)) {
            return;
        }

        $sql = file_get_contents($filePath);

        // Split by -- ROLLBACK separator
        $parts = preg_split('/^-- ROLLBACK$/m', $sql);

        if (isset($parts[1])) {
            $downSql = trim($parts[1]);
            if (!empty($downSql)) {
                $this->pdo->exec($downSql);
            }
        }
    }

    /**
     * Record migration
     */
    private function recordMigration(string $migration, int $batch): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)");
        $stmt->execute([$migration, $batch]);
    }

    /**
     * Remove migration record
     */
    private function removeMigrationRecord(string $migration): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->migrationsTable} WHERE migration = ?");
        $stmt->execute([$migration]);
    }

    /**
     * Get next batch number
     */
    private function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM {$this->migrationsTable}");
        $maxBatch = $stmt->fetchColumn();

        return $maxBatch ? $maxBatch + 1 : 1;
    }

    /**
     * Get last batches
     */
    private function getLastBatches(int $steps): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT batch FROM {$this->migrationsTable} ORDER BY batch DESC LIMIT {$steps}");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get migrations by batch
     */
    private function getMigrationsByBatch(int $batch): array
    {
        $stmt = $this->pdo->prepare("SELECT migration FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id");
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get migration batch
     */
    private function getMigrationBatch(string $migration): ?int
    {
        $stmt = $this->pdo->prepare("SELECT batch FROM {$this->migrationsTable} WHERE migration = ?");
        $stmt->execute([$migration]);
        $result = $stmt->fetchColumn();

        return $result ?: null;
    }
}
