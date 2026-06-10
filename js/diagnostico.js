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

const perguntasDiagnostico = [
  { nome: "google_aparece", texto: "Quando alguem pesquisa sua empresa no Google, ela aparece?" },
  { nome: "whatsapp_contatos", texto: "Voce recebe contatos pelo WhatsApp regularmente?" },
  { nome: "perfil_google", texto: "Sua empresa possui Perfil da Empresa no Google?" },
  { nome: "instagram_ativo", texto: "Sua empresa possui Instagram ativo?" },
  { nome: "google_ads", texto: "Voce ja anunciou no Google Ads?" },
  { nome: "site_profissional", texto: "Voce possui um site profissional?" },
  { nome: "visitas_site", texto: "Voce sabe quantas pessoas visitam seu site por mes?" },
  { nome: "contatos_google", texto: "Voce acompanha quantos contatos chegam atraves do Google?" },
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
  if (!formDiagnostico || !elementoAtivo) {
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

    etapaDiagnostico.textContent = "Dados para analise";
    progressoDiagnostico.style.width = `${percentual}%`;

    if (percentualDiagnostico) {
      percentualDiagnostico.textContent = `${percentual}%`;
    }

    return;
  }

  if (perguntaAtual < 0) {
    progressoDiagnostico.style.width = "0%";
    etapaDiagnostico.textContent = "Diagnostico gratuito";

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

function rolarParaQuestionario() {
  formDiagnostico?.scrollIntoView({ behavior: "smooth", block: "center" });
}

function criarTelaInicial() {
  if (!formDiagnostico || formDiagnostico.querySelector("[data-etapa='inicio']")) {
    return;
  }

  const telaInicial = document.createElement("section");
  telaInicial.className = "diagnostico-inicio diagnostico-etapa ativa";
  telaInicial.dataset.etapa = "inicio";
  telaInicial.innerHTML = `
    <span class="diagnostico-etapa-icone" aria-hidden="true">🚀</span>
    <h3>Diagnóstico Gratuito de Presença Digital</h3>
    <p>Descubra em menos de 1 minuto se sua empresa está aparecendo bem no Google, aproveitando o WhatsApp e preparada para gerar mais oportunidades.</p>
    <div class="diagnostico-beneficios">
      <span>Visibilidade no Google</span>
      <span>Captação pelo WhatsApp</span>
      <span>Oportunidades de crescimento</span>
    </div>
  `;

  formDiagnostico.insertBefore(telaInicial, progressoTopo || formDiagnostico.firstChild);

  if (botaoIniciarPerguntas) {
    telaInicial.appendChild(botaoIniciarPerguntas);
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
  rolarParaQuestionario();

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
      return "Informe um e-mail valido ou deixe o campo em branco.";
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
  alternarProgresso(true);
  botaoSairDiagnostico.hidden = false;
  definirEtapaAtiva(etapaPergunta(perguntaAtual));
  atualizarProgressoDiagnostico();
  rolarParaQuestionario();
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
      titulo: "🔴 Oportunidade Crítica",
      texto: "Sua empresa possui oportunidades importantes de melhoria digital. Existem pontos que podem estar reduzindo sua visibilidade e geração de contatos.",
      complemento: "Inicie uma conversa com a RWDEV e descubra possíveis soluções para aumentar seus resultados.",
    };
  }

  if (pontos <= 70) {
    return {
      titulo: "🟡 Potencial de Crescimento",
      texto: "Sua empresa já possui uma boa base digital, porém ainda existem oportunidades para ampliar sua presença online e aumentar a geração de leads.",
      complemento: "A RWDEV pode ajudar a identificar ações práticas para acelerar esse crescimento.",
    };
  }

  return {
    titulo: "🟢 Presença Digital Sólida",
    texto: "Parabéns! Sua empresa demonstra uma presença digital sólida.",
    complemento: "Mesmo empresas com boa estrutura podem alcançar resultados ainda melhores através de SEO avançado, Google Ads, otimização de conversão, automações e melhorias estratégicas.",
  };
}

function gerarRecomendacoes(respostas) {
  const recomendacoes = [];

  if (respostas.perfil_google !== "Sim") {
    recomendacoes.push("Sua empresa pode ganhar mais visibilidade com um Perfil da Empresa no Google bem estruturado.");
  }

  if (respostas.google_ads !== "Sim") {
    recomendacoes.push("Google Ads pode acelerar a captacao quando existe uma oferta clara e uma pagina preparada para converter.");
  }

  if (respostas.site_profissional !== "Sim") {
    recomendacoes.push("Um site profissional pode centralizar sua presenca digital e transformar visitas em contatos.");
  }

  if (respostas.visitas_site !== "Sim") {
    recomendacoes.push("Mensurar acessos e contatos ajuda a entender quais canais realmente geram resultado.");
  }

  if (respostas.contatos_google !== "Sim") {
    recomendacoes.push("Acompanhar contatos vindos do Google ajuda a medir o retorno da sua presenca digital.");
  }

  if (respostas.whatsapp_contatos !== "Sim") {
    recomendacoes.push("Seu WhatsApp pode ser melhor posicionado para receber mais pedidos de orcamento.");
  }

  if (recomendacoes.length === 0) {
    recomendacoes.push("Sua base digital esta bem estruturada. A RWDEV pode ajudar a refinar SEO, automacoes, campanhas e conversao.");
  }

  return recomendacoes;
}

function montarMensagemWhatsapp(dados, respostas, pontos, diagnostico, recomendacoes) {
  const linhasRespostas = perguntasDiagnostico.map((pergunta) => {
    return `- ${pergunta.texto} ${respostas[pergunta.nome]}`;
  });

  return [
    "Ola, RWDEV! Quero solicitar uma analise gratuita com base no meu diagnostico digital.",
    "",
    "*Dados da empresa*",
    `Empresa: ${dados.empresa}`,
    `Responsavel: ${dados.responsavel}`,
    `Cidade: ${dados.cidade}`,
    `WhatsApp: ${dados.whatsapp}`,
    `E-mail: ${dados.email || "Nao informado"}`,
    "",
    "*Resultado*",
    `Pontuacao final: ${pontos}/100`,
    `Diagnostico: ${diagnostico.titulo}`,
    diagnostico.texto,
    diagnostico.complemento,
    "",
    "*Recomendacoes*",
    ...recomendacoes.map((item) => `- ${item}`),
    "",
    "*Respostas*",
    ...linhasRespostas,
  ].join("\n");
}

function validarDiagnostico() {
  const primeiraSemResposta = perguntasDiagnostico.find((pergunta) => !respostaSelecionada(pergunta.nome));

  if (primeiraSemResposta) {
    return "Responda todas as perguntas para gerar seu diagnostico.";
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
  });

  resultadoDiagnostico.scrollIntoView({ behavior: "smooth", block: "start" });
}

