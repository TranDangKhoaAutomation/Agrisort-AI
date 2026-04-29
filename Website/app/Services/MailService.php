<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Models\Setting;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        if (!class_exists(PHPMailer::class)) {
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            $smtpHost = Setting::get('smtp_host', (string) Config::get('mail.host'));
            $smtpPort = (int) Setting::get('smtp_port', (string) Config::get('mail.port'));
            $smtpUser = Setting::get('smtp_user', (string) Config::get('mail.user'));
            $smtpPass = Setting::get('smtp_pass', (string) Config::get('mail.pass'));
            $smtpFrom = Setting::get('smtp_from', (string) Config::get('mail.from'));

            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = (string) Config::get('mail.secure');
            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($smtpFrom, (string) Config::get('mail.from_name'));
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            $mail->send();
            return true;
        } catch (Exception) {
            return false;
        }
    }
}
