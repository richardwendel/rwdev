// =========================================
// PORTFÓLIO PREMIUM RWDEV
// =========================================

// Revela os elementos quando entram na área visível da tela.
const elementosReveal = document.querySelectorAll(".reveal-scroll");

if ("IntersectionObserver" in window && elementosReveal.length > 0) {
  const revealObserver = new IntersectionObserver(
    (entradas) => {
      entradas.forEach((entrada) => {
        if (entrada.isIntersecting) {
          entrada.target.classList.add("revelado");
          revealObserver.unobserve(entrada.target);
        }
      });
    },
    {
      threshold: 0.18,
      rootMargin: "0px 0px -60px 0px",
    }
  );

  elementosReveal.forEach((elemento) => revealObserver.observe(elemento));
} else {
  elementosReveal.forEach((elemento) => elemento.classList.add("revelado"));
}

// Cria uma micro interação de luz no card acompanhando o ponteiro.
const cardsPortfolio = document.querySelectorAll(".portfolio-card");
const aceitaHover = window.matchMedia("(hover: hover)").matches;

if (aceitaHover && cardsPortfolio.length > 0) {
  cardsPortfolio.forEach((card) => {
    card.addEventListener("mousemove", (evento) => {
      const area = card.getBoundingClientRect();
      const mouseX = ((evento.clientX - area.left) / area.width) * 100;
      const mouseY = ((evento.clientY - area.top) / area.height) * 100;

      card.style.setProperty("--mouse-x", `${mouseX}%`);
      card.style.setProperty("--mouse-y", `${mouseY}%`);
    });

    card.addEventListener("mouseleave", () => {
      card.style.setProperty("--mouse-x", "50%");
      card.style.setProperty("--mouse-y", "0%");
    });
  });
}
