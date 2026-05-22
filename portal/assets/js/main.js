document.querySelectorAll("[data-toggle-outra]").forEach((select) => {
  const target = document.querySelector(select.dataset.toggleOutra);

  function atualizar() {
    if (!target) return;
    target.classList.toggle("hidden", select.value !== "Outra");
  }

  select.addEventListener("change", atualizar);
  atualizar();
});

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
