<div class="lewy-bar">
  <button class="menu-toggle" aria-label="Menu">
    <span></span>
    <span></span>
    <span></span>
  </button>

  <div class="mobile-header">
    <span class="page-title">Project</span>
  </div>

  <nav class="sidebar">
      <ul>
          <li><a href="#">Project</a></li>
          <li><a href="#">Plan</a></li>
          <li><a href="#">Users</a></li>
          <li><a href="#">Invoices</a></li>
      </ul>
  </nav>

  <div class="content">
      <h1>Project</h1>
      <p>Bla Bla</p>
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
