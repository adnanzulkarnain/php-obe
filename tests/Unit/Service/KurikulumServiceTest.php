<?php

namespace Tests\Unit\Service;

use Tests\TestCase;
use App\Service\KurikulumService;
use App\Repository\KurikulumRepository;
use App\Service\AuditLogService;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit Test for KurikulumService
 */
class KurikulumServiceTest extends TestCase
{
    private KurikulumService $service;
    private MockObject $repositoryMock;
    private MockObject $auditLogMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->repositoryMock = $this->createMock(KurikulumRepository::class);
        $this->auditLogMock = $this->createMock(AuditLogService::class);

        // Inject mocks into service
        $this->service = new KurikulumService();

        // Use reflection to inject private dependencies
        $reflection = new \ReflectionClass($this->service);

        $repoProperty = $reflection->getProperty('repository');
        $repoProperty->setAccessible(true);
        $repoProperty->setValue($this->service, $this->repositoryMock);

        $auditProperty = $reflection->getProperty('auditLog');
        $auditProperty->setAccessible(true);
        $auditProperty->setValue($this->service, $this->auditLogMock);
    }

    public function testGetAllKurikulumReturnsArray(): void
    {
        // Arrange
        $expectedData = [
            [
                'id_kurikulum' => 1,
                'kode_kurikulum' => 'K2024',
                'nama_kurikulum' => 'Kurikulum OBE 2024',
                'status' => 'approved'
            ]
        ];

        $this->repositoryMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedData);

        // Act
        $result = $this->service->getAllKurikulum();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('K2024', $result[0]['kode_kurikulum']);
    }

    public function testGetKurikulumByIdReturnsCorrectData(): void
    {
        // Arrange
        $id = 1;
        $expectedData = [
            'id_kurikulum' => $id,
            'kode_kurikulum' => 'K2024',
            'nama_kurikulum' => 'Kurikulum OBE 2024'
        ];

        $this->repositoryMock
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($expectedData);

        // Act
        $result = $this->service->getKurikulumById($id);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($id, $result['id_kurikulum']);
    }

    public function testGetKurikulumByIdReturnsNullWhenNotFound(): void
    {
        // Arrange
        $this->repositoryMock
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        // Act
        $result = $this->service->getKurikulumById(999);

        // Assert
        $this->assertNull($result);
    }

    public function testCreateKurikulumCallsRepositoryAndAuditLog(): void
    {
        // Arrange
        $data = [
            'id_prodi' => 'TIF',
            'kode_kurikulum' => 'K2024',
            'nama_kurikulum' => 'Kurikulum OBE 2024',
            'tahun_berlaku' => 2024
        ];

        $createdId = 1;

        $this->repositoryMock
            ->expects($this->once())
            ->method('create')
            ->with($data)
            ->willReturn($createdId);

        $this->auditLogMock
            ->expects($this->once())
            ->method('log');

        // Act
        $result = $this->service->createKurikulum($data, 1);

        // Assert
        $this->assertEquals($createdId, $result);
    }

    public function testApproveKurikulumUpdatesStatus(): void
    {
        // Arrange
        $id = 1;
        $userId = 2;

        $this->repositoryMock
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn(['id_kurikulum' => $id, 'status' => 'draft']);

        $this->repositoryMock
            ->expects($this->once())
            ->method('updateStatus')
            ->with($id, 'approved')
            ->willReturn(true);

        $this->auditLogMock
            ->expects($this->once())
            ->method('log');

        // Act
        $result = $this->service->approveKurikulum($id, $userId);

        // Assert
        $this->assertTrue($result);
    }

    public function testApproveKurikulumReturnsFalseWhenNotFound(): void
    {
        // Arrange
        $this->repositoryMock
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        // Act
        $result = $this->service->approveKurikulum(999, 1);

        // Assert
        $this->assertFalse($result);
    }

    public function testValidateKurikulumDataReturnsTrueForValidData(): void
    {
        // Arrange
        $validData = [
            'id_prodi' => 'TIF',
            'kode_kurikulum' => 'K2024',
            'nama_kurikulum' => 'Kurikulum OBE 2024',
            'tahun_berlaku' => 2024
        ];

        // Act
        $result = $this->service->validateKurikulumData($validData);

        // Assert
        $this->assertTrue($result);
    }

    public function testValidateKurikulumDataReturnsFalseForInvalidData(): void
    {
        // Arrange - missing required field
        $invalidData = [
            'id_prodi' => 'TIF',
            'kode_kurikulum' => 'K2024'
            // missing nama_kurikulum and tahun_berlaku
        ];

        // Act
        $result = $this->service->validateKurikulumData($invalidData);

        // Assert
        $this->assertFalse($result);
    }
}
