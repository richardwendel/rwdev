// =========================================
// DIAGNOSTICO DIGITAL RWDEV
// =========================================

const formDiagnostico = document.getElementById("formDiagnostico");
const alertaDiagnostico = document.getElementById("diagnosticoAlerta");
const progressoDiagnostico = document.getElementById("diagnosticoProgresso");
const etapaDiagnostico = document.getElementById("diagnosticoEtapaTexto");
const percentualDiagnostico = document.getElementById("diagnosticoPercentual");
const resultadoDiagnostico = document.getElementById("diagnosticoResultado");
const processamentoDiagnostico = document.getElementById("diagnosticoProcessando");
const tituloResultado = document.getElementById("diagnosticoTituloResultado");
const pontuacaoResultado = document.getElementById("diagnosticoPontuacao");
const barraResultado = document.getElementById("diagnosticoBarraResultado");
const resumoResultado = document.getElementById("diagnosticoResumo");
const mensagemFinalResultado = document.getElementById("diagnosticoMensagemFinal");
const listaRecomendacoes = document.getElementById("diagnosticoRecomendacoes");
const linkWhatsappDiagnostico = document.getElementById("diagnosticoWhatsapp");
const botaoIniciarPerguntas = document.getElementById("diagnosticoIniciarPerguntas");
const botaoAbrirDiagnostico = document.querySelector(".diagnostico-cta");
const progressoTopo = document.querySelector(".diagnostico-progresso");
const progressoBarra = document.querySelector(".diagnostico-progresso-barra");
const blocoDados = formDiagnostico?.querySelector(".diagnostico-dados");

const endpointMetricasDiagnostico = "api/track-diagnostico.php";
const telefoneRwdev = "5511981104971";
let inicioDiagnosticoRegistrado = false;
let conclusaoDiagnosticoRegistrada = false;
let perguntaAtual = -1;
let dadoAtual = -1;
let avancandoPergunta = false;
let botaoSairDiagnostico = null;
let modalFluxoDiagnostico = null;
let modalFluxoCard = null;
let modalConfirmacaoSaida = null;

const perguntasDiagnostico = [
  { nome: "google_aparece", texto: "Quando algu\u00e9m pesquisa sua empresa no Google, ela aparece?" },
  { nome: "whatsapp_contatos", texto: "Voc\u00ea recebe contatos pelo WhatsApp regularmente?" },
  { nome: "perfil_google", texto: "Sua empresa possui Perfil da Empresa no Google?" },
  { nome: "instagram_ativo", texto: "Sua empresa possui Instagram ativo?" },
  { nome: "google_ads", texto: "Voc\u00ea j\u00e1 anunciou no Google Ads?" },
  { nome: "site_profissional", texto: "Voc\u00ea possui um site profissional?" },
  { nome: "visitas_site", texto: "Voc\u00ea sabe quantas pessoas visitam seu site por m\u00eas?" },
  { nome: "contatos_google", texto: "Voc\u00ea acompanha quantos contatos chegam atrav\u00e9s do Google?" },
];

const dadosEtapas = ["empresa", "responsavel", "whatsapp", "cidade", "email"];

function registrarMetricaDiagnostico(eventType, dadosExtras = {}) {
  const payload = JSON.stringify({
    event_type: eventType,
    page: "/diagnostico",
    referer: document.referrer || "",
    ...dadosExtras,
  });

  if (navigator.sendBeacon) {
    const blob = new Blob([payload], { type: "application/json" });

    if (navigator.sendBeacon(endpointMetricasDiagnostico, blob)) {
      return;
    }
  }

  fetch(endpointMetricasDiagnostico, {
    method: "POST",
    headers: { "Content-Type": "application/json", "Accept": "application/json" },
    body: payload,
    keepalive: true,
  }).catch(() => {});
}

function campoFormulario(nome) {
  return formDiagnostico ? formDiagnostico.elements[nome] : null;
}

function valorCampo(nome) {
  const campo = campoFormulario(nome);

  return campo ? String(campo.value || "").trim() : "";
}

function respostaSelecionada(nome) {
  return formDiagnostico ? formDiagnostico.querySelector(`input[name="${nome}"]:checked`) : null;
}

function etapaPergunta(indice) {
  return formDiagnostico ? formDiagnostico.querySelector(`[data-pergunta="${indice}"]`) : null;
}

