<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\RPSRepository;
use App\Repository\CPMKRepository;
use App\Repository\RencanaMingguanRepository;
use App\Repository\PustakaRepository;
use App\Repository\MediaPembelajaranRepository;

/**
 * RPS Export Service
 * Export RPS to various formats (Markdown, HTML)
 */
class RPSExportService
{
    private RPSRepository $rpsRepo;
    private CPMKRepository $cpmkRepo;
    private RencanaMingguanRepository $mingguRepo;
    private PustakaRepository $pustakaRepo;
    private MediaPembelajaranRepository $mediaRepo;

    public function __construct()
    {
        $this->rpsRepo = new RPSRepository();
        $this->cpmkRepo = new CPMKRepository();
        $this->mingguRepo = new RencanaMingguanRepository();
        $this->pustakaRepo = new PustakaRepository();
        $this->mediaRepo = new MediaPembelajaranRepository();
    }

    /**
     * Export RPS to Markdown format
     */
    public function exportToMarkdown(int $idRps): string
    {
        // Get RPS data
        $rps = $this->rpsRepo->findWithDetails($idRps);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Get related data
        $cpmk = $this->cpmkRepo->findByRPS($idRps);
        $minggu = $this->mingguRepo->findByRPS($idRps);
        $pustaka = $this->pustakaRepo->findByRPS($idRps);
        $media = $this->mediaRepo->findByRPS($idRps);

        // Generate markdown
        $md = "# RENCANA PEMBELAJARAN SEMESTER (RPS)\n\n";

        // Header Info
        $md .= "## Informasi Mata Kuliah\n\n";
        $md .= "| Field | Value |\n";
        $md .= "|-------|-------|\n";
        $md .= "| **Mata Kuliah** | {$rps['nama_mk']} |\n";
        $md .= "| **Kode** | {$rps['kode_mk']} |\n";
        $md .= "| **SKS** | {$rps['sks']} |\n";
        $md .= "| **Semester** | {$rps['semester']} |\n";
        $md .= "| **Program Studi** | {$rps['nama_prodi']} |\n";
        $md .= "| **Kurikulum** | {$rps['nama_kurikulum']} |\n\n";

        // Deskripsi
        if (!empty($rps['deskripsi_mk'])) {
            $md .= "## Deskripsi Mata Kuliah\n\n";
            $md .= $rps['deskripsi_mk'] . "\n\n";
        }

        // CPMK
        if (!empty($cpmk)) {
            $md .= "## Capaian Pembelajaran Mata Kuliah (CPMK)\n\n";
            foreach ($cpmk as $idx => $c) {
                $md .= ($idx + 1) . ". **{$c['kode_cpmk']}**: {$c['deskripsi']}\n";
            }
            $md .= "\n";
        }

        // Rencana Mingguan
        if (!empty($minggu)) {
            $md .= "## Rencana Pembelajaran Mingguan\n\n";
            foreach ($minggu as $m) {
                $md .= "### Minggu {$m['minggu_ke']}\n\n";

                if (!empty($m['materi'])) {
                    $materi = json_decode($m['materi'], true);
                    if ($materi) {
                        $md .= "**Materi:** " . (is_array($materi) ? implode(', ', $materi) : $materi) . "\n\n";
                    }
                }

                if (!empty($m['pengalaman_belajar'])) {
                    $md .= "**Pengalaman Belajar:** {$m['pengalaman_belajar']}\n\n";
                }

                if (!empty($m['estimasi_waktu_menit'])) {
                    $md .= "**Estimasi Waktu:** {$m['estimasi_waktu_menit']} menit\n\n";
                }
            }
        }

        // Pustaka
        if (!empty($pustaka)) {
            $md .= "## Referensi\n\n";

            $pustakaUtama = array_filter($pustaka, fn($p) => $p['jenis'] === 'utama');
            if (!empty($pustakaUtama)) {
                $md .= "### Pustaka Utama\n\n";
                foreach ($pustakaUtama as $idx => $p) {
                    $md .= ($idx + 1) . ". {$p['referensi']}";
                    if (!empty($p['penulis'])) $md .= " - {$p['penulis']}";
                    if (!empty($p['tahun'])) $md .= " ({$p['tahun']})";
                    $md .= "\n";
                }
                $md .= "\n";
            }

            $pustakaPendukung = array_filter($pustaka, fn($p) => $p['jenis'] === 'pendukung');
            if (!empty($pustakaPendukung)) {
                $md .= "### Pustaka Pendukung\n\n";
                foreach ($pustakaPendukung as $idx => $p) {
                    $md .= ($idx + 1) . ". {$p['referensi']}";
                    if (!empty($p['penulis'])) $md .= " - {$p['penulis']}";
                    if (!empty($p['tahun'])) $md .= " ({$p['tahun']})";
                    $md .= "\n";
                }
                $md .= "\n";
            }
        }

        // Media Pembelajaran
        if (!empty($media)) {
            $md .= "## Media Pembelajaran\n\n";
            foreach ($media as $m) {
                $md .= "- **{$m['nama']}** ({$m['kategori']})";
                if (!empty($m['deskripsi'])) {
                    $md .= ": {$m['deskripsi']}";
                }
                $md .= "\n";
            }
            $md .= "\n";
        }

        $md .= "---\n\n";
        $md .= "*Dokumen ini digenerate otomatis dari Sistem Informasi Kurikulum OBE*\n";
        $md .= "*Tanggal: " . date('d F Y H:i:s') . "*\n";

        return $md;
    }

