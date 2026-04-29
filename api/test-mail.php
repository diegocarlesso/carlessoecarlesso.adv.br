<?php
/**
 * api/test-mail.php — Teste e diagnóstico de SMTP.
 *
 * Acesso: somente admin logado. Tenta enviar um e-mail teste e mostra
 * EXATAMENTE o que aconteceu (config lida do .env, erro do PHPMailer
 * em texto cru, etc.). Use isso pra diagnosticar quando o form de
 * contato salva no DB mas o e-mail não chega.
 *
 * URL: https://seudominio/api/test-mail.php?to=seu-email@exemplo.com
 *
 * REMOVA este arquivo após confirmar que SMTP funciona — ele expõe
 * informações sobre a config do servidor.
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    http_response_code(403);
    die('Apenas admins.');
}

header('Content-Type: text/plain; charset=utf-8');

$to = $_GET['to'] ?? Auth::user()['email'] ?? null;
if (!$to) {
    die("Use: /api/test-mail.php?to=email@exemplo.com\n");
}

echo "═══════════════════════════════════════════════════════\n";
echo "  TESTE DE ENVIO SMTP — Carlesso CMS\n";
echo "═══════════════════════════════════════════════════════\n\n";

// 1. Lê config do .env
echo "[1] Configuração lida do .env:\n";
foreach (['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_ENCRYPTION',
          'SMTP_FROM_EMAIL', 'SMTP_FROM_NAME', 'CONTACT_FORM_TO'] as $key) {
    $val = env($key, '(não setado)');
    if ($key === 'SMTP_PASS') {
        $val = $val !== '(não setado)' ? '(definido, ' . strlen((string)$val) . ' chars)' : '(não setado)';
    }
    echo "    " . str_pad($key, 18) . " = $val\n";
}
$smtpPass = env('SMTP_PASS', '');
echo "    " . str_pad('SMTP_PASS', 18) . " = " . ($smtpPass ? '(definido, ' . strlen($smtpPass) . ' chars)' : '(VAZIO ⚠)') . "\n\n";

// 2. Verifica PHPMailer instalado
echo "[2] PHPMailer:\n";
$phpmailerLoaded = class_exists('\PHPMailer\PHPMailer\PHPMailer');
echo "    Classe \\PHPMailer\\PHPMailer\\PHPMailer existe: " . ($phpmailerLoaded ? '✓ SIM' : '✗ NÃO (rode composer install + suba vendor/)') . "\n";
$autoload = (defined('VENDOR_PATH') ? VENDOR_PATH : (BASE_PATH . '/vendor')) . '/autoload.php';
echo "    vendor/autoload.php em '$autoload': " . (is_file($autoload) ? '✓ presente' : '✗ AUSENTE') . "\n\n";

// 3. Verifica conectividade ao host SMTP
echo "[3] Conectividade ao SMTP:\n";
$host = env('SMTP_HOST', '');
$port = (int) env('SMTP_PORT', 465);
if (!$host) {
    echo "    SMTP_HOST não setado no .env — vai usar mail() nativo (provavelmente bloqueado pela Hostinger).\n\n";
} else {
    $err = '';
    $errno = 0;
    echo "    Tentando abrir conexão TCP para $host:$port (timeout 5s)... ";
    $sock = @fsockopen(($port === 465 ? 'ssl://' : '') . $host, $port, $errno, $err, 5);
    if ($sock) {
        echo "✓ conectou\n";
        $banner = @fgets($sock, 256);
        echo "    Banner do servidor: " . trim((string) $banner) . "\n";
        fclose($sock);
    } else {
        echo "✗ FALHOU\n    Erro: [$errno] $err\n";
        echo "    → Se for 'Connection refused' ou timeout, a porta/host está errado ou Hostinger bloqueia.\n";
    }
    echo "\n";
}

// 4. Tenta enviar
echo "[4] Enviando e-mail teste para: $to\n";

$subject = '[TESTE] Carlesso CMS — diagnóstico SMTP — ' . date('d/m/Y H:i:s');
$body    = "<p>Este é um e-mail teste enviado pelo diagnóstico SMTP do CMS.</p>"
         . "<p>Se você recebeu, significa que o SMTP está funcionando corretamente.</p>"
         . "<p>Enviado por: " . htmlspecialchars(Auth::user()['username'] ?? '?') . "<br>"
         . "Data: " . date('d/m/Y H:i:s') . "<br>"
         . "Host: " . ($_SERVER['HTTP_HOST'] ?? '?') . "</p>";

if (class_exists(\Carlesso\Services\Mail\Mailer::class)) {
    $result = \Carlesso\Services\Mail\Mailer::send($to, $subject, $body, ['is_html' => true]);
    echo "    Resultado: " . ($result['success'] ? '✓ ENVIADO' : '✗ FALHOU') . "\n";
    echo "    Método:    " . $result['method'] . "\n";
    if ($result['error']) {
        echo "    Erro:      " . $result['error'] . "\n";
    }
} else {
    echo "    ✗ Carlesso\\Services\\Mail\\Mailer não está disponível.\n";
    echo "      Verifique se src/Services/Mail/Mailer.php foi subido.\n";
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "Verifique a caixa de entrada de $to nos próximos minutos.\n";
echo "Não esquece de DELETAR este arquivo após o teste.\n";