function definirEtapaAtiva(elementoAtivo) {
  if (!formDiagnostico) {
    return;
  }

  formDiagnostico.querySelectorAll(".diagnostico-etapa").forEach((etapa) => {
    etapa.classList.toggle("ativa", etapa === elementoAtivo);
  });
}

function alternarProgresso(mostrar) {
  if (progressoTopo) {
    progressoTopo.hidden = !mostrar;
  }

  if (progressoBarra) {
    progressoBarra.hidden = !mostrar;
  }
}

function atualizarProgressoDiagnostico(modo = "pergunta") {
  if (!progressoDiagnostico || !etapaDiagnostico) {
    return;
  }

  if (modo === "dados") {
    const numeroDado = Math.max(1, dadoAtual + 1);
    const percentual = Math.round(((perguntasDiagnostico.length + numeroDado) / (perguntasDiagnostico.length + dadosEtapas.length)) * 100);

    etapaDiagnostico.textContent = "Dados para an\u00e1lise";
    progressoDiagnostico.style.width = `${percentual}%`;

    if (percentualDiagnostico) {
      percentualDiagnostico.textContent = `${percentual}%`;
    }

    return;
  }

  if (perguntaAtual < 0) {
    progressoDiagnostico.style.width = "0%";
    etapaDiagnostico.textContent = "Diagn\u00f3stico gratuito";

    if (percentualDiagnostico) {
      percentualDiagnostico.textContent = "0%";
    }

    return;
  }

  const numeroPergunta = Math.min(perguntaAtual + 1, perguntasDiagnostico.length);
  const percentual = Math.round((numeroPergunta / (perguntasDiagnostico.length + dadosEtapas.length)) * 100);

  progressoDiagnostico.style.width = `${percentual}%`;
  etapaDiagnostico.textContent = `Pergunta ${numeroPergunta} de ${perguntasDiagnostico.length}`;

  if (percentualDiagnostico) {
    percentualDiagnostico.textContent = `${percentual}%`;
  }
}

function rolarModalParaTopo() {
  modalFluxoCard?.scrollTo({ top: 0, behavior: "smooth" });
}

function prepararPaginaCompacta() {
  const heroTexto = document.querySelector(".diagnostico-hero-texto");
  const tituloHero = heroTexto?.querySelector("h1");
  const textoHero = heroTexto?.querySelector("p");

  if (tituloHero) {
    tituloHero.textContent = "Diagn\u00f3stico Gratuito de Presen\u00e7a Digital";
  }

  if (textoHero) {
    textoHero.textContent = "Descubra em menos de 1 minuto se sua empresa est\u00e1 aparecendo bem no Google, aproveitando o WhatsApp e preparada para gerar mais oportunidades.";
  }

  if (botaoAbrirDiagnostico) {
    botaoAbrirDiagnostico.textContent = "Come\u00e7ar diagn\u00f3stico";
    botaoAbrirDiagnostico.setAttribute("href", "#");
    botaoAbrirDiagnostico.setAttribute("role", "button");
  }

  if (heroTexto && !heroTexto.querySelector(".diagnostico-beneficios-pagina")) {
    const beneficios = document.createElement("div");
    beneficios.className = "diagnostico-beneficios-pagina";
    beneficios.innerHTML = `
      <span>Visibilidade no Google</span>
      <span>Capta\u00e7\u00e3o pelo WhatsApp</span>
      <span>Oportunidades de crescimento</span>
    `;
    heroTexto.insertBefore(beneficios, botaoAbrirDiagnostico);
  }
}

function prepararCamposDeDados() {
  if (!blocoDados) {
    return;
  }

  blocoDados.classList.remove("ativa");
  blocoDados.querySelectorAll(".diagnostico-campos label").forEach((label) => {
    label.classList.add("diagnostico-campo-passo");
  });

  let botaoContinuar = blocoDados.querySelector("[data-continuar-dados]");

  if (!botaoContinuar) {
    botaoContinuar = document.createElement("button");
    botaoContinuar.type = "button";
    botaoContinuar.className = "diagnostico-submit diagnostico-continuar-dados";
    botaoContinuar.dataset.continuarDados = "true";
    botaoContinuar.textContent = "Continuar";
    blocoDados.appendChild(botaoContinuar);
  }

  botaoContinuar.addEventListener("click", avancarDado);
}

