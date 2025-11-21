<?php

namespace App\Utils;

/**
 * Email Helper
 * Handles email sending functionality
 *
 * Note: This is a basic implementation using PHP mail().
 * For production, consider using PHPMailer, SwiftMailer, or Symfony Mailer
 * with SMTP configuration for better reliability.
 */
class EmailHelper
{
    private static ?string $fromEmail = null;
    private static ?string $fromName = null;
    private static bool $enabled = true;

    /**
     * Initialize email configuration
     */
    public static function init(): void
    {
        self::$fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@obe-system.edu';
        self::$fromName = getenv('MAIL_FROM_NAME') ?: 'OBE System';
        self::$enabled = getenv('MAIL_ENABLED') !== 'false';
    }

    /**
     * Send email
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $options Additional options (cc, bcc, replyTo)
     * @return bool Success status
     */
    public static function send(
        string $to,
        string $subject,
        string $body,
        array $options = []
    ): bool {
        if (!self::$enabled) {
            // Email disabled, log and return success for testing
            error_log("[EmailHelper] Email disabled. Would send to: $to, Subject: $subject");
            return true;
        }

        if (self::$fromEmail === null) {
            self::init();
        }

        // Prepare headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::$fromName . ' <' . self::$fromEmail . '>',
        ];

        // Add CC if provided
        if (!empty($options['cc'])) {
            $headers[] = 'Cc: ' . $options['cc'];
        }

        // Add BCC if provided
        if (!empty($options['bcc'])) {
            $headers[] = 'Bcc: ' . $options['bcc'];
        }

        // Add Reply-To if provided
        if (!empty($options['replyTo'])) {
            $headers[] = 'Reply-To: ' . $options['replyTo'];
        }

        // Wrap body in HTML template
        $htmlBody = self::wrapInTemplate($subject, $body);

        // Send email
        $success = mail($to, $subject, $htmlBody, implode("\r\n", $headers));

        if (!$success) {
            error_log("[EmailHelper] Failed to send email to: $to, Subject: $subject");
        }

        return $success;
    }

    /**
     * Send notification email
     */
    public static function sendNotification(
        string $to,
        string $judul,
        string $pesan,
        ?string $link = null
    ): bool {
        $body = "<h2>$judul</h2>";
        $body .= "<p>$pesan</p>";

        if ($link) {
            $body .= "<p><a href='$link' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Lihat Detail</a></p>";
        }

        return self::send($to, $judul, $body);
    }

    /**
     * Send RPS approval notification
     */
    public static function sendRPSApprovalNotification(
        string $to,
        string $dosenName,
        string $mataKuliah,
        string $status,
        ?string $komentar = null
    ): bool {
        $judul = "RPS $mataKuliah - " . ucfirst($status);

        $body = "<p>Yth. $dosenName,</p>";
        $body .= "<p>RPS untuk mata kuliah <strong>$mataKuliah</strong> telah <strong>$status</strong>.</p>";

        if ($komentar) {
            $body .= "<p><strong>Komentar Reviewer:</strong><br>$komentar</p>";
        }

        $body .= "<p>Silakan login ke sistem untuk melihat detail lebih lanjut.</p>";

        return self::send($to, $judul, $body);
    }

    /**
     * Send deadline reminder
     */
    public static function sendDeadlineReminder(
        string $to,
        string $recipientName,
        string $taskName,
        string $deadline
    ): bool {
        $judul = "Pengingat Deadline: $taskName";

        $body = "<p>Yth. $recipientName,</p>";
        $body .= "<p>Ini adalah pengingat bahwa <strong>$taskName</strong> akan berakhir pada:</p>";
        $body .= "<p style='font-size: 18px; color: #dc3545;'><strong>$deadline</strong></p>";
        $body .= "<p>Mohon segera menyelesaikan sebelum deadline berakhir.</p>";

        return self::send($to, $judul, $body);
    }

    /**
     * Wrap content in HTML email template
     */
    private static function wrapInTemplate(string $subject, string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$subject</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; border-radius: 10px; padding: 30px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #007bff; margin: 0;">OBE System</h1>
            <p style="color: #6c757d; margin: 5px 0 0 0;">Sistem Informasi Kurikulum</p>
        </div>

        <div style="background-color: white; border-radius: 8px; padding: 25px;">
            $body
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
            <p style="color: #6c757d; font-size: 12px; margin: 0;">
                Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini.
            </p>
            <p style="color: #6c757d; font-size: 12px; margin: 5px 0 0 0;">
                &copy; 2024 OBE System. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Validate email address
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Enable/disable email sending
     */
    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    /**
     * Check if email is enabled
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }
}
