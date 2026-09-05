const galeria = document.querySelector("[data-galeria]");
const botaoAnterior = document.querySelector("[data-galeria-anterior]");
const botaoProximo = document.querySelector("[data-galeria-proxima]");
const modalImagem = document.querySelector("[data-modal-imagem]");
const modalConteudo = modalImagem?.querySelector("img");
const botaoFecharModal = modalImagem?.querySelector("[data-modal-fechar]");

function moverGaleria(direcao) {
  if (!galeria) return;

  const item = galeria.querySelector(".agv-galeria-item");
  const distancia = item ? item.getBoundingClientRect().width + 20 : galeria.clientWidth * 0.8;
  galeria.scrollBy({ left: distancia * direcao, behavior: "smooth" });
}

botaoAnterior?.addEventListener("click", () => moverGaleria(-1));
botaoProximo?.addEventListener("click", () => moverGaleria(1));

document.querySelectorAll("[data-imagem-grande]").forEach((item) => {
  item.addEventListener("click", () => {
    if (!modalImagem || !modalConteudo) return;

    modalConteudo.src = item.dataset.imagemGrande || "";
    modalConteudo.alt = item.dataset.imagemAlt || "Material da Universo AGV";
    modalImagem.showModal();
  });
});

function fecharModal() {
  if (modalImagem?.open) modalImagem.close();
}

botaoFecharModal?.addEventListener("click", fecharModal);

modalImagem?.addEventListener("click", (evento) => {
  if (evento.target === modalImagem) fecharModal();
});

const formularioCotacao = document.getElementById("agvCotacaoForm");
const mensagemFormulario = document.querySelector("[data-agv-form-mensagem]");
const botaoEnviar = document.querySelector("[data-agv-enviar]");

function normalizarPlaca(valor) {
  const placa = String(valor || "").toUpperCase().replace(/[\s-]+/g, "").replace(/[^A-Z0-9]/g, "").slice(0, 7);
  return /^[A-Z]{3}[0-9]{4}$/.test(placa) ? `${placa.slice(0, 3)}-${placa.slice(3)}` : placa;
}

function normalizarWhatsapp(valor) {
  let digitos = String(valor || "").replace(/\D+/g, "");
  if ((digitos.length === 12 || digitos.length === 13) && digitos.startsWith("55")) digitos = digitos.slice(2);
  return digitos;
}

function whatsappValido(valor) {
  return /^[1-9][0-9](?:[2-9][0-9]{7}|9[0-9]{8})$/.test(normalizarWhatsapp(valor));
}

function placaValida(valor) {
  return /^(?:[A-Z]{3}-[0-9]{4}|[A-Z]{3}[0-9][A-Z][0-9]{2})$/.test(normalizarPlaca(valor));
}

function mostrarMensagem(texto, tipo = "") {
  if (!mensagemFormulario) return;
  mensagemFormulario.textContent = texto;
  mensagemFormulario.className = `agv-form-mensagem ${tipo}`.trim();
}

function definirErro(campo, mensagem) {
  if (!formularioCotacao) return;
  const entrada = formularioCotacao.elements.namedItem(campo);
  const erro = formularioCotacao.querySelector(`[data-erro-campo="${campo}"]`);

  if (entrada instanceof HTMLInputElement) {
    entrada.setCustomValidity(mensagem);
    entrada.setAttribute("aria-invalid", mensagem ? "true" : "false");
  }

  if (erro) erro.textContent = mensagem;
}

function validarFormulario() {
  if (!formularioCotacao) return false;

  const dados = new FormData(formularioCotacao);
  const anoAtual = new Date().getFullYear();
  const ano = String(dados.get("ano") || "").trim();
  const erros = {
    nome: String(dados.get("nome") || "").trim().length >= 2 ? "" : "Informe seu nome.",
    whatsapp: whatsappValido(dados.get("whatsapp")) ? "" : "Informe um WhatsApp brasileiro válido com DDD.",
    cidade: String(dados.get("cidade") || "").trim().length >= 2 ? "" : "Informe sua cidade.",
    veiculo: String(dados.get("veiculo") || "").trim().length >= 2 ? "" : "Informe o veículo e o modelo.",
    ano: /^\d{4}$/.test(ano) && Number(ano) >= 1900 && Number(ano) <= anoAtual + 1 ? "" : "Informe um ano válido com quatro números.",
    placa: placaValida(dados.get("placa")) ? "" : "Informe uma placa válida, como ABC-1234 ou ABC1D23.",
    privacidade_aceita: dados.get("privacidade_aceita") ? "" : "Confirme o uso dos dados para solicitar a cotação.",
  };

  Object.entries(erros).forEach(([campo, mensagem]) => definirErro(campo, mensagem));
  const valido = Object.values(erros).every((mensagem) => mensagem === "");

  if (!valido) {
    mostrarMensagem("Revise os campos destacados.", "erro");
    formularioCotacao.reportValidity();
  }

  return valido;
}

if (formularioCotacao) {
  const campoPlaca = formularioCotacao.elements.namedItem("placa");

  campoPlaca?.addEventListener("input", () => {
    campoPlaca.value = normalizarPlaca(campoPlaca.value);
    definirErro("placa", "");
  });

  formularioCotacao.querySelectorAll("input").forEach((entrada) => {
    entrada.addEventListener("input", () => definirErro(entrada.name, ""));
    entrada.addEventListener("change", () => definirErro(entrada.name, ""));
  });

  formularioCotacao.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    mostrarMensagem("");

    if (!validarFormulario()) return;

    const dadosFormulario = new FormData(formularioCotacao);
    const payload = Object.fromEntries(dadosFormulario.entries());
    payload.privacidade_aceita = dadosFormulario.has("privacidade_aceita");
    payload.placa = normalizarPlaca(payload.placa);

    if (botaoEnviar) {
      botaoEnviar.disabled = true;
      botaoEnviar.textContent = "Registrando solicitação...";
    }

    try {
      const resposta = await fetch("api/agv-leads.php", {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json", "Accept": "application/json" },
        body: JSON.stringify(payload),
      });
      let retorno = {};

      try {
        retorno = await resposta.json();
      } catch {
        throw new Error("Não foi possível registrar sua solicitação agora. Tente novamente.");
      }

      if (!resposta.ok || !retorno.sucesso || !retorno.whatsapp_url) {
        Object.entries(retorno.erros || {}).forEach(([campo, mensagem]) => definirErro(campo, String(mensagem)));
        throw new Error(retorno.mensagem || "Não foi possível registrar sua solicitação.");
      }

      mostrarMensagem(`Solicitação ${retorno.codigo} registrada. Abrindo o WhatsApp...`, "sucesso");
      window.location.assign(retorno.whatsapp_url);
    } catch (erro) {
      mostrarMensagem(erro instanceof Error ? erro.message : "Não foi possível registrar sua solicitação.", "erro");

      if (botaoEnviar) {
        botaoEnviar.disabled = false;
        botaoEnviar.textContent = "Solicitar cotação e falar com Carlos";
      }
    }
  });
}
