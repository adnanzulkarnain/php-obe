<?php

namespace App\Utils;

use Mpdf\Mpdf;

/**
 * PDF Exporter
 * Handles PDF generation using mPDF
 */
class PDFExporter
{
    private Mpdf $mpdf;

    public function __construct(array $config = [])
    {
        $defaultConfig = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_header' => 10,
            'margin_footer' => 10
        ];

        $config = array_merge($defaultConfig, $config);

        $this->mpdf = new Mpdf($config);
    }

    /**
     * Set document title
     */
    public function setTitle(string $title): void
    {
        $this->mpdf->SetTitle($title);
    }

    /**
     * Set document author
     */
    public function setAuthor(string $author): void
    {
        $this->mpdf->SetAuthor($author);
    }

    /**
     * Set header
     */
    public function setHeader(string $header): void
    {
        $this->mpdf->SetHeader($header);
    }

    /**
     * Set footer
     */
    public function setFooter(string $footer = '{PAGENO}'): void
    {
        $this->mpdf->SetFooter($footer);
    }

    /**
     * Write HTML content
     */
    public function writeHTML(string $html): void
    {
        $this->mpdf->WriteHTML($html);
    }

    /**
     * Output PDF to browser
     */
    public function output(string $filename = 'document.pdf', string $dest = 'I'): void
    {
        // Dest options:
        // I: Send to browser inline
        // D: Force download
        // F: Save to file
        // S: Return as string
        $this->mpdf->Output($filename, $dest);
    }

    /**
     * Save PDF to file
     */
    public function save(string $filepath): void
    {
        $this->mpdf->Output($filepath, 'F');
    }

    /**
     * Export RPS to PDF
     */
    public static function exportRPS(array $rpsData): string
    {
        $exporter = new self();
        $exporter->setTitle('RPS - ' . $rpsData['nama_mk']);
        $exporter->setAuthor('OBE System');

        $html = self::generateRPSTemplate($rpsData);
        $exporter->writeHTML($html);

        $filename = 'RPS_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $rpsData['kode_mk']) . '.pdf';

        // Save to temporary location
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        $exporter->save($tempPath);

        return $tempPath;
    }

    /**
     * Export analytics report to PDF
     */
    public static function exportAnalyticsReport(array $reportData): string
    {
        $exporter = new self();
        $exporter->setTitle($reportData['title'] ?? 'Analytics Report');
        $exporter->setAuthor('OBE System');
        $exporter->setFooter('Generated on {DATE j-m-Y} | Page {PAGENO} of {nb}');

        $html = self::generateAnalyticsTemplate($reportData);
        $exporter->writeHTML($html);

        $filename = 'Report_' . date('Y-m-d_His') . '.pdf';
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        $exporter->save($tempPath);

        return $tempPath;
    }

    /**
     * Generate RPS HTML template
     */
    private static function generateRPSTemplate(array $data): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        h1 { text-align: center; color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 20px; border-bottom: 1px solid #bdc3c7; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #3498db; color: white; font-weight: bold; }
        .info-table td:first-child { font-weight: bold; width: 30%; background-color: #ecf0f1; }
    </style>
</head>
<body>
    <h1>RENCANA PEMBELAJARAN SEMESTER (RPS)</h1>

    <h2>Informasi Mata Kuliah</h2>
    <table class="info-table">
        <tr><td>Kode MK</td><td>{$data['kode_mk']}</td></tr>
        <tr><td>Nama MK</td><td>{$data['nama_mk']}</td></tr>
        <tr><td>SKS</td><td>{$data['sks']}</td></tr>
        <tr><td>Semester</td><td>{$data['semester']}</td></tr>
        <tr><td>Prasyarat</td><td>{$data['prasyarat']}</td></tr>
    </table>

    <h2>Deskripsi Mata Kuliah</h2>
    <p>{$data['deskripsi']}</p>

    <h2>Capaian Pembelajaran (CPL)</h2>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Deskripsi CPL</th>
            </tr>
        </thead>
        <tbody>
HTML;

        foreach ($data['cpl'] ?? [] as $cpl) {
            $html .= "<tr><td>{$cpl['kode_cpl']}</td><td>{$cpl['deskripsi']}</td></tr>";
        }

        $html .= <<<HTML
        </tbody>
    </table>

    <h2>Capaian Pembelajaran Mata Kuliah (CPMK)</h2>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Deskripsi CPMK</th>
            </tr>
        </thead>
        <tbody>
HTML;

        foreach ($data['cpmk'] ?? [] as $cpmk) {
            $html .= "<tr><td>{$cpmk['kode_cpmk']}</td><td>{$cpmk['deskripsi']}</td></tr>";
        }

        $html .= <<<HTML
        </tbody>
    </table>

    <h2>Rencana Pembelajaran</h2>
    <table>
        <thead>
            <tr>
                <th>Minggu</th>
                <th>Materi</th>
                <th>Metode</th>
                <th>CPMK</th>
            </tr>
        </thead>
        <tbody>
HTML;

        foreach ($data['rencana_mingguan'] ?? [] as $minggu) {
            $html .= "<tr>";
            $html .= "<td>{$minggu['minggu_ke']}</td>";
            $html .= "<td>{$minggu['materi']}</td>";
            $html .= "<td>{$minggu['metode']}</td>";
            $html .= "<td>{$minggu['cpmk']}</td>";
            $html .= "</tr>";
        }

        $html .= <<<HTML
        </tbody>
    </table>

    <div style="margin-top: 40px;">
        <p>Disusun oleh: {$data['dosen_pengampu']}</p>
        <p>Tanggal: {$data['tanggal']}</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Generate Analytics HTML template
     */
    private static function generateAnalyticsTemplate(array $data): string
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        h1 { text-align: center; color: #2c3e50; }
        h2 { color: #34495e; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #3498db; color: white; }
        .summary { background-color: #ecf0f1; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .metric { display: inline-block; margin: 10px 20px; }
        .metric-value { font-size: 24pt; font-weight: bold; color: #3498db; }
        .metric-label { font-size: 10pt; color: #7f8c8d; }
    </style>
</head>
<body>
    <h1>{$data['title']}</h1>
    <p style="text-align: center; color: #7f8c8d;">Generated on {$data['date']}</p>
HTML;

        if (isset($data['summary'])) {
            $html .= '<div class="summary"><h2>Summary</h2>';
            foreach ($data['summary'] as $key => $value) {
                $html .= "<div class='metric'>";
                $html .= "<div class='metric-value'>$value</div>";
                $html .= "<div class='metric-label'>$key</div>";
                $html .= "</div>";
            }
            $html .= '</div>';
        }

        if (isset($data['tables'])) {
            foreach ($data['tables'] as $table) {
                $html .= "<h2>{$table['title']}</h2><table><thead><tr>";

                foreach ($table['columns'] as $column) {
                    $html .= "<th>$column</th>";
                }

                $html .= "</tr></thead><tbody>";

                foreach ($table['data'] as $row) {
                    $html .= "<tr>";
                    foreach ($row as $cell) {
                        $html .= "<td>$cell</td>";
                    }
                    $html .= "</tr>";
                }

                $html .= "</tbody></table>";
            }
        }

        $html .= '</body></html>';

        return $html;
    }
}
