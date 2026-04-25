<?php
// admin/includes/footer.php
if (!defined('CARLESSO_CMS')) exit;
?>
  </div><!-- .page-content -->
</div><!-- .main-content -->
</div><!-- .admin-layout -->

<script>
// Sidebar toggle mobile
const sidebarToggle = document.getElementById('sidebar-toggle');
const sidebar       = document.getElementById('sidebar');
if (sidebarToggle && sidebar) {
  sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', e => {
    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });
}

// CSRF helper para fetch
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
async function apiFetch(url, data = {}) {
  const body = new FormData();
  Object.entries(data).forEach(([k, v]) => body.append(k, v));
  body.append('_csrf', csrfToken);
  const res  = await fetch(url, { method: 'POST', body });
  return res.json();
}

// Confirmação de delete
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', e => {
    if (!confirm(btn.dataset.confirm || 'Confirmar esta ação?')) e.preventDefault();
  });
});

// Auto-dismiss alerts
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(a => {
    a.style.transition = 'opacity .4s';
    a.style.opacity = '0';
    setTimeout(() => a.remove(), 400);
  });
}, 4000);
</script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
