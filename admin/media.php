<?php
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('editor');

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && Auth::isAdmin()) {
    CSRF::check();
    $mid   = (int)$_POST['delete_id'];
    $media = Database::fetchOne('SELECT * FROM media WHERE id = ?', [$mid]);
    if ($media) {
        $fullPath = __DIR__ . '/..' . $media['file_path'];
        if (file_exists($fullPath)) unlink($fullPath);
        Database::query('DELETE FROM media WHERE id = ?', [$mid]);
        flash('success', 'Arquivo removido.');
    }
    header('Location: /admin/media.php');
    exit;
}

$mediaItems = Database::fetchAll('SELECT * FROM media ORDER BY created_at DESC');
$pageTitle  = 'Biblioteca de Mídia';
$breadcrumb = ['Biblioteca de Mídia' => ''];

$extraScripts = '
<script>
// Drag & drop upload zone
const zone   = document.getElementById("upload-zone");
const fileIn = document.getElementById("file-input");
const csrf   = document.querySelector(\'meta[name="csrf-token"]\')?.content || "";
const gallery= document.getElementById("media-gallery");

if (zone) {
  zone.addEventListener("click", () => fileIn.click());
  zone.addEventListener("dragover", e => { e.preventDefault(); zone.classList.add("drag-over"); });
  zone.addEventListener("dragleave", () => zone.classList.remove("drag-over"));
  zone.addEventListener("drop", e => {
    e.preventDefault(); zone.classList.remove("drag-over");
    uploadFiles(e.dataTransfer.files);
  });
  fileIn.addEventListener("change", () => uploadFiles(fileIn.files));
}

async function uploadFiles(files) {
  for (const file of files) {
    const fd = new FormData();
    fd.append("file", file);
    fd.append("_csrf", csrf);
    const res  = await fetch("/api/upload.php", { method: "POST", body: fd });
    const json = await res.json();
    if (json.success) {
      gallery.insertAdjacentHTML("afterbegin", `
        <div class="media-item" style="position:relative;border-radius:6px;overflow:hidden;border:1px solid #e5e9ef;aspect-ratio:1">
          <img src="${json.url}" alt="" style="width:100%;height:100%;object-fit:cover">
          <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.7);padding:6px 8px;font-size:.68rem;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${file.name}</div>
        </div>`);
      showToast("✓ " + file.name + " enviado!", "success");
    } else {
      showToast("✕ " + (json.message || "Erro"), "error");
    }
  }
}

function copyUrl(url) {
  navigator.clipboard.writeText(location.origin + url).then(() => showToast("URL copiada!", "success"));
}

function showToast(msg, type) {
  const el = Object.assign(document.createElement("div"), {textContent: msg});
  Object.assign(el.style, {
    position:"fixed",bottom:"24px",right:"24px",zIndex:"9999",
    padding:"12px 18px",borderRadius:"6px",fontSize:".85rem",fontWeight:"600",
    background:type==="success"?"#10b981":"#ef4444",color:"#fff",
    boxShadow:"0 4px 20px rgba(0,0,0,.2)",transform:"translateX(120%)",transition:"transform .3s ease"
  });
  document.body.appendChild(el);
  requestAnimationFrame(() => el.style.transform = "translateX(0)");
  setTimeout(() => { el.style.transform = "translateX(120%)"; setTimeout(() => el.remove(), 400); }, 3500);
}
</script>';

include __DIR__ . '/includes/header.php';
?>

<!-- Upload Zone -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><h3>Upload de Arquivos</h3></div>
  <div class="card-body">
    <div class="upload-zone" id="upload-zone">
      <div class="icon">☁️</div>
      <p><strong>Arraste arquivos aqui</strong> ou clique para selecionar</p>
      <p style="font-size:.75rem;margin-top:4px;color:#9ca3af">JPG, PNG, WEBP, GIF, SVG, PDF — máx. 5MB</p>
    </div>
    <input type="file" id="file-input" multiple accept="image/*,.pdf,.svg" style="display:none">
  </div>
</div>

<!-- Galeria -->
<div class="card">
  <div class="card-header">
    <h3>Biblioteca <span class="badge badge-blue"><?= count($mediaItems) ?></span></h3>
  </div>
  <div class="card-body">
    <?php if (empty($mediaItems)): ?>
    <div style="text-align:center;padding:48px;color:#9ca3af">
      <div style="font-size:3rem;margin-bottom:12px">🖼️</div>
      <p>Nenhum arquivo enviado ainda. Faça o primeiro upload!</p>
    </div>
    <?php else: ?>
    <div id="media-gallery" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:12px">
      <?php foreach ($mediaItems as $m): ?>
      <div style="position:relative;border-radius:6px;overflow:hidden;border:1px solid #e5e9ef;aspect-ratio:1;group">
        <?php
        $isImg = str_starts_with($m['file_type'] ?? '', 'image/');
        if ($isImg): ?>
          <img src="<?= e($m['file_path']) ?>" alt="<?= e($m['original_name']) ?>"
               style="width:100%;height:100%;object-fit:cover" loading="lazy">
        <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f8fafc;flex-direction:column;gap:8px">
            <span style="font-size:2rem">📄</span>
            <span style="font-size:.65rem;color:#6b7280;text-align:center;padding:4px;word-break:break-all"><?= e(strtoupper(pathinfo($m['filename'], PATHINFO_EXTENSION))) ?></span>
          </div>
        <?php endif; ?>

        <!-- Overlay de ações -->
        <div style="position:absolute;inset:0;background:rgba(0,0,0,.6);opacity:0;transition:.2s;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:8px"
             onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
          <button onclick="copyUrl('<?= e($m['file_path']) ?>')"
                  style="width:100%;padding:5px 8px;background:rgba(255,255,255,.9);border:none;border-radius:3px;font-size:.7rem;font-weight:600;cursor:pointer">
            📋 Copiar URL
          </button>
          <a href="<?= e($m['file_path']) ?>" target="_blank"
             style="width:100%;padding:5px 8px;background:rgba(255,255,255,.75);border:none;border-radius:3px;font-size:.7rem;font-weight:600;cursor:pointer;text-align:center;color:#000;text-decoration:none">
            👁 Abrir
          </a>
          <?php if (Auth::isAdmin()): ?>
          <form method="POST" style="width:100%">
            <?= CSRF::field() ?>
            <input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
            <button type="submit" data-confirm="Remover '<?= e($m['original_name']) ?>'?"
                    style="width:100%;padding:5px 8px;background:rgba(239,68,68,.8);border:none;border-radius:3px;font-size:.7rem;font-weight:600;cursor:pointer;color:#fff">
              🗑 Remover
            </button>
          </form>
          <?php endif; ?>
        </div>

        <!-- Info nome -->
        <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.65);padding:5px 6px;font-size:.62rem;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= e($m['original_name']) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
