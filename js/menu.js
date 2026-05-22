const menuToggle = document.querySelector(".menu-toggle");
const menuPrincipal = document.getElementById("menuPrincipal");

if (menuToggle && menuPrincipal) {
  menuToggle.addEventListener("click", () => {
    const menuAberto = menuPrincipal.classList.toggle("aberto");

    menuToggle.classList.toggle("ativo", menuAberto);
    menuToggle.setAttribute("aria-expanded", String(menuAberto));
    menuToggle.setAttribute("aria-label", menuAberto ? "Fechar menu" : "Abrir menu");
  });

  menuPrincipal.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      menuPrincipal.classList.remove("aberto");
      menuToggle.classList.remove("ativo");
      menuToggle.setAttribute("aria-expanded", "false");
      menuToggle.setAttribute("aria-label", "Abrir menu");
    });
  });
}
