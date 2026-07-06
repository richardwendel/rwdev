(function () {
  var dias = [
    'Domingo',
    'Segunda-feira',
    'Terça-feira',
    'Quarta-feira',
    'Quinta-feira',
    'Sexta-feira',
    'Sábado'
  ];

  function doisDigitos(valor) {
    valor = String(valor);
    return valor.length < 2 ? '0' + valor : valor;
  }

  function horaAtual() {
    var agora = new Date();
    return [
      doisDigitos(agora.getHours()),
      doisDigitos(agora.getMinutes()),
      doisDigitos(agora.getSeconds())
    ].join(':');
  }

  function atualizarDiaSemana(input, destino) {
    if (!input || !destino || !input.value) {
      if (destino) {
        destino.textContent = '';
      }
      return;
    }

    var partes = input.value.split('-').map(Number);
    var data = new Date(partes[0], partes[1] - 1, partes[2]);
    destino.textContent = dias[data.getDay()] || '';
  }

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-ponto-form]');

    if (!form) {
      return;
    }

    var dataInput = form.querySelector('[data-ponto-data]');
    var diaSemana = form.querySelector('[data-ponto-dia-semana]');
    atualizarDiaSemana(dataInput, diaSemana);

    if (dataInput) {
      dataInput.addEventListener('change', function () {
        atualizarDiaSemana(dataInput, diaSemana);
      });
    }

    form.querySelectorAll('[data-ponto-agora]').forEach(function (botao) {
      botao.addEventListener('click', function () {
        var nomeCampo = botao.getAttribute('data-ponto-agora');
        var campo = form.querySelector('[name="' + nomeCampo + '"]');

        if (campo) {
          campo.value = horaAtual();
          campo.focus();
        }
      });
    });
  });
}());
