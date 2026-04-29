<?php
declare(strict_types=1);

namespace Carlesso\Services\Mail;

use Carlesso\Support\Env;

/**
 * Mailer — wrapper de envio de e-mail.
 *
 * Estratégia:
 *   1. Se SMTP_HOST setado no .env → usa PHPMailer via SMTP (preferido — Hostinger
 *      bloqueia mail() na maioria dos planos)
 *   2. Senão, fallback para mail() PHP (best effort — pode falhar silencioso)
 *
 * Configuração no .env:
 *   SMTP_HOST=smtp.hostinger.com
 *   SMTP_PORT=465
 *   SMTP_USER=contato@carlessoecarlesso.adv.br
 *   SMTP_PASS=sua_senha_da_conta_de_email
 *   SMTP_ENCRYPTION=ssl       # ssl (porta 465) ou tls (porta 587)
 *   SMTP_FROM_EMAIL=contato@carlessoecarlesso.adv.br
 *   SMTP_FROM_NAME=Carlesso & Carlesso
 *
 * Hostinger especificamente:
 *   - Crie a conta de e-mail em hPanel → Emails → Email Accounts
 *   - Use AS MESMAS credenciais (e-mail + senha) no .env como SMTP_USER/SMTP_PASS
 *   - Host: smtp.hostinger.com, port: 465, encryption: ssl
 */
final class Mailer
{
    /**
     * Envia e-mail. Retorna ['success' => bool, 'method' => 'smtp'|'mail()', 'error' => ?string].
     *
     * @param string $to       Destinatário
     * @param string $subject  Assunto
     * @param string $body     Corpo do e-mail (texto puro ou HTML — auto-detect)
     * @param array  $options  ['reply_to' => ..., 'reply_name' => ..., 'is_html' => bool]
     */
    public static function send(string $to, string $subject, string $body, array $options = []): array
    {
        $smtpHost = Env::get('SMTP_HOST', '');
        if ($smtpHost) {
            return self::sendSmtp($to, $subject, $body, $options);
        }
        return self::sendNative($to, $subject, $body, $options);
    }

    /**
     * Envio via PHPMailer + SMTP (preferido).
     */
    private static function sendSmtp(string $to, string $subject, string $body, array $options): array
    {
        if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            return [
                'success' => false,
                'method'  => 'smtp',
                'error'   => 'PHPMailer não instalado. Rode "composer install" e suba a pasta vendor/.',
            ];
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = Env::get('SMTP_HOST', 'smtp.hostinger.com');
            $mail->Port       = Env::getInt('SMTP_PORT', 465);
            $mail->SMTPAuth   = true;
            $mail->Username   = Env::get('SMTP_USER', '');
            $mail->Password   = Env::get('SMTP_PASS', '');

            $encryption = strtolower(Env::get('SMTP_ENCRYPTION', 'ssl'));
            if ($encryption === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }

            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';

            $fromEmail = Env::get('SMTP_FROM_EMAIL', $mail->Username);
            $fromName  = Env::get('SMTP_FROM_NAME',  'Carlesso & Carlesso');
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            if (!empty($options['reply_to'])) {
                $mail->addReplyTo(
                    $options['reply_to'],
                    $options['reply_name'] ?? ''
                );
            }

            $mail->Subject = $subject;
            $isHtml = $options['is_html'] ?? self::looksLikeHtml($body);
            if ($isHtml) {
                $mail->isHTML(true);
                $mail->Body    = $body;
                $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body));
            } else {
                $mail->isHTML(false);
                $mail->Body = $body;
            }

            $mail->send();
            return ['success' => true, 'method' => 'smtp', 'error' => null];

        } catch (\Throwable $e) {
            error_log('[Mailer SMTP] ' . $e->getMessage());
            // Fallback para mail() nativo se SMTP falhar
            $native = self::sendNative($to, $subject, $body, $options);
            $native['error'] = 'SMTP falhou (' . $e->getMessage() . '); fallback mail() ' . ($native['success'] ? 'OK' : 'também falhou');
            return $native;
        }
    }

    /**
     * Envio via mail() nativo (fallback).
     */
    private static function sendNative(string $to, string $subject, string $body, array $options): array
    {
        $hostDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $hostDomain = preg_replace('/^www\./', '', $hostDomain);
        $fromAddr   = Env::get('SMTP_FROM_EMAIL', 'noreply@' . $hostDomain);
        $fromName   = Env::get('SMTP_FROM_NAME', 'Carlesso & Carlesso');

        $isHtml = $options['is_html'] ?? self::looksLikeHtml($body);

        $headers  = "From: $fromName <$fromAddr>\r\n";
        if (!empty($options['reply_to'])) {
            $rname = $options['reply_name'] ?? '';
            $headers .= "Reply-To: " . ($rname ? "$rname <{$options['reply_to']}>" : $options['reply_to']) . "\r\n";
        }
        $headers .= "Return-Path: $fromAddr\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: " . ($isHtml ? 'text/html' : 'text/plain') . "; charset=UTF-8\r\n";
        $headers .= "X-Mailer: CarlessoCMS/1.0\r\n";

        $sent = false;
        try {
            $sent = @mail($to, $subject, $body, $headers, '-f' . $fromAddr);
        } catch (\Throwable $e) {
            error_log('[Mailer mail()] ' . $e->getMessage());
        }

        return [
            'success' => (bool) $sent,
            'method'  => 'mail()',
            'error'   => $sent ? null : 'mail() retornou false (provavelmente bloqueado pelo host)',
        ];
    }

    private static function looksLikeHtml(string $s): bool
    {
        return preg_match('/<\/?(p|br|div|html|body|span|a|table)\b/i', $s) === 1;
    }
}