function processarDiagnostico() {
  if (!processamentoDiagnostico) {
    exibirResultado();
    return;
  }

  definirEtapaAtiva(processamentoDiagnostico);
  etapaDiagnostico.textContent = "Analise em andamento";
  progressoDiagnostico.style.width = "100%";
  botaoSairDiagnostico.hidden = true;

  if (percentualDiagnostico) {
    percentualDiagnostico.textContent = "100%";
  }

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
      rolarParaQuestionario();
    } else {
      mostrarCampoDeDados(0);
    }

    avancandoPergunta = false;
  }, 320);
}

function criarControleSaida() {
  const botaoSair = document.createElement("button");
  botaoSair.type = "button";
  botaoSair.className = "diagnostico-sair";
  botaoSair.textContent = "Sair do diagnóstico";
  botaoSair.hidden = true;
  botaoSair.id = "diagnosticoSair";
  formDiagnostico.appendChild(botaoSair);

  const modal = document.createElement("div");
  modal.className = "diagnostico-modal";
  modal.id = "diagnosticoModalSair";
  modal.setAttribute("aria-hidden", "true");
  modal.innerHTML = `
    <div class="diagnostico-modal-card" role="dialog" aria-modal="true" aria-labelledby="diagnosticoModalTitulo">
      <h3 id="diagnosticoModalTitulo">Deseja sair do diagnóstico?</h3>
      <p>Você está a poucos passos de descobrir oportunidades importantes para sua empresa aparecer melhor no Google e gerar mais contatos pelo WhatsApp.</p>
      <div class="diagnostico-modal-acoes">
        <button class="diagnostico-submit" id="diagnosticoContinuarModal" type="button">Continuar diagnóstico</button>
        <button class="diagnostico-modal-sair" id="diagnosticoConfirmarSair" type="button">Sair mesmo assim</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);

  botaoSair.addEventListener("click", () => {
    modal.classList.add("ativo");
    modal.setAttribute("aria-hidden", "false");
  });

  modal.querySelector("#diagnosticoContinuarModal")?.addEventListener("click", () => {
    modal.classList.remove("ativo");
    modal.setAttribute("aria-hidden", "true");
  });

  modal.querySelector("#diagnosticoConfirmarSair")?.addEventListener("click", () => {
    modal.classList.remove("ativo");
    modal.setAttribute("aria-hidden", "true");
    resetarDiagnosticoVisual();
  });

  return botaoSair;
}

function resetarDiagnosticoVisual() {
  perguntaAtual = -1;
  dadoAtual = -1;
  avancandoPergunta = false;
  alertaDiagnostico.textContent = "";
  resultadoDiagnostico.hidden = true;
  formDiagnostico.reset();
  formDiagnostico.querySelectorAll(".selecionando").forEach((label) => {
    label.classList.remove("selecionando");
  });
  alternarProgresso(false);
  definirEtapaAtiva(formDiagnostico.querySelector("[data-etapa='inicio']"));
  botaoSairDiagnostico.hidden = true;
  atualizarProgressoDiagnostico();
  rolarParaQuestionario();
}

let botaoSairDiagnostico = null;

if (formDiagnostico) {
  criarTelaInicial();
  prepararCamposDeDados();
  botaoSairDiagnostico = criarControleSaida();
  alternarProgresso(false);
  registrarMetricaDiagnostico("page_view");
  formDiagnostico.addEventListener("change", avancarAposResposta);
  formDiagnostico.addEventListener("submit", (evento) => {
    evento.preventDefault();
  });
  botaoIniciarPerguntas?.addEventListener("click", iniciarPerguntas);
  atualizarProgressoDiagnostico();
}

if (linkWhatsappDiagnostico) {
  linkWhatsappDiagnostico.addEventListener("click", () => {
    registrarMetricaDiagnostico("whatsapp_click");
  });
}
