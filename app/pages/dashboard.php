<div class="lewy-bar">
  <button class="menu-toggle" aria-label="Menu">
    <span></span>
    <span></span>
    <span></span>
  </button>

  <div class="mobile-header">
    <span class="page-title">Dashboard</span>
  </div>

  <nav class="sidebar">
      <ul>
          <li><a href="<?= htmlspecialchars(url('project'), ENT_QUOTES) ?>">Plan</a></li>
          <li><a href="<?= htmlspecialchars(url('project'), ENT_QUOTES) ?>">Progress</a></li>
          <li><a href="<?= htmlspecialchars(url('project'), ENT_QUOTES) ?>">Invoices</a></li>
          <li><a href="<?= htmlspecialchars(url('project'), ENT_QUOTES) ?>">Testers</a></li>
      </ul>
  </nav>

  <div class="content">
      <h1>Treść strony</h1>
      <p>Tutaj leci content.</p>
  </div>
</div>

<script>
const toggle = document.querySelector('.menu-toggle');
const sidebar = document.querySelector('.sidebar');

toggle.addEventListener('click', () => {
  sidebar.classList.toggle('active');
  toggle.classList.toggle('is-active');
});
</script>