    /**
     * Export RPS to HTML format
     */
    public function exportToHTML(int $idRps): string
    {
        // Get RPS data
        $rps = $this->rpsRepo->findWithDetails($idRps);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Get related data
        $cpmk = $this->cpmkRepo->findByRPS($idRps);
        $minggu = $this->mingguRepo->findByRPS($idRps);
        $pustaka = $this->pustakaRepo->findByRPS($idRps);
        $media = $this->mediaRepo->findByRPS($idRps);

        // Generate HTML
        $html = "<!DOCTYPE html>\n<html lang='id'>\n<head>\n";
        $html .= "<meta charset='UTF-8'>\n";
        $html .= "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
        $html .= "<title>RPS - {$rps['nama_mk']}</title>\n";
        $html .= "<style>\n";
        $html .= "body { font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; line-height: 1.6; }\n";
        $html .= "h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }\n";
        $html .= "h2 { color: #34495e; margin-top: 30px; border-bottom: 2px solid #95a5a6; padding-bottom: 5px; }\n";
        $html .= "h3 { color: #7f8c8d; }\n";
        $html .= "table { width: 100%; border-collapse: collapse; margin: 20px 0; }\n";
        $html .= "th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }\n";
        $html .= "th { background-color: #3498db; color: white; }\n";
        $html .= "tr:nth-child(even) { background-color: #f2f2f2; }\n";
        $html .= ".week-card { background: #ecf0f1; padding: 15px; margin: 10px 0; border-radius: 5px; }\n";
        $html .= ".footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #ccc; color: #7f8c8d; font-size: 0.9em; text-align: center; }\n";
        $html .= "</style>\n</head>\n<body>\n";

        $html .= "<h1>RENCANA PEMBELAJARAN SEMESTER (RPS)</h1>\n";

        // Header Info
        $html .= "<h2>Informasi Mata Kuliah</h2>\n";
        $html .= "<table>\n";
        $html .= "<tr><th>Mata Kuliah</th><td>{$rps['nama_mk']}</td></tr>\n";
        $html .= "<tr><th>Kode</th><td>{$rps['kode_mk']}</td></tr>\n";
        $html .= "<tr><th>SKS</th><td>{$rps['sks']}</td></tr>\n";
        $html .= "<tr><th>Semester</th><td>{$rps['semester']}</td></tr>\n";
        $html .= "<tr><th>Program Studi</th><td>{$rps['nama_prodi']}</td></tr>\n";
        $html .= "<tr><th>Kurikulum</th><td>{$rps['nama_kurikulum']}</td></tr>\n";
        $html .= "</table>\n";

        // Deskripsi
        if (!empty($rps['deskripsi_mk'])) {
            $html .= "<h2>Deskripsi Mata Kuliah</h2>\n";
            $html .= "<p>" . nl2br(htmlspecialchars($rps['deskripsi_mk'])) . "</p>\n";
        }

        // CPMK
        if (!empty($cpmk)) {
            $html .= "<h2>Capaian Pembelajaran Mata Kuliah (CPMK)</h2>\n<ol>\n";
            foreach ($cpmk as $c) {
                $html .= "<li><strong>{$c['kode_cpmk']}</strong>: {$c['deskripsi']}</li>\n";
            }
            $html .= "</ol>\n";
        }

        // Rencana Mingguan
        if (!empty($minggu)) {
            $html .= "<h2>Rencana Pembelajaran Mingguan</h2>\n";
            foreach ($minggu as $m) {
                $html .= "<div class='week-card'>\n";
                $html .= "<h3>Minggu {$m['minggu_ke']}</h3>\n";

                if (!empty($m['materi'])) {
                    $materi = json_decode($m['materi'], true);
                    if ($materi) {
                        $materiText = is_array($materi) ? implode(', ', $materi) : $materi;
                        $html .= "<p><strong>Materi:</strong> {$materiText}</p>\n";
                    }
                }

                if (!empty($m['pengalaman_belajar'])) {
                    $html .= "<p><strong>Pengalaman Belajar:</strong> {$m['pengalaman_belajar']}</p>\n";
                }

                if (!empty($m['estimasi_waktu_menit'])) {
                    $html .= "<p><strong>Estimasi Waktu:</strong> {$m['estimasi_waktu_menit']} menit</p>\n";
                }

                $html .= "</div>\n";
            }
        }

        // Pustaka
        if (!empty($pustaka)) {
            $html .= "<h2>Referensi</h2>\n";

            $pustakaUtama = array_filter($pustaka, fn($p) => $p['jenis'] === 'utama');
            if (!empty($pustakaUtama)) {
                $html .= "<h3>Pustaka Utama</h3>\n<ol>\n";
                foreach ($pustakaUtama as $p) {
                    $html .= "<li>{$p['referensi']}";
                    if (!empty($p['penulis'])) $html .= " - {$p['penulis']}";
                    if (!empty($p['tahun'])) $html .= " ({$p['tahun']})";
                    $html .= "</li>\n";
                }
                $html .= "</ol>\n";
            }

            $pustakaPendukung = array_filter($pustaka, fn($p) => $p['jenis'] === 'pendukung');
            if (!empty($pustakaPendukung)) {
                $html .= "<h3>Pustaka Pendukung</h3>\n<ol>\n";
                foreach ($pustakaPendukung as $p) {
                    $html .= "<li>{$p['referensi']}";
                    if (!empty($p['penulis'])) $html .= " - {$p['penulis']}";
                    if (!empty($p['tahun'])) $html .= " ({$p['tahun']})";
                    $html .= "</li>\n";
                }
                $html .= "</ol>\n";
            }
        }

        // Media Pembelajaran
        if (!empty($media)) {
            $html .= "<h2>Media Pembelajaran</h2>\n<ul>\n";
            foreach ($media as $m) {
                $html .= "<li><strong>{$m['nama']}</strong> ({$m['kategori']})";
                if (!empty($m['deskripsi'])) {
                    $html .= ": {$m['deskripsi']}";
                }
                $html .= "</li>\n";
            }
            $html .= "</ul>\n";
        }

        $html .= "<div class='footer'>\n";
        $html .= "<p><em>Dokumen ini digenerate otomatis dari Sistem Informasi Kurikulum OBE</em></p>\n";
        $html .= "<p><em>Tanggal: " . date('d F Y H:i:s') . "</em></p>\n";
        $html .= "</div>\n";

        $html .= "</body>\n</html>";

        return $html;
    }

    /**
     * Export RPS to JSON format (for API)
     */
    public function exportToJSON(int $idRps): array
    {
        $rps = $this->rpsRepo->findWithDetails($idRps);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        return [
            'rps' => $rps,
            'cpmk' => $this->cpmkRepo->findByRPS($idRps),
            'rencana_mingguan' => $this->mingguRepo->findByRPS($idRps),
            'pustaka' => $this->pustakaRepo->findByRPS($idRps),
            'media_pembelajaran' => $this->mediaRepo->findByRPS($idRps),
            'exported_at' => date('Y-m-d H:i:s')
        ];
    }
}
