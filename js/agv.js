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
