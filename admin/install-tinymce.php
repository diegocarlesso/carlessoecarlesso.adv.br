<?php
/**
 * admin/install-tinymce.php
 * Baixa e instala TinyMCE 7 community (LGPL) localmente em /assets/tinymce/
 * Sem CDN — uso 100% offline após instalação.
 *
 * Acesso restrito: somente admins logados.
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireCan('settings.manage');

$tinymceDir   = PUBLIC_PATH . '/assets/tinymce';
$installedJs  = $tinymceDir . '/tinymce.min.js';
$installed    = file_exists($installedJs);
$message      = '';
$messageType  = 'info';

// URL oficial: TinyMCE 7 community (LGPL)
$downloadUrl  = 'https://download.tiny.cloud/tinymce/community/tinymce_7.5.1.zip';

// ── Processar instalação ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    CSRF::check();

    if ($_POST['action'] === 'install') {
        try {
            // Aumenta limites para download
            @set_time_limit(300);
            @ini_set('memory_limit', '256M');

            // Cria diretório de destino
            if (!is_dir($tinymceDir)) {
                mkdir($tinymceDir, 0755, true);
            }

            $zipPath = $tinymceDir . '/_tinymce.zip';

            // Download
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'timeout' => 120,
                    'header'  => "User-Agent: CarlessoCMS-Installer\r\n",
                ],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $data = @file_get_contents($downloadUrl, false, $ctx);

            if ($data === false || strlen($data) < 1000000) {
                throw new Exception("Falha no download. O servidor pode estar bloqueando saída externa, ou o arquivo veio corrompido. Faça download manual e suba via FTP — instruções no final desta página.");
            }

            file_put_contents($zipPath, $data);

            // Extração
            if (!class_exists('ZipArchive')) {
                throw new Exception("Extensão PHP 'zip' não disponível neste servidor. Faça upload manual via FTP.");
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new Exception("Não foi possível abrir o ZIP do TinyMCE.");
            }

            // Extrai num diretório temporário
            $tmpExtract = $tinymceDir . '/_tmp';
            if (!is_dir($tmpExtract)) mkdir($tmpExtract, 0755, true);
            $zip->extractTo($tmpExtract);
            $zip->close();
            unlink($zipPath);

            // Move conteúdo de tinymce/ para o destino final
            $sourceFolder = $tmpExtract . '/tinymce';
            if (!is_dir($sourceFolder)) {
                throw new Exception("Estrutura inesperada do ZIP. Faça upload manual.");
            }

            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceFolder, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($items as $item) {
                $rel  = substr($item->getPathname(), strlen($sourceFolder) + 1);
                $dest = $tinymceDir . '/' . $rel;
                if ($item->isDir()) {
                    if (!is_dir($dest)) mkdir($dest, 0755, true);
                } else {
                    rename($item->getPathname(), $dest);
                }
            }
            // Limpa temporário
            removeDirectory($tmpExtract);

            // Verificação final
            if (!file_exists($installedJs)) {
                throw new Exception("Instalação incompleta — tinymce.min.js não encontrado.");
            }

            $message = 'TinyMCE instalado com sucesso! Já pode usar normalmente o editor de páginas e produções.';
            $messageType = 'success';
            $installed = true;

        } catch (Exception $ex) {
            $message = 'Erro: ' . $ex->getMessage();
            $messageType = 'error';
        }
    }

    if ($_POST['action'] === 'uninstall' && $installed) {
        removeDirectory($tinymceDir);
        mkdir($tinymceDir, 0755, true);
        $message = 'TinyMCE removido.';
        $messageType = 'success';
        $installed = false;
    }
}

function removeDirectory(string $dir): void {
    if (!is_dir($dir)) return;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

$pageTitle  = 'Instalar TinyMCE';
$breadcrumb = ['Configurações' => '/admin/configs.php', 'Instalar TinyMCE' => ''];

include __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h3>Editor TinyMCE — Instalação Local</h3>
    <span class="badge <?= $installed ? 'badge-green' : 'badge-yellow' ?>">
      <?= $installed ? '✓ Instalado' : '⚠ Não instalado' ?>
    </span>
  </div>

  <div class="card-body">
    <?php if ($message): ?>
      <div class="alert alert-<?= e($messageType) ?>" style="margin-bottom:20px">
        <?= e($message) ?>
      </div>
    <?php endif; ?>

    <p style="margin-bottom:18px;color:var(--text-muted)">
      O TinyMCE é o editor rich-text usado para criar conteúdo das páginas e das produções.
      Ele <strong>não é incluído no zip por questão de licença e tamanho</strong> — instale localmente
      com 1 clique abaixo, ou faça upload manual via FTP.
    </p>

    <?php if (!$installed): ?>

      <h4 style="margin:24px 0 12px">Opção 1: Instalação automática</h4>
      <p style="font-size:.88rem;color:var(--text-muted);margin-bottom:12px">
        Faz download direto do site oficial (<code>download.tiny.cloud</code>) — TinyMCE 7
        Community (LGPL). Requer permissão de saída HTTP no servidor + extensão PHP <code>zip</code>.
      </p>
      <form method="POST" style="margin-bottom:24px">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="install">
        <button type="submit" class="topbar-btn primary">
          <span class="i i-cloud-upload"></span> Instalar TinyMCE 7 (≈ 4 MB)
        </button>
      </form>

      <h4 style="margin:32px 0 12px">Opção 2: Instalação manual (FTP)</h4>
      <ol style="font-size:.9rem;line-height:1.8;color:var(--text);padding-left:24px">
        <li>Baixe TinyMCE 7 community em
          <a href="https://www.tiny.cloud/get-tiny/self-hosted/" target="_blank" rel="noopener">
            tiny.cloud/get-tiny/self-hosted
          </a>
        </li>
        <li>Extraia o ZIP. Você terá uma pasta <code>tinymce/</code></li>
        <li>Suba <strong>todo o conteúdo</strong> dessa pasta para
          <code>/public_html/assets/tinymce/</code> via FTP/SFTP/cPanel
        </li>
        <li>O caminho final deve ser <code>/public_html/assets/tinymce/tinymce.min.js</code></li>
        <li>Recarregue esta página — deve aparecer ✓ Instalado</li>
      </ol>

    <?php else: ?>

      <p style="margin-bottom:16px;color:#10b981">
        <strong>✓ Tudo certo!</strong> Os editores de Páginas e Produções já usam a versão local.
      </p>

      <p style="margin-bottom:24px;font-size:.85rem;color:var(--text-muted)">
        Localização: <code>/assets/tinymce/tinymce.min.js</code>
        (<?= bytesFormat(filesize($installedJs)) ?>)
      </p>

      <form method="POST">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="uninstall">
        <button type="submit" class="topbar-btn danger" data-confirm="Remover TinyMCE? Os editores rich-text deixarão de funcionar até reinstalar.">
          <span class="i i-trash"></span> Desinstalar
        </button>
      </form>

    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
