const formContato = document.getElementById("formContato");
const mensagemFormulario = document.getElementById("mensagemFormulario");

if (formContato) {
  formContato.addEventListener("submit", (evento) => {
    evento.preventDefault();

    const dados = new FormData(formContato);
    const nome = dados.get("nome").trim();
    const email = dados.get("email").trim();
    const servico = dados.get("servico").trim();
    const mensagem = dados.get("mensagem").trim();

    if (!nome || !email || !servico || !mensagem) {
      mensagemFormulario.textContent = "Preencha todos os campos para enviar a mensagem.";
      return;
    }

    const texto = [
      "Olá, vim pelo site RWDEV e quero solicitar um orçamento.",
      "",
      `Nome: ${nome}`,
      `Email: ${email}`,
      `Serviço desejado: ${servico}`,
      "",
      `Mensagem: ${mensagem}`,
      "",
      "Estou ciente de que meus dados serão usados pela RWDEV para responder esta solicitação."
    ].join("\n");

    const linkWhatsApp = `https://wa.me/5511981104971?text=${encodeURIComponent(texto)}`;

    mensagemFormulario.textContent = "Abrindo WhatsApp com sua mensagem pronta...";
    window.open(linkWhatsApp, "_blank", "noopener,noreferrer");
  });
}
