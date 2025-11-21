<?php

namespace Tests\Integration\Repository;

use Tests\TestCase;
use App\Repository\KurikulumRepository;
use App\Config\Database;

/**
 * Integration Test for KurikulumRepository
 * Tests actual database operations
 */
class KurikulumRepositoryTest extends TestCase
{
    private KurikulumRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new KurikulumRepository();
    }

    protected function needsDatabase(): bool
    {
        return true;
    }

    protected function tearDown(): void
    {
        $this->rollbackDatabase();
        parent::tearDown();
    }

    public function testFindAllReturnsArray(): void
    {
        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertIsArray($result);
    }

    public function testFindByIdReturnsCorrectStructure(): void
    {
        // Arrange - Assume there's at least one kurikulum in test database
        $allKurikulum = $this->repository->findAll();

        if (empty($allKurikulum)) {
            $this->markTestSkipped('No kurikulum data in test database');
        }

        $firstKurikulum = $allKurikulum[0];
        $id = $firstKurikulum['id_kurikulum'];

        // Act
        $result = $this->repository->findById($id);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKeys([
            'id_kurikulum',
            'kode_kurikulum',
            'nama_kurikulum'
        ], $result);
    }

    public function testFindByIdReturnsNullForInvalidId(): void
    {
        // Act
        $result = $this->repository->findById(99999);

        // Assert
        $this->assertNull($result);
    }

    public function testCreateInsertsNewRecord(): void
    {
        // Arrange
        $data = [
            'id_prodi' => 'TIF',
            'kode_kurikulum' => 'TEST_' . time(),
            'nama_kurikulum' => 'Test Kurikulum',
            'tahun_berlaku' => 2024,
            'status' => 'draft',
            'deskripsi' => 'Test description'
        ];

        // Act
        $id = $this->repository->create($data);

        // Assert
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        // Verify the record exists
        $created = $this->repository->findById($id);
        $this->assertNotNull($created);
        $this->assertEquals($data['kode_kurikulum'], $created['kode_kurikulum']);
    }

    public function testUpdateModifiesExistingRecord(): void
    {
        // Arrange - Create a test record first
        $createData = [
            'id_prodi' => 'TIF',
            'kode_kurikulum' => 'TEST_UPDATE_' . time(),
            'nama_kurikulum' => 'Original Name',
            'tahun_berlaku' => 2024,
            'status' => 'draft'
        ];

        $id = $this->repository->create($createData);

        $updateData = [
            'nama_kurikulum' => 'Updated Name',
            'deskripsi' => 'Updated description'
        ];

        // Act
        $result = $this->repository->update($id, $updateData);

        // Assert
        $this->assertTrue($result);

        // Verify the update
        $updated = $this->repository->findById($id);
        $this->assertEquals('Updated Name', $updated['nama_kurikulum']);
    }

    public function testUpdateStatusChangesKurikulumStatus(): void
    {
        // Arrange - Create a test record
        $data = [
            'id_prodi' => 'TIF',
            'kode_kurikulum' => 'TEST_STATUS_' . time(),
            'nama_kurikulum' => 'Test Status',
            'tahun_berlaku' => 2024,
            'status' => 'draft'
        ];

        $id = $this->repository->create($data);

        // Act
        $result = $this->repository->updateStatus($id, 'approved');

        // Assert
        $this->assertTrue($result);

        // Verify the status change
        $updated = $this->repository->findById($id);
        $this->assertEquals('approved', $updated['status']);
    }

    public function testFindByProdiReturnsOnlyProdiKurikulum(): void
    {
        // Arrange
        $prodi = 'TIF';

        // Act
        $result = $this->repository->findByProdi($prodi);

        // Assert
        $this->assertIsArray($result);

        // All results should belong to the specified prodi
        foreach ($result as $kurikulum) {
            $this->assertEquals($prodi, $kurikulum['id_prodi']);
        }
    }
}
