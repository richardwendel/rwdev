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

  function atualizarTrajetos(form, limparSelecao) {
    var origemJson = form.querySelector('[data-ponto-trajetos-json]');
    var lojaSelect = form.querySelector('[data-ponto-loja]');
    var avisoSemTrajeto = form.querySelector('[data-ponto-sem-trajeto]');
    var trajetos = {};

    if (origemJson) {
      try {
        trajetos = JSON.parse(origemJson.textContent || '{}');
      } catch (erro) {
        trajetos = {};
      }
    }

    if (!lojaSelect) {
      return;
    }

    var lojaId = lojaSelect.value;
    var opcoes = trajetos[lojaId] || [];

    form.querySelectorAll('[data-ponto-trajeto]').forEach(function (select) {
      var selecionado = limparSelecao ? '' : (select.value || select.getAttribute('data-selected') || '');
      select.innerHTML = '';

      var opcaoVazia = document.createElement('option');
      opcaoVazia.value = '';
      opcaoVazia.textContent = lojaId ? 'Selecione' : 'Selecione a loja primeiro';
      select.appendChild(opcaoVazia);

      opcoes.forEach(function (trajeto) {
        var option = document.createElement('option');
        option.value = String(trajeto.id);
        option.textContent = trajeto.rotulo;
        option.selected = String(trajeto.id) === String(selecionado);
        select.appendChild(option);
      });

    });

    if (avisoSemTrajeto) {
      avisoSemTrajeto.hidden = !lojaId || opcoes.length > 0;
    }
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

    atualizarTrajetos(form, false);

    var lojaSelect = form.querySelector('[data-ponto-loja]');
    if (lojaSelect) {
      lojaSelect.addEventListener('change', function () {
        atualizarTrajetos(form, true);
      });
    }

    form.querySelectorAll('[data-ponto-trajeto]').forEach(function (select) {
      select.addEventListener('change', function () {
        atualizarTrajetos(form, false);
      });
    });

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
