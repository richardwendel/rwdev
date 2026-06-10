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

const endpointMetricasDiagnostico = "api/track-diagnostico.php";
const telefoneRwdev = "5511981104971";
let inicioDiagnosticoRegistrado = false;
let conclusaoDiagnosticoRegistrada = false;
let perguntaAtual = -1;
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

function atualizarProgressoDiagnostico() {
  if (!progressoDiagnostico || !etapaDiagnostico) {
    return;
  }

  if (perguntaAtual < 0) {
    progressoDiagnostico.style.width = "0%";
    etapaDiagnostico.textContent = "Dados da empresa";

    if (percentualDiagnostico) {
      percentualDiagnostico.textContent = "0%";
    }

    return;
  }

  const numeroPergunta = Math.min(perguntaAtual + 1, perguntasDiagnostico.length);
  const percentual = Math.round((numeroPergunta / perguntasDiagnostico.length) * 100);

  progressoDiagnostico.style.width = `${percentual}%`;
  etapaDiagnostico.textContent = `Pergunta ${numeroPergunta} de ${perguntasDiagnostico.length}`;

  if (percentualDiagnostico) {
    percentualDiagnostico.textContent = `${percentual}%`;
  }
}

function rolarParaQuestionario() {
  formDiagnostico?.scrollIntoView({ behavior: "smooth", block: "center" });
}

function validarDadosIniciais() {
  const camposObrigatorios = ["empresa", "responsavel", "whatsapp", "cidade"];
  const campoVazio = camposObrigatorios.find((nome) => !valorCampo(nome));

  if (campoVazio) {
    return "Preencha nome da empresa, responsavel, WhatsApp e cidade.";
  }

  const telefone = valorCampo("whatsapp").replace(/\D/g, "");

  if (telefone.length < 10 || telefone.length > 13) {
    return "Informe um WhatsApp valido com DDD.";
  }

  const email = valorCampo("email");
  const campoEmail = campoFormulario("email");

  if (email && campoEmail && !campoEmail.checkValidity()) {
    return "Informe um e-mail valido ou deixe o campo em branco.";
  }

  return "";
}

function iniciarPerguntas() {
  const erro = validarDadosIniciais();

  if (erro) {
    alertaDiagnostico.textContent = erro;
    return;
  }

  alertaDiagnostico.textContent = "";
  perguntaAtual = 0;
  botaoIniciarPerguntas.hidden = true;
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
  const erroDados = validarDadosIniciais();

  if (erroDados) {
    return erroDados;
  }

  const primeiraSemResposta = perguntasDiagnostico.find((pergunta) => !respostaSelecionada(pergunta.nome));

  if (primeiraSemResposta) {
    return "Responda todas as perguntas para gerar seu diagnostico.";
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

  perguntaAtual = perguntasDiagnostico.length - 1;
  definirEtapaAtiva(processamentoDiagnostico);
  etapaDiagnostico.textContent = "Analise em andamento";
  progressoDiagnostico.style.width = "100%";

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
      processarDiagnostico();
    }

    avancandoPergunta = false;
  }, 360);
}

if (formDiagnostico) {
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
