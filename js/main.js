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

// Atualiza o indicador de pendencias administrativas sem expor dados sensiveis.
function atualizarBadgeNotificacoesAdmin() {
  const linkAreaCliente = Array.from(document.querySelectorAll(".menu a")).find((link) => {
    const href = link.getAttribute("href") || "";
    return href.includes("/portal/cliente/login.php");
  });

  if (!linkAreaCliente) {
    return;
  }

  fetch("/api/notificacoes-admin.php", {
    headers: { "Accept": "application/json" },
    cache: "no-store",
  })
    .then((resposta) => {
      if (!resposta.ok) {
        return { adminAutenticado: false, total: 0 };
      }

      return resposta.json().then((dados) => ({
        adminAutenticado: true,
        total: Number(dados.total || 0),
      }));
    })
    .then((dados) => {
      const total = dados.total;
      const indicadorAtual = linkAreaCliente.querySelector(".notificacoes-admin-indicador");

      if (!dados.adminAutenticado) {
        indicadorAtual?.remove();
        linkAreaCliente.classList.remove("com-notificacoes-admin");
        return;
      }

      linkAreaCliente.setAttribute("href", "/portal/admin/dashboard.php");

      if (total <= 0) {
        indicadorAtual?.remove();
        linkAreaCliente.classList.remove("com-notificacoes-admin");
        return;
      }

      const indicador = indicadorAtual || document.createElement("span");
      indicador.className = "notificacoes-admin-indicador";
      indicador.innerHTML = `
        <span class="notificacoes-admin-icone" aria-hidden="true">🔔</span>
        <span class="notificacoes-admin-badge">${total}</span>
      `;

      if (!indicadorAtual) {
        linkAreaCliente.appendChild(indicador);
      }

      linkAreaCliente.classList.add("com-notificacoes-admin");
      linkAreaCliente.setAttribute("aria-label", `Area do Cliente, ${total} pendencia${total === 1 ? "" : "s"} administrativa${total === 1 ? "" : "s"}`);
    })
    .catch(() => {});
}

atualizarBadgeNotificacoesAdmin();
