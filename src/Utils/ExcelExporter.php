<?php

namespace App\Utils;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Excel Exporter
 * Handles Excel generation using PhpSpreadsheet
 */
class ExcelExporter
{
    private Spreadsheet $spreadsheet;
    private $activeSheet;
    private int $currentRow = 1;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->activeSheet = $this->spreadsheet->getActiveSheet();
    }

    /**
     * Set sheet title
     */
    public function setSheetTitle(string $title): void
    {
        $this->activeSheet->setTitle($title);
    }

    /**
     * Set cell value
     */
    public function setCellValue(string $cell, $value): void
    {
        $this->activeSheet->setCellValue($cell, $value);
    }

    /**
     * Write header row
     */
    public function writeHeader(array $headers, int $row = null): void
    {
        if ($row !== null) {
            $this->currentRow = $row;
        }

        $col = 'A';
        foreach ($headers as $header) {
            $cell = $col . $this->currentRow;
            $this->activeSheet->setCellValue($cell, $header);

            // Style header
            $this->activeSheet->getStyle($cell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);

            $col++;
        }

        $this->currentRow++;
    }

    /**
     * Write data rows
     */
    public function writeData(array $data): void
    {
        foreach ($data as $row) {
            $col = 'A';
            foreach ($row as $value) {
                $this->activeSheet->setCellValue($col . $this->currentRow, $value);
                $col++;
            }
            $this->currentRow++;
        }
    }

    /**
     * Write data with headers
     */
    public function writeTable(array $headers, array $data, int $startRow = 1): void
    {
        $this->currentRow = $startRow;
        $this->writeHeader($headers);
        $this->writeData($data);

        // Apply borders to table
        $lastCol = chr(ord('A') + count($headers) - 1);
        $lastRow = $this->currentRow - 1;

        $this->activeSheet->getStyle("A{$startRow}:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Auto-size columns
        foreach (range('A', $lastCol) as $col) {
            $this->activeSheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Add title
     */
    public function addTitle(string $title, int $row = 1): void
    {
        $this->activeSheet->setCellValue("A{$row}", $title);
        $this->activeSheet->getStyle("A{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16
            ]
        ]);
    }

    /**
     * Merge cells
     */
    public function mergeCells(string $range): void
    {
        $this->activeSheet->mergeCells($range);
    }

    /**
     * Save to file
     */
    public function save(string $filepath): void
    {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($filepath);
    }

    /**
     * Output to browser
     */
    public function output(string $filename = 'export.xlsx'): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($this->spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export analytics to Excel
     */
    public static function exportAnalytics(array $reportData): string
    {
        $exporter = new self();
        $exporter->setSheetTitle('Analytics Report');

        $row = 1;

        // Title
        $exporter->addTitle($reportData['title'] ?? 'Analytics Report', $row);
        $exporter->mergeCells("A{$row}:E{$row}");
        $row += 2;

        // Summary
        if (isset($reportData['summary'])) {
            $exporter->setCellValue("A{$row}", 'Summary');
            $exporter->activeSheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            foreach ($reportData['summary'] as $key => $value) {
                $exporter->setCellValue("A{$row}", $key);
                $exporter->setCellValue("B{$row}", $value);
                $row++;
            }

            $row += 2;
        }

        // Data tables
        if (isset($reportData['tables'])) {
            foreach ($reportData['tables'] as $table) {
                $exporter->setCellValue("A{$row}", $table['title']);
                $exporter->activeSheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;

                $exporter->writeTable($table['columns'], $table['data'], $row);
                $row = $exporter->currentRow + 2;
            }
        }

        // Save to temp file
        $filename = 'Analytics_' . date('Y-m-d_His') . '.xlsx';
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        $exporter->save($tempPath);

        return $tempPath;
    }

    /**
     * Export nilai to Excel
     */
    public static function exportNilai(array $nilaiData): string
    {
        $exporter = new self();
        $exporter->setSheetTitle('Nilai');

        // Title
        $exporter->addTitle('Daftar Nilai - ' . $nilaiData['mata_kuliah'], 1);
        $exporter->mergeCells('A1:F1');

        // Info
        $exporter->setCellValue('A3', 'Kelas');
        $exporter->setCellValue('B3', $nilaiData['kelas']);
        $exporter->setCellValue('A4', 'Semester');
        $exporter->setCellValue('B4', $nilaiData['semester']);
        $exporter->setCellValue('A5', 'Dosen');
        $exporter->setCellValue('B5', $nilaiData['dosen']);

        // Data table
        $headers = ['NIM', 'Nama', 'Tugas', 'UTS', 'UAS', 'Nilai Akhir', 'Grade'];
        $exporter->writeTable($headers, $nilaiData['data'], 7);

        // Save to temp file
        $filename = 'Nilai_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nilaiData['mata_kuliah']) . '.xlsx';
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        $exporter->save($tempPath);

        return $tempPath;
    }

    /**
     * Export mahasiswa list to Excel
     */
    public static function exportMahasiswaList(array $mahasiswaData): string
    {
        $exporter = new self();
        $exporter->setSheetTitle('Daftar Mahasiswa');

        $exporter->addTitle('Daftar Mahasiswa', 1);
        $exporter->mergeCells('A1:E1');

        $headers = ['NIM', 'Nama', 'Program Studi', 'Angkatan', 'Status'];

        $data = [];
        foreach ($mahasiswaData as $mhs) {
            $data[] = [
                $mhs['nim'],
                $mhs['nama'],
                $mhs['prodi'] ?? '-',
                $mhs['angkatan'] ?? '-',
                $mhs['status'] ?? 'Aktif'
            ];
        }

        $exporter->writeTable($headers, $data, 3);

        $filename = 'Mahasiswa_' . date('Y-m-d_His') . '.xlsx';
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        $exporter->save($tempPath);

        return $tempPath;
    }

    /**
     * Export kurikulum comparison to Excel
     */
    public static function exportKurikulumComparison(array $comparisonData): string
    {
        $exporter = new self();
        $exporter->setSheetTitle('Perbandingan Kurikulum');

        $exporter->addTitle('Perbandingan Kurikulum', 1);
        $exporter->mergeCells('A1:D1');

        $headers = ['Mata Kuliah', 'Kurikulum 1', 'Kurikulum 2', 'Status'];
        $exporter->writeTable($headers, $comparisonData['data'], 3);

        $filename = 'Comparison_' . date('Y-m-d_His') . '.xlsx';
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        $exporter->save($tempPath);

        return $tempPath;
    }
}
