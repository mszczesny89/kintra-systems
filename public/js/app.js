document.addEventListener("click", (e) => {
  const btn = e.target.closest(".menu-toggle");
  if (!btn) return;

  const sidebar = document.getElementById("sidebar");
  if (!sidebar) return;

  const isOpen = sidebar.classList.toggle("active");
  btn.classList.toggle("is-active", isOpen);
  btn.setAttribute("aria-expanded", String(isOpen));
});
