// =========================================
// DIAGNOSTICO DIGITAL RWDEV
// =========================================

const formDiagnostico = document.getElementById("formDiagnostico");
const alertaDiagnostico = document.getElementById("diagnosticoAlerta");
const progressoDiagnostico = document.getElementById("diagnosticoProgresso");
const etapaDiagnostico = document.getElementById("diagnosticoEtapaTexto");
const resultadoDiagnostico = document.getElementById("diagnosticoResultado");
const tituloResultado = document.getElementById("diagnosticoTituloResultado");
const pontuacaoResultado = document.getElementById("diagnosticoPontuacao");
const barraResultado = document.getElementById("diagnosticoBarraResultado");
const resumoResultado = document.getElementById("diagnosticoResumo");
const listaRecomendacoes = document.getElementById("diagnosticoRecomendacoes");
const linkWhatsappDiagnostico = document.getElementById("diagnosticoWhatsapp");

const endpointMetricasDiagnostico = "api/track-diagnostico.php";
const telefoneRwdev = "5511981104971";
let inicioDiagnosticoRegistrado = false;
let conclusaoDiagnosticoRegistrada = false;
const perguntasDiagnostico = [
  { nome: "google_aparece", texto: "Quando alguém pesquisa sua empresa no Google, ela aparece?" },
  { nome: "whatsapp_contatos", texto: "Você recebe contatos pelo WhatsApp regularmente?" },
  { nome: "perfil_google", texto: "Sua empresa possui Perfil da Empresa no Google?" },
  { nome: "instagram_ativo", texto: "Sua empresa possui Instagram ativo?" },
  { nome: "google_ads", texto: "Você já anunciou no Google Ads?" },
  { nome: "site_profissional", texto: "Você possui um site profissional?" },
  { nome: "visitas_site", texto: "Você sabe quantas pessoas visitam seu site por mês?" },
  { nome: "contatos_google", texto: "Você acompanha quantos contatos chegam através do Google?" },
];

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

function atualizarProgressoDiagnostico() {
  if (!formDiagnostico || !progressoDiagnostico || !etapaDiagnostico) {
    return;
  }

  const respostas = perguntasDiagnostico.filter((pergunta) => respostaSelecionada(pergunta.nome)).length;
  const percentual = Math.round((respostas / perguntasDiagnostico.length) * 100);

  progressoDiagnostico.style.width = `${percentual}%`;
  etapaDiagnostico.textContent = `${respostas} de ${perguntasDiagnostico.length} respostas`;
}

function registrarInicioDiagnostico(evento) {
  const alvo = evento.target;

  if (!inicioDiagnosticoRegistrado && alvo && alvo.name === "google_aparece") {
    inicioDiagnosticoRegistrado = true;
    registrarMetricaDiagnostico("diagnosis_start");
  }
}

function diagnosticoPorPontuacao(pontos) {
  if (pontos >= 80) {
    return {
      titulo: "🟢 Excelente presença digital",
      texto: "Sua empresa já possui uma base digital forte. O próximo passo é otimizar canais, acompanhar métricas e transformar essa presença em mais contatos qualificados.",
    };
  }

  if (pontos >= 50) {
    return {
      titulo: "🟡 Sua empresa possui potencial de crescimento digital",
      texto: "Seu negócio já tem alguns canais importantes, mas ainda existem oportunidades para melhorar presença no Google, site, anúncios, mensuração e geração de contatos.",
    };
  }

  return {
    titulo: "🔴 Sua empresa pode estar perdendo oportunidades diariamente",
    texto: "A presença digital da sua empresa precisa de atenção. Melhorar Google, site, WhatsApp e acompanhamento de contatos pode ajudar a recuperar oportunidades locais.",
  };
}

function gerarRecomendacoes(respostas) {
  const recomendacoes = [];

  if (respostas.perfil_google !== "Sim") {
    recomendacoes.push("Você não possui Perfil da Empresa no Google e pode estar perdendo clientes locais.");
  }

  if (respostas.instagram_ativo === "Sim" && respostas.google_ads !== "Sim") {
    recomendacoes.push("Seu negócio possui Instagram, mas ainda não utiliza Google Ads.");
  }

  if (respostas.site_profissional !== "Sim") {
    recomendacoes.push("Seu site pode ser otimizado para gerar mais contatos.");
  }

  if (respostas.visitas_site !== "Sim") {
    recomendacoes.push("Instalar e acompanhar métricas ajuda a entender quantas pessoas visitam seu site por mês.");
  }

  if (respostas.contatos_google !== "Sim") {
    recomendacoes.push("Acompanhar contatos vindos do Google ajuda a medir o retorno da sua presença digital.");
  }

  if (respostas.whatsapp_contatos !== "Sim") {
    recomendacoes.push("Seu WhatsApp pode ser melhor posicionado para receber mais solicitações de orçamento.");
  }

  if (recomendacoes.length === 0) {
    recomendacoes.push("Sua base digital está bem estruturada. A RWDEV pode ajudar a refinar campanhas, mensuração e conversão.");
  }

  return recomendacoes;
}

