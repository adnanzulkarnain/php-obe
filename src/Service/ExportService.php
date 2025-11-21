<?php

namespace App\Service;

use App\Utils\PDFExporter;
use App\Utils\ExcelExporter;
use App\Repository\RPSRepository;
use App\Repository\KelasRepository;
use App\Repository\MahasiswaRepository;

/**
 * Export Service
 * Handles data export to various formats
 */
class ExportService
{
    private RPSRepository $rpsRepository;
    private KelasRepository $kelasRepository;
    private MahasiswaRepository $mahasiswaRepository;

    public function __construct()
    {
        $this->rpsRepository = new RPSRepository();
        $this->kelasRepository = new KelasRepository();
        $this->mahasiswaRepository = new MahasiswaRepository();
    }

    /**
     * Export RPS to PDF
     */
    public function exportRPSToPDF(int $idRPS): ?string
    {
        $rps = $this->rpsRepository->findById($idRPS);

        if (!$rps) {
            return null;
        }

        // Prepare RPS data for export
        $rpsData = [
            'kode_mk' => $rps['kode_mk'],
            'nama_mk' => $rps['nama_mk'],
            'sks' => $rps['sks'],
            'semester' => $rps['semester'],
            'prasyarat' => $rps['prasyarat'] ?? '-',
            'deskripsi' => $rps['deskripsi'] ?? '-',
            'cpl' => $rps['cpl'] ?? [],
            'cpmk' => $rps['cpmk'] ?? [],
            'rencana_mingguan' => $rps['rencana_mingguan'] ?? [],
            'dosen_pengampu' => $rps['dosen_pengampu'] ?? '-',
            'tanggal' => date('d F Y')
        ];

        return PDFExporter::exportRPS($rpsData);
    }

    /**
     * Export analytics report to PDF
     */
    public function exportAnalyticsToPDF(array $reportData): string
    {
        return PDFExporter::exportAnalyticsReport($reportData);
    }

    /**
     * Export analytics report to Excel
     */
    public function exportAnalyticsToExcel(array $reportData): string
    {
        return ExcelExporter::exportAnalytics($reportData);
    }

    /**
     * Export nilai to Excel
     */
    public function exportNilaiToExcel(int $idKelas): ?string
    {
        $kelas = $this->kelasRepository->findById($idKelas);

        if (!$kelas) {
            return null;
        }

        // Get nilai data (simplified - you should implement proper query)
        $nilaiData = [
            'mata_kuliah' => $kelas['nama_mk'],
            'kelas' => $kelas['nama_kelas'],
            'semester' => $kelas['semester'],
            'dosen' => $kelas['dosen_pengampu'] ?? '-',
            'data' => [] // Should be populated with actual nilai data
        ];

        return ExcelExporter::exportNilai($nilaiData);
    }

    /**
     * Export mahasiswa list to Excel
     */
    public function exportMahasiswaToExcel(array $filters = []): string
    {
        $mahasiswa = $this->mahasiswaRepository->findAll();

        return ExcelExporter::exportMahasiswaList($mahasiswa);
    }

    /**
     * Export kurikulum comparison to Excel
     */
    public function exportKurikulumComparisonToExcel(array $comparisonData): string
    {
        return ExcelExporter::exportKurikulumComparison($comparisonData);
    }

    /**
     * Get file and prepare for download
     */
    public function prepareDownload(string $filepath, string $filename): ?array
    {
        if (!file_exists($filepath)) {
            return null;
        }

        return [
            'filepath' => $filepath,
            'filename' => $filename,
            'mime_type' => mime_content_type($filepath),
            'size' => filesize($filepath)
        ];
    }

    /**
     * Cleanup temporary file
     */
    public function cleanupTempFile(string $filepath): void
    {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}
