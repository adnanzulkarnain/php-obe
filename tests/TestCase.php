<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Config\Database;
use PDO;

/**
 * Base Test Case
 * Provides common functionality for all tests
 */
abstract class TestCase extends BaseTestCase
{
    protected ?PDO $pdo = null;
    protected static bool $dbInitialized = false;

    /**
     * Setup before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize database connection for integration tests
        if ($this->needsDatabase()) {
            $this->setupDatabase();
        }
    }

    /**
     * Teardown after each test
     */
    protected function tearDown(): void
    {
        $this->pdo = null;
        parent::tearDown();
    }

    /**
     * Check if test needs database
     */
    protected function needsDatabase(): bool
    {
        return false;
    }

    /**
     * Setup database connection
     */
    protected function setupDatabase(): void
    {
        if (!self::$dbInitialized) {
            Database::connect();
            self::$dbInitialized = true;
        }

        $this->pdo = Database::getConnection();

        // Begin transaction for test isolation
        if ($this->pdo && !$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    /**
     * Rollback database changes after test
     */
    protected function rollbackDatabase(): void
    {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Create mock JWT token for testing
     */
    protected function createMockToken(array $payload = []): string
    {
        $defaultPayload = [
            'id_user' => 1,
            'username' => 'testuser',
            'role' => 'admin',
            'exp' => time() + 3600
        ];

        $payload = array_merge($defaultPayload, $payload);

        // Simple mock token for testing (not actual JWT)
        return base64_encode(json_encode($payload));
    }

    /**
     * Assert array has keys
     */
    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array missing key: $key");
        }
    }

    /**
     * Assert successful API response structure
     */
    protected function assertSuccessResponse(array $response): void
    {
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
    }

    /**
     * Assert error API response structure
     */
    protected function assertErrorResponse(array $response, int $expectedCode = null): void
    {
        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);

        if ($expectedCode !== null) {
            $this->assertArrayHasKey('code', $response);
            $this->assertEquals($expectedCode, $response['code']);
        }
    }
}