function criarModalFluxo() {
  if (!formDiagnostico || modalFluxoDiagnostico) {
    return;
  }

  modalFluxoDiagnostico = document.createElement("div");
  modalFluxoDiagnostico.className = "diagnostico-fluxo-modal";
  modalFluxoDiagnostico.id = "diagnosticoFluxoModal";
  modalFluxoDiagnostico.setAttribute("aria-hidden", "true");
  modalFluxoDiagnostico.innerHTML = `
    <div class="diagnostico-fluxo-card" role="dialog" aria-modal="true" aria-labelledby="diagnosticoFluxoTitulo">
      <button class="diagnostico-fluxo-fechar" type="button" aria-label="Fechar diagnostico">x</button>
      <div class="diagnostico-fluxo-conteudo"></div>
    </div>
  `;

  document.body.appendChild(modalFluxoDiagnostico);
  modalFluxoCard = modalFluxoDiagnostico.querySelector(".diagnostico-fluxo-card");

  const conteudo = modalFluxoDiagnostico.querySelector(".diagnostico-fluxo-conteudo");
  const tituloModal = document.createElement("span");
  tituloModal.className = "sr-only";
  tituloModal.id = "diagnosticoFluxoTitulo";
  tituloModal.textContent = "Diagn\u00f3stico gratuito de presen\u00e7a digital";

  conteudo.appendChild(tituloModal);
  conteudo.appendChild(formDiagnostico);

  if (resultadoDiagnostico) {
    conteudo.appendChild(resultadoDiagnostico);
  }

  modalFluxoDiagnostico.querySelector(".diagnostico-fluxo-fechar")?.addEventListener("click", pedirConfirmacaoSaida);
  modalFluxoDiagnostico.addEventListener("click", (evento) => {
    if (evento.target === modalFluxoDiagnostico) {
      pedirConfirmacaoSaida();
    }
  });
}

function abrirModalDiagnostico(evento) {
  evento?.preventDefault();

  if (!modalFluxoDiagnostico) {
    return;
  }

  resetarDiagnosticoVisual();
  modalFluxoDiagnostico.classList.add("ativo");
  modalFluxoDiagnostico.setAttribute("aria-hidden", "false");
  document.body.classList.add("diagnostico-modal-aberto");
  iniciarPerguntas();
}

function fecharModalDiagnostico() {
  if (!modalFluxoDiagnostico) {
    return;
  }

  modalFluxoDiagnostico.classList.remove("ativo");
  modalFluxoDiagnostico.setAttribute("aria-hidden", "true");
  document.body.classList.remove("diagnostico-modal-aberto");
  resetarDiagnosticoVisual();
}

function mostrarCampoDeDados(indice) {
  if (!blocoDados) {
    return;
  }

  dadoAtual = indice;
  definirEtapaAtiva(blocoDados);
  blocoDados.querySelectorAll(".diagnostico-campo-passo").forEach((label) => {
    const input = label.querySelector("input");
    label.classList.toggle("ativo", input?.name === dadosEtapas[indice]);
  });

  const inputAtual = campoFormulario(dadosEtapas[indice]);
  alertaDiagnostico.textContent = "";
  atualizarProgressoDiagnostico("dados");
  rolarModalParaTopo();

  window.setTimeout(() => inputAtual?.focus(), 120);
}

function validarDadoAtual() {
  const nome = dadosEtapas[dadoAtual];
  const valor = valorCampo(nome);

  if (nome !== "email" && !valor) {
    return "Preencha este campo para continuar.";
  }

  if (nome === "whatsapp") {
    const telefone = valor.replace(/\D/g, "");

    if (telefone.length < 10 || telefone.length > 13) {
      return "Informe um WhatsApp valido com DDD.";
    }
  }

  if (nome === "email") {
    const campoEmail = campoFormulario("email");

    if (valor && campoEmail && !campoEmail.checkValidity()) {
      return "Informe um e-mail v\u00e1lido ou deixe o campo em branco.";
    }
  }

  return "";
}

function avancarDado() {
  const erro = validarDadoAtual();

  if (erro) {
    alertaDiagnostico.textContent = erro;
    return;
  }

  if (dadoAtual < dadosEtapas.length - 1) {
    mostrarCampoDeDados(dadoAtual + 1);
    return;
  }

  processarDiagnostico();
}

