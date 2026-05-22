document.querySelectorAll("[data-toggle-outra]").forEach((select) => {
  const target = document.querySelector(select.dataset.toggleOutra);

  function atualizar() {
    if (!target) return;
    target.classList.toggle("hidden", select.value !== "Outra");
  }

  select.addEventListener("change", atualizar);
  atualizar();
});

const projetoSelect = document.querySelector("#projeto_id");
const paginaSelect = document.querySelector("#pagina");

if (projetoSelect && paginaSelect) {
  function atualizarPaginasDoProjeto() {
    const selected = projetoSelect.selectedOptions[0];
    let paginas = [];

    if (selected && selected.dataset.pages) {
      try {
        paginas = JSON.parse(selected.dataset.pages);
      } catch (error) {
        paginas = [];
      }
    }

    if (!paginas.length) {
      paginas = ["Inicio", "Sobre", "Servicos", "Contato", "Outra"];
    }

    paginaSelect.innerHTML = "";

    paginas.forEach((pagina) => {
      const option = document.createElement("option");
      option.value = pagina;
      option.textContent = pagina;
      paginaSelect.appendChild(option);
    });

    paginaSelect.dispatchEvent(new Event("change"));
  }

  projetoSelect.addEventListener("change", atualizarPaginasDoProjeto);
  atualizarPaginasDoProjeto();
}

document.querySelectorAll('input[type="file"][multiple]').forEach((input) => {
  input.addEventListener("change", () => {
    if (input.files.length > 5) {
      alert("Envie no máximo 5 arquivos por solicitação.");
      input.value = "";
    }
  });
});

document.querySelectorAll("[data-copy]").forEach((button) => {
  button.addEventListener("click", async () => {
    const value = button.dataset.copy || "";

    try {
      await navigator.clipboard.writeText(value);
      const original = button.textContent;
      button.textContent = "Link copiado";
      setTimeout(() => {
        button.textContent = original;
      }, 1800);
    } catch (error) {
      window.prompt("Copie o link do convite:", value);
    }
  });
});

document.querySelectorAll("[data-copy-target]").forEach((button) => {
  button.addEventListener("click", async () => {
    const input = document.querySelector(button.dataset.copyTarget);
    const value = input ? input.value : "";
    const feedback = button.parentElement
      ? button.parentElement.querySelector(".copy-feedback")
      : null;

    function mostrarFeedback(texto) {
      if (!feedback) return;
      feedback.textContent = texto;
      feedback.classList.add("visivel");
      setTimeout(() => {
        feedback.textContent = "";
        feedback.classList.remove("visivel");
      }, 2400);
    }

    try {
      await navigator.clipboard.writeText(value);
      const original = button.textContent;
      button.textContent = "Link copiado";
      mostrarFeedback("Link copiado com sucesso.");
      setTimeout(() => {
        button.textContent = original;
      }, 1800);
    } catch (error) {
      if (input) {
        input.select();
        document.execCommand("copy");
        mostrarFeedback("Link copiado com sucesso.");
      }
    }
  });
});

document.querySelectorAll("form[data-prevent-double-submit]").forEach((form) => {
  form.addEventListener("submit", () => {
    const button = form.querySelector('button[type="submit"]');
    if (!button) return;

    button.disabled = true;
    button.dataset.originalText = button.textContent;
    button.textContent = "Enviando...";
  });
});
