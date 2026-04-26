<?php
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireCan('appearance.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();

    $settings = [
        'primary_color'   => ['color', trim($_POST['primary_color'] ?? '#527095')],
        'secondary_color' => ['color', trim($_POST['secondary_color'] ?? '#081217')],
        'text_color'      => ['color', trim($_POST['text_color'] ?? '#000000')],
        'heading_font'    => ['font', trim($_POST['heading_font'] ?? "'Hepta Slab', serif")],
        'body_font'       => ['font', trim($_POST['body_font'] ?? "'Open Sans', sans-serif")],
        'logo_text'       => ['text', trim($_POST['logo_text'] ?? 'Carlesso & Carlesso')],
        'footer_text'     => ['text', trim($_POST['footer_text'] ?? 'Todos os direitos reservados.')],
    ];

    foreach ($settings as $key => [$type, $value]) {
        Database::query(
            'INSERT INTO customizations (setting_key, setting_value, setting_type) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE setting_value=?, setting_type=?',
            [$key, $value, $type, $value, $type]
        );
    }

    // Upload logo
    if (!empty($_FILES['logo_file']['name'])) {
        $up = handleUpload($_FILES['logo_file'], __DIR__ . '/../assets/images');
        if ($up['success']) {
            flash('success', 'Aparência salva. Logo: ' . $up['filename']);
        }
    } else {
        flash('success', 'Aparência atualizada com sucesso.');
    }
    header('Location: /admin/aparencia.php');
    exit;
}

// Load current
$custom = [];
foreach (Database::fetchAll('SELECT * FROM customizations') as $c) {
    $custom[$c['setting_key']] = $c['setting_value'];
}

$pageTitle  = 'Aparência';
$breadcrumb = ['Aparência' => ''];

include __DIR__ . '/includes/header.php';
?>