function iniciarPerguntas() {
  perguntaAtual = 0;
  dadoAtual = -1;
  avancandoPergunta = false;

  if (formDiagnostico) {
    formDiagnostico.hidden = false;
  }

  if (resultadoDiagnostico) {
    resultadoDiagnostico.hidden = true;
  }

  alternarProgresso(true);
  botaoSairDiagnostico.hidden = false;
  definirEtapaAtiva(etapaPergunta(perguntaAtual));
  atualizarProgressoDiagnostico();
  rolarModalParaTopo();
}

function registrarInicioDiagnostico() {
  if (!inicioDiagnosticoRegistrado) {
    inicioDiagnosticoRegistrado = true;
    registrarMetricaDiagnostico("diagnosis_start");
  }
}

function diagnosticoPorPontuacao(pontos) {
  if (pontos <= 40) {
    return {
      titulo: "Oportunidade Cr\u00edtica",
      texto: "Sua empresa possui oportunidades importantes de melhoria digital. Existem pontos que podem estar reduzindo sua visibilidade e gera\u00e7\u00e3o de contatos.",
      complemento: "Inicie uma conversa com a RWDEV e descubra poss\u00edveis solu\u00e7\u00f5es para aumentar seus resultados.",
    };
  }

  if (pontos <= 70) {
    return {
      titulo: "Potencial de Crescimento",
      texto: "Sua empresa j\u00e1 possui uma boa base digital, por\u00e9m ainda existem oportunidades para ampliar sua presen\u00e7a online e aumentar a gera\u00e7\u00e3o de leads.",
      complemento: "A RWDEV pode ajudar a identificar a\u00e7\u00f5es pr\u00e1ticas para acelerar esse crescimento.",
    };
  }

  return {
    titulo: "Presen\u00e7a Digital S\u00f3lida",
    texto: "Parab\u00e9ns! Sua empresa demonstra uma presen\u00e7a digital s\u00f3lida.",
    complemento: "Mesmo empresas com boa estrutura podem alcan\u00e7ar resultados ainda melhores atrav\u00e9s de SEO avan\u00e7ado, Google Ads, otimiza\u00e7\u00e3o de convers\u00e3o, automa\u00e7\u00f5es e melhorias estrat\u00e9gicas.",
  };
}

function gerarRecomendacoes(respostas) {
  const recomendacoes = [];

  if (respostas.perfil_google !== "Sim") {
    recomendacoes.push("Sua empresa pode ganhar mais visibilidade com um Perfil da Empresa no Google bem estruturado.");
  }

  if (respostas.google_ads !== "Sim") {
    recomendacoes.push("Google Ads pode acelerar a capta\u00e7\u00e3o quando existe uma oferta clara e uma p\u00e1gina preparada para converter.");
  }

  if (respostas.site_profissional !== "Sim") {
    recomendacoes.push("Um site profissional pode centralizar sua presen\u00e7a digital e transformar visitas em contatos.");
  }

  if (respostas.visitas_site !== "Sim") {
    recomendacoes.push("Mensurar acessos e contatos ajuda a entender quais canais realmente geram resultado.");
  }

  if (respostas.contatos_google !== "Sim") {
    recomendacoes.push("Acompanhar contatos vindos do Google ajuda a medir o retorno da sua presen\u00e7a digital.");
  }

  if (respostas.whatsapp_contatos !== "Sim") {
    recomendacoes.push("Seu WhatsApp pode ser melhor posicionado para receber mais pedidos de orcamento.");
  }

  if (recomendacoes.length === 0) {
    recomendacoes.push("Sua base digital est\u00e1 bem estruturada. A RWDEV pode ajudar a refinar SEO, automa\u00e7\u00f5es, campanhas e convers\u00e3o.");
  }

  return recomendacoes;
}

function montarMensagemWhatsapp(dados, respostas, pontos, diagnostico, recomendacoes) {
  const linhasRespostas = perguntasDiagnostico.map((pergunta) => {
    return `- ${pergunta.texto} ${respostas[pergunta.nome]}`;
  });

  return [
    "Ol\u00e1, RWDEV! Quero solicitar uma an\u00e1lise gratuita com base no meu diagn\u00f3stico digital.",
    "",
    "*Dados da empresa*",
    `Empresa: ${dados.empresa}`,
    `Respons\u00e1vel: ${dados.responsavel}`,
    `Cidade: ${dados.cidade}`,
    `WhatsApp: ${dados.whatsapp}`,
    `E-mail: ${dados.email || "Nao informado"}`,
    "",
    "*Resultado*",
    `Pontua\u00e7\u00e3o final: ${pontos}/100`,
    `Diagn\u00f3stico: ${diagnostico.titulo}`,
    diagnostico.texto,
    diagnostico.complemento,
    "",
    "*Recomenda\u00e7\u00f5es*",
    ...recomendacoes.map((item) => `- ${item}`),
    "",
    "*Respostas*",
    ...linhasRespostas,
  ].join("\n");
}