function montarMensagemWhatsapp(dados, respostas, pontos, diagnostico, recomendacoes) {
  const linhasRespostas = perguntasDiagnostico.map((pergunta) => {
    return `- ${pergunta.texto} ${respostas[pergunta.nome]}`;
  });

  return [
    "Olá, RWDEV! Quero solicitar uma análise gratuita com base no meu diagnóstico digital.",
    "",
    "*Dados da empresa*",
    `Empresa: ${dados.empresa}`,
    `Responsável: ${dados.responsavel}`,
    `Cidade: ${dados.cidade}`,
    `WhatsApp: ${dados.whatsapp}`,
    `E-mail: ${dados.email || "Não informado"}`,
    "",
    "*Resultado*",
    `Pontuação final: ${pontos}/100`,
    `Diagnóstico: ${diagnostico.titulo}`,
    diagnostico.texto,
    "",
    "*Recomendações*",
    ...recomendacoes.map((item) => `- ${item}`),
    "",
    "*Respostas*",
    ...linhasRespostas,
  ].join("\n");
}

function validarDiagnostico() {
  const primeiraSemResposta = perguntasDiagnostico.find((pergunta) => !respostaSelecionada(pergunta.nome));

  if (primeiraSemResposta) {
    return "Responda todas as perguntas para gerar seu diagnóstico.";
  }

  const camposObrigatorios = ["empresa", "responsavel", "whatsapp", "cidade"];
  const campoVazio = camposObrigatorios.find((nome) => !valorCampo(nome));

  if (campoVazio) {
    return "Preencha nome da empresa, responsável, WhatsApp e cidade.";
  }

  const telefone = valorCampo("whatsapp").replace(/\D/g, "");

  if (telefone.length < 10 || telefone.length > 13) {
    return "Informe um WhatsApp válido com DDD.";
  }

  const email = valorCampo("email");
  const campoEmail = campoFormulario("email");

  if (email && campoEmail && !campoEmail.checkValidity()) {
    return "Informe um e-mail válido ou deixe o campo em branco.";
  }

  return "";
}

function processarDiagnostico(evento) {
  evento.preventDefault();

  const erro = validarDiagnostico();

  if (erro) {
    alertaDiagnostico.textContent = erro;
    return;
  }

  alertaDiagnostico.textContent = "";

  const respostas = {};
  let pontos = 0;

  perguntasDiagnostico.forEach((pergunta) => {
    const resposta = respostaSelecionada(pergunta.nome);
    respostas[pergunta.nome] = resposta ? resposta.value : "";
    pontos += resposta ? Number(resposta.dataset.pontos || 0) : 0;
  });

  const dados = {
    empresa: valorCampo("empresa"),
    responsavel: valorCampo("responsavel"),
    whatsapp: valorCampo("whatsapp"),
    cidade: valorCampo("cidade"),
    email: valorCampo("email"),
  };
  const diagnostico = diagnosticoPorPontuacao(pontos);
  const recomendacoes = gerarRecomendacoes(respostas);
  const mensagem = montarMensagemWhatsapp(dados, respostas, pontos, diagnostico, recomendacoes);

  tituloResultado.textContent = diagnostico.titulo;
  pontuacaoResultado.textContent = String(pontos);
  resumoResultado.textContent = diagnostico.texto;
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

if (formDiagnostico) {
  registrarMetricaDiagnostico("page_view");
  formDiagnostico.addEventListener("change", registrarInicioDiagnostico);
  formDiagnostico.addEventListener("change", atualizarProgressoDiagnostico);
  formDiagnostico.addEventListener("submit", processarDiagnostico);
  atualizarProgressoDiagnostico();
}

if (linkWhatsappDiagnostico) {
  linkWhatsappDiagnostico.addEventListener("click", () => {
    registrarMetricaDiagnostico("whatsapp_click");
  });
}
