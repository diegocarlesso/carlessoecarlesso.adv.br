<?php
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireCan('settings.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $fields = ['site_titulo','telefone','email_contato','endereco','horario',
               'instagram','facebook','linkedin','whatsapp',
               'mapa_lat','mapa_lng','mapa_embed_html'];
    foreach ($fields as $f) {
        // mapa_embed_html aceita HTML (iframe do Google Maps), não filtra
        $val = $f === 'mapa_embed_html' ? trim($_POST[$f] ?? '') : trim($_POST[$f] ?? '');
        setConfig($f, $val);
    }
    flash('success', 'Configurações salvas com sucesso.');
    header('Location: /admin/configs.php');
    exit;
}

$pageTitle  = 'Configurações';
$breadcrumb = ['Configurações' => ''];

include __DIR__ . '/includes/header.php';
?>

<form method="POST">
  <?= CSRF::field() ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

    <!-- Geral -->
    <div class="card">
      <div class="card-header"><h3>⚙️ Informações Gerais</h3></div>
      <div class="card-body form-grid">
        <div class="form-group full">
          <label class="form-label">Título do Site</label>
          <input class="form-input" type="text" name="site_titulo"
                 value="<?= e(getConfig('site_titulo')) ?>">
        </div>
        <div class="form-group full">
          <label class="form-label">Endereço</label>
          <input class="form-input" type="text" name="endereco"
                 value="<?= e(getConfig('endereco')) ?>"
                 placeholder="São Miguel do Oeste/SC">
        </div>
        <div class="form-group">
          <label class="form-label">Telefone</label>
          <input class="form-input" type="text" name="telefone"
                 value="<?= e(getConfig('telefone')) ?>"
                 placeholder="(49) 3621-2254">
        </div>
        <div class="form-group">
          <label class="form-label">E-mail de Contato</label>
          <input class="form-input" type="email" name="email_contato"
                 value="<?= e(getConfig('email_contato')) ?>">
        </div>
      </div>
    </div>

    <!-- Redes Sociais -->
    <div class="card">
      <div class="card-header"><h3>🌐 Redes Sociais</h3></div>
      <div class="card-body form-grid">
        <div class="form-group full">
          <label class="form-label">Instagram</label>
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:1.1rem">📸</span>
            <input class="form-input" type="url" name="instagram"
                   value="<?= e(getConfig('instagram')) ?>"
                   placeholder="https://www.instagram.com/perfil/">
          </div>
        </div>
        <div class="form-group full">
          <label class="form-label">Facebook</label>
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:1.1rem">📘</span>
            <input class="form-input" type="url" name="facebook"
                   value="<?= e(getConfig('facebook')) ?>"
                   placeholder="https://www.facebook.com/pagina">
          </div>
        </div>
        <div class="form-group full">
          <label class="form-label">LinkedIn</label>
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:1.1rem">💼</span>
            <input class="form-input" type="url" name="linkedin"
                   value="<?= e(getConfig('linkedin')) ?>"
                   placeholder="https://www.linkedin.com/company/...">
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Mapa & Localização -->
  <div class="card" style="margin-top:24px">
    <div class="card-header">
      <h3>🗺️ Mapa &amp; Localização</h3>
      <span style="font-size:.78rem;color:var(--text-muted)">3 modos disponíveis</span>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:18px">

      <div class="form-group">
        <label class="form-label">
          Modo 1 (Recomendado): HTML completo do iframe do Google Maps
        </label>
        <textarea class="form-textarea" name="mapa_embed_html" rows="4"
                  placeholder='<iframe src="https://www.google.com/maps/embed?pb=..." width="600" height="450" ...></iframe>'
                  style="font-family:ui-monospace,monospace;font-size:.78rem"><?= e(getConfig('mapa_embed_html', '')) ?></textarea>
        <div class="form-hint" style="line-height:1.6">
          Vá em <a href="https://maps.google.com" target="_blank">maps.google.com</a> →
          procure o endereço → <strong>Compartilhar → Incorporar mapa</strong> →
          copie o <code>&lt;iframe&gt;</code> inteiro e cole aqui.
          <strong>Sem chave de API necessária.</strong>
        </div>
      </div>

      <hr style="margin:0;border:none;border-top:1px dashed #e5e9ef">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group">
          <label class="form-label">Modo 2: Latitude</label>
          <input class="form-input" type="text" name="mapa_lat"
                 value="<?= e(getConfig('mapa_lat', '-26.7252')) ?>"
                 placeholder="-26.7252">
        </div>
        <div class="form-group">
          <label class="form-label">Modo 2: Longitude</label>
          <input class="form-input" type="text" name="mapa_lng"
                 value="<?= e(getConfig('mapa_lng', '-53.5189')) ?>"
                 placeholder="-53.5189">
        </div>
      </div>
      <div class="form-hint" style="margin-top:-8px">
        Se o campo HTML acima estiver vazio, o sistema usa OpenStreetMap com essas coordenadas
        (sem chave de API).
      </div>
    </div>
  </div>

  <!-- Horário & WhatsApp -->
  <div class="card" style="margin-top:24px">
    <div class="card-header"><h3>⏰ Horário &amp; WhatsApp</h3></div>
    <div class="card-body form-grid">
      <div class="form-group full">
        <label class="form-label">Horário de Atendimento</label>
        <input class="form-input" type="text" name="horario"
               value="<?= e(getConfig('horario', 'Segunda a Sexta, das 8h às 18h')) ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">WhatsApp (apenas números, com DDI 55)</label>
        <input class="form-input" type="text" name="whatsapp"
               value="<?= e(getConfig('whatsapp', '5549936212254')) ?>"
               placeholder="5549999999999">
        <div class="form-hint">Se vazio, usa o telefone fixo.</div>
      </div>
    </div>
  </div>
  <div class="card" style="margin-top:24px">
    <div class="card-header"><h3>ℹ️ Informações do Sistema</h3></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
        <?php
        $infos = [
          'PHP Version'   => PHP_VERSION,
          'Servidor'      => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache',
          'Charset BD'    => 'utf8mb4',
          'CMS Version'   => '1.0.0',
          'Upload Máximo' => ini_get('upload_max_filesize'),
          'Memória Limite'=> ini_get('memory_limit'),
        ];
        foreach ($infos as $k => $v): ?>
        <div style="background:#f8fafc;padding:12px 16px;border-radius:6px;border:1px solid #e5e9ef">
          <div style="font-size:.7rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px"><?= e($k) ?></div>
          <div style="font-size:.88rem;font-weight:600;color:var(--text)"><?= e($v) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div style="display:flex;justify-content:flex-end;margin-top:20px">
    <button type="submit" class="topbar-btn success" style="padding:12px 28px;font-size:.9rem">
      <span class="i i-check-circle"></span> Salvar Configurações
    </button>
  </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
