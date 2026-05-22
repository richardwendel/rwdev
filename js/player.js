const rwdevAudio = document.getElementById("rwdevAudio");
const rwdevPlayerBtn = document.getElementById("rwdevPlayerBtn");

if (rwdevAudio && rwdevPlayerBtn) {
  const rwdevPlayerTexto = rwdevPlayerBtn.querySelector(".rwdev-player-texto");
  const chaveTempo = "rwdev_musica_tempo";

  const tempoSalvo = localStorage.getItem(chaveTempo);

  if (tempoSalvo) {
    rwdevAudio.currentTime = parseFloat(tempoSalvo);
  }

  function atualizarBotao() {
    const estaPausado = rwdevAudio.paused;

    rwdevPlayerBtn.classList.toggle("tocando", !estaPausado);
    rwdevPlayerBtn.setAttribute("aria-label", estaPausado ? "Tocar música do site" : "Pausar música do site");
    rwdevPlayerTexto.textContent = estaPausado ? "Ouvir trilha" : "Pausar trilha";
  }

  rwdevPlayerBtn.addEventListener("click", async () => {
    try {
      if (rwdevAudio.paused) {
        await rwdevAudio.play();
      } else {
        rwdevAudio.pause();
      }
    } catch (erro) {
      console.log("Não foi possível controlar a trilha do site.", erro);
    }

    atualizarBotao();
  });

  rwdevAudio.addEventListener("timeupdate", () => {
    localStorage.setItem(chaveTempo, rwdevAudio.currentTime);
  });

  window.addEventListener("beforeunload", () => {
    localStorage.setItem(chaveTempo, rwdevAudio.currentTime);
  });

  atualizarBotao();
}