function validarDiagnostico() {
  const primeiraSemResposta = perguntasDiagnostico.find((pergunta) => !respostaSelecionada(pergunta.nome));

  if (primeiraSemResposta) {
    return "Responda todas as perguntas para gerar seu diagn\u00f3stico.";
  }

  for (const nome of ["empresa", "responsavel", "whatsapp", "cidade"]) {
    if (!valorCampo(nome)) {
      return "Preencha todos os dados obrigatorios.";
    }
  }

  return "";
}

function dadosDiagnostico() {
  return {
    empresa: valorCampo("empresa"),
    responsavel: valorCampo("responsavel"),
    whatsapp: valorCampo("whatsapp"),
    cidade: valorCampo("cidade"),
    email: valorCampo("email"),
  };
}

function respostasDiagnostico() {
  const respostas = {};
  let pontos = 0;

  perguntasDiagnostico.forEach((pergunta) => {
    const resposta = respostaSelecionada(pergunta.nome);
    respostas[pergunta.nome] = resposta ? resposta.value : "";
    pontos += resposta ? Number(resposta.dataset.pontos || 0) : 0;
  });

  return { respostas, pontos };
}

function exibirResultado() {
  const erro = validarDiagnostico();

  if (erro) {
    alertaDiagnostico.textContent = erro;
    return;
  }

  alertaDiagnostico.textContent = "";
  botaoSairDiagnostico.hidden = true;

  const dados = dadosDiagnostico();
  const { respostas, pontos } = respostasDiagnostico();
  const diagnostico = diagnosticoPorPontuacao(pontos);
  const recomendacoes = gerarRecomendacoes(respostas);
  const mensagem = montarMensagemWhatsapp(dados, respostas, pontos, diagnostico, recomendacoes);

  tituloResultado.textContent = diagnostico.titulo;
  pontuacaoResultado.textContent = String(pontos);
  resumoResultado.textContent = diagnostico.texto;

  if (mensagemFinalResultado) {
    mensagemFinalResultado.textContent = diagnostico.complemento;
  }

  listaRecomendacoes.innerHTML = recomendacoes.map((item) => `<li>${item}</li>`).join("");
  linkWhatsappDiagnostico.href = `https://wa.me/${telefoneRwdev}?text=${encodeURIComponent(mensagem)}`;

  if (formDiagnostico) {
    formDiagnostico.hidden = true;
  }

  resultadoDiagnostico.hidden = false;

  if (!conclusaoDiagnosticoRegistrada) {
    conclusaoDiagnosticoRegistrada = true;
    registrarMetricaDiagnostico("diagnosis_completed", {
      lead: {
        dados,
        respostas,
        pontuacao: pontos,
      },
    });
  }

  window.requestAnimationFrame(() => {
    barraResultado.style.width = `${pontos}%`;
    rolarModalParaTopo();
  });
}

function processarDiagnostico() {
  if (!processamentoDiagnostico) {
    exibirResultado();
    return;
  }

  definirEtapaAtiva(processamentoDiagnostico);
  etapaDiagnostico.textContent = "An\u00e1lise em andamento";
  progressoDiagnostico.style.width = "100%";
  botaoSairDiagnostico.hidden = true;

  if (percentualDiagnostico) {
    percentualDiagnostico.textContent = "100%";
  }

  rolarModalParaTopo();
  setTimeout(exibirResultado, 2000);
}

function avancarAposResposta(evento) {
  const alvo = evento.target;

  if (!alvo || alvo.type !== "radio") {
    return;
  }

  registrarInicioDiagnostico();
  alertaDiagnostico.textContent = "";

  if (avancandoPergunta) {
    return;
  }

  avancandoPergunta = true;
  alvo.closest("label")?.classList.add("selecionando");

  window.setTimeout(() => {
    if (perguntaAtual < perguntasDiagnostico.length - 1) {
      perguntaAtual += 1;
      definirEtapaAtiva(etapaPergunta(perguntaAtual));
      atualizarProgressoDiagnostico();
      rolarModalParaTopo();
    } else {
      mostrarCampoDeDados(0);
    }

    avancandoPergunta = false;
  }, 300);
}