<form method="POST" enctype="multipart/form-data">
  <?= CSRF::field() ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

    <!-- Cores -->
    <div class="card">
      <div class="card-header"><h3>🎨 Paleta de Cores</h3></div>
      <div class="card-body form-grid">

        <div class="form-group">
          <label class="form-label">Cor Primária (Azul-aço)</label>
          <div style="display:flex;gap:10px;align-items:center">
            <input type="color" name="primary_color" value="<?= e($custom['primary_color'] ?? '#527095') ?>"
                   style="width:46px;height:36px;border:1px solid #e5e9ef;border-radius:4px;cursor:pointer;padding:2px">
            <input class="form-input" type="text" id="pc_text" value="<?= e($custom['primary_color'] ?? '#527095') ?>"
                   style="flex:1" oninput="syncColor('pc')">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Cor Secundária (Chumbo)</label>
          <div style="display:flex;gap:10px;align-items:center">
            <input type="color" name="secondary_color" value="<?= e($custom['secondary_color'] ?? '#081217') ?>"
                   style="width:46px;height:36px;border:1px solid #e5e9ef;border-radius:4px;cursor:pointer;padding:2px">
            <input class="form-input" type="text" value="<?= e($custom['secondary_color'] ?? '#081217') ?>" style="flex:1">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Cor do Texto</label>
          <div style="display:flex;gap:10px;align-items:center">
            <input type="color" name="text_color" value="<?= e($custom['text_color'] ?? '#000000') ?>"
                   style="width:46px;height:36px;border:1px solid #e5e9ef;border-radius:4px;cursor:pointer;padding:2px">
            <input class="form-input" type="text" value="<?= e($custom['text_color'] ?? '#000000') ?>" style="flex:1">
          </div>
        </div>

        <!-- Prévia das cores -->
        <div class="form-group full">
          <label class="form-label">Prévia</label>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php foreach ([
              'primary_color' => 'Primária', 'secondary_color' => 'Secundária',
              'text_color' => 'Texto'
            ] as $k => $label): ?>
            <div style="display:flex;align-items:center;gap:6px;font-size:.78rem">
              <div style="width:24px;height:24px;border-radius:50%;background:<?= e($custom[$k] ?? '#000') ?>;border:1px solid rgba(0,0,0,.1)"></div>
              <?= e($label) ?>: <code style="font-size:.7rem"><?= e($custom[$k] ?? '') ?></code>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Tipografia -->
    <div class="card">
      <div class="card-header"><h3>🔤 Tipografia</h3></div>
      <div class="card-body form-grid">
        <div class="form-group full">
          <label class="form-label">Fonte dos Títulos</label>
          <select class="form-select" name="heading_font">
            <?php $fonts = [
              "'Hepta Slab', 'Georgia', serif" => "Hepta Slab (padrão)",
              "'Playfair Display', serif"       => "Playfair Display",
              "'Libre Baskerville', serif"      => "Libre Baskerville",
              "'Merriweather', serif"           => "Merriweather",
              "'EB Garamond', serif"            => "EB Garamond",
            ];
            $curr = $custom['heading_font'] ?? '';
            foreach ($fonts as $val => $lab): ?>
              <option value="<?= e($val) ?>" <?= $curr === $val ? 'selected' : '' ?>><?= e($lab) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group full">
          <label class="form-label">Fonte do Corpo</label>
          <select class="form-select" name="body_font">
            <?php $bfonts = [
              "'Open Sans', sans-serif"   => "Open Sans (padrão)",
              "'Lato', sans-serif"        => "Lato",
              "'Source Sans 3', sans-serif"=> "Source Sans 3",
              "'Nunito', sans-serif"      => "Nunito",
              "'Inter', sans-serif"       => "Inter",
            ];
            $currB = $custom['body_font'] ?? '';
            foreach ($bfonts as $val => $lab): ?>
              <option value="<?= e($val) ?>" <?= $currB === $val ? 'selected' : '' ?>><?= e($lab) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group full">
          <label class="form-label">Prévia Tipográfica</label>
          <div style="padding:16px;background:#f8fafc;border-radius:6px;border:1px solid #e5e9ef">
            <p style="font-family:<?= e($custom['heading_font'] ?? 'serif') ?>;font-size:1.3rem;font-weight:700;margin-bottom:6px;color:#1a3554">
              Carlesso & Carlesso Advogados
            </p>
            <p style="font-family:<?= e($custom['body_font'] ?? 'sans-serif') ?>;font-size:.9rem;color:#6b7280;margin:0">
              Excelência jurídica com ética, transparência e comprometimento.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Identidade -->
    <div class="card">
      <div class="card-header"><h3>🏛️ Identidade</h3></div>
      <div class="card-body form-grid">
        <div class="form-group full">
          <label class="form-label">Texto do Logo (header)</label>
          <input class="form-input" type="text" name="logo_text" value="<?= e($custom['logo_text'] ?? 'Carlesso & Carlesso') ?>">
        </div>
        <div class="form-group full">
          <label class="form-label">Texto do Rodapé</label>
          <input class="form-input" type="text" name="footer_text" value="<?= e($custom['footer_text'] ?? 'Todos os direitos reservados.') ?>">
        </div>
        <div class="form-group full">
          <label class="form-label">Upload Logo (PNG transparente)</label>
          <input class="form-input" type="file" name="logo_file" accept="image/png,image/svg+xml">
          <div class="form-hint">Faça upload do logo sem texto (versão clean) como PNG transparente.</div>
          <?php if (file_exists(__DIR__ . '/../assets/images/logo_sem_texto.png')): ?>
          <img src="/assets/images/logo_sem_texto.png" alt="Logo atual"
               style="margin-top:10px;max-height:60px;border-radius:4px;background:#f0f0f0;padding:6px">
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- CSS Customizado -->
    <div class="card">
      <div class="card-header"><h3>💻 CSS Adicional</h3></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">CSS Customizado</label>
          <textarea class="form-textarea" name="custom_css" rows="8"
                    style="font-family:monospace;font-size:.8rem"
                    placeholder="/* Adicione estilos CSS customizados aqui */
.hero { background: ... }
"><?= e(getCustomization('custom_css', '')) ?></textarea>
          <div class="form-hint">Este CSS será injetado no &lt;head&gt; do site.</div>
        </div>
      </div>
    </div>

  </div><!-- grid -->

  <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px">
    <a href="/" target="_blank" class="topbar-btn outline">
      <span class="i i-eye"></span> Ver Site
    </a>
    <button type="submit" class="topbar-btn success" style="padding:12px 28px;font-size:.9rem">
      <span class="i i-check-circle"></span> Salvar Aparência
    </button>
  </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