function criarControleSaida() {
  const botaoSair = document.createElement("button");
  botaoSair.type = "button";
  botaoSair.className = "diagnostico-sair";
  botaoSair.textContent = "Sair do diagn\u00f3stico";
  botaoSair.hidden = true;
  botaoSair.id = "diagnosticoSair";
  formDiagnostico.appendChild(botaoSair);

  modalConfirmacaoSaida = document.createElement("div");
  modalConfirmacaoSaida.className = "diagnostico-modal";
  modalConfirmacaoSaida.id = "diagnosticoModalSair";
  modalConfirmacaoSaida.setAttribute("aria-hidden", "true");
  modalConfirmacaoSaida.innerHTML = `
    <div class="diagnostico-modal-card" role="dialog" aria-modal="true" aria-labelledby="diagnosticoModalTitulo">
      <h3 id="diagnosticoModalTitulo">Deseja sair do diagn\u00f3stico?</h3>
      <p>Voc\u00ea est\u00e1 a poucos passos de descobrir oportunidades importantes para sua empresa aparecer melhor no Google e gerar mais contatos pelo WhatsApp.</p>
      <div class="diagnostico-modal-acoes">
        <button class="diagnostico-submit" id="diagnosticoContinuarModal" type="button">Continuar diagn\u00f3stico</button>
        <button class="diagnostico-modal-sair" id="diagnosticoConfirmarSair" type="button">Sair mesmo assim</button>
      </div>
    </div>
  `;
  document.body.appendChild(modalConfirmacaoSaida);

  botaoSair.addEventListener("click", pedirConfirmacaoSaida);

  modalConfirmacaoSaida.querySelector("#diagnosticoContinuarModal")?.addEventListener("click", fecharConfirmacaoSaida);
  modalConfirmacaoSaida.querySelector("#diagnosticoConfirmarSair")?.addEventListener("click", () => {
    fecharConfirmacaoSaida();
    fecharModalDiagnostico();
  });

  return botaoSair;
}

function pedirConfirmacaoSaida() {
  if (!modalConfirmacaoSaida || !modalFluxoDiagnostico?.classList.contains("ativo")) {
    return;
  }

  modalConfirmacaoSaida.classList.add("ativo");
  modalConfirmacaoSaida.setAttribute("aria-hidden", "false");
}

function fecharConfirmacaoSaida() {
  if (!modalConfirmacaoSaida) {
    return;
  }

  modalConfirmacaoSaida.classList.remove("ativo");
  modalConfirmacaoSaida.setAttribute("aria-hidden", "true");
}

function resetarDiagnosticoVisual() {
  perguntaAtual = -1;
  dadoAtual = -1;
  avancandoPergunta = false;

  if (alertaDiagnostico) {
    alertaDiagnostico.textContent = "";
  }

  if (resultadoDiagnostico) {
    resultadoDiagnostico.hidden = true;
  }

  if (formDiagnostico) {
    formDiagnostico.hidden = false;
    formDiagnostico.reset();
    formDiagnostico.querySelectorAll(".selecionando").forEach((label) => {
      label.classList.remove("selecionando");
    });
    definirEtapaAtiva(null);
  }

  alternarProgresso(false);

  if (botaoSairDiagnostico) {
    botaoSairDiagnostico.hidden = true;
  }

  if (barraResultado) {
    barraResultado.style.width = "0%";
  }

  atualizarProgressoDiagnostico();
}

if (formDiagnostico) {
  prepararPaginaCompacta();
  prepararCamposDeDados();
  criarModalFluxo();
  botaoSairDiagnostico = criarControleSaida();
  alternarProgresso(false);
  registrarMetricaDiagnostico("page_view");
  formDiagnostico.addEventListener("change", avancarAposResposta);
  formDiagnostico.addEventListener("submit", (evento) => {
    evento.preventDefault();
  });
  botaoIniciarPerguntas?.setAttribute("hidden", "true");
  botaoAbrirDiagnostico?.addEventListener("click", abrirModalDiagnostico);
  atualizarProgressoDiagnostico();
}

if (linkWhatsappDiagnostico) {
  linkWhatsappDiagnostico.addEventListener("click", () => {
    registrarMetricaDiagnostico("whatsapp_click");
  });
}
