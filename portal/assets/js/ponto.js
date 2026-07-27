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
    var statusSelect = form.querySelector('[data-ponto-status]');
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

    if (statusSelect && ['trabalhado', 'feriado_trabalhado'].indexOf(statusSelect.value) === -1) {
      form.querySelectorAll('[data-ponto-trajeto]').forEach(function (select) {
        select.innerHTML = '<option value="">Selecione a loja primeiro</option>';
      });

      if (avisoSemTrajeto) {
        avisoSemTrajeto.hidden = true;
      }

      return;
    }

    var lojaId = lojaSelect.value;
    var opcaoLoja = lojaSelect.options[lojaSelect.selectedIndex];
    var lojaCodigo = opcaoLoja ? opcaoLoja.getAttribute('data-loja-codigo') : '';
    var chaveTrajetos = lojaCodigo || lojaId;
    var opcoes = trajetos[chaveTrajetos] || [];

    form.querySelectorAll('[data-ponto-trajeto]').forEach(function (select) {
      var selecionado = limparSelecao ? '' : (select.value || select.getAttribute('data-selected') || '');
      select.innerHTML = '';

      var opcaoVazia = document.createElement('option');
      opcaoVazia.value = '';
      opcaoVazia.textContent = lojaId ? 'Selecione um trajeto' : 'Selecione a loja primeiro';
      select.appendChild(opcaoVazia);

      opcoes.forEach(function (trajeto) {
        var option = document.createElement('option');
        option.value = String(trajeto.id);
        option.textContent = trajeto.rotulo;
        option.selected = String(trajeto.id) === String(selecionado);
        select.appendChild(option);
      });

    });

    if (limparSelecao) {
      var padrao = opcoes.filter(function (trajeto) { return trajeto.padrao; })[0];
      if (padrao) {
        form.querySelectorAll('[data-ponto-trajeto]').forEach(function (select) {
          select.value = String(padrao.id);
        });
      }
    }

    if (avisoSemTrajeto) {
      avisoSemTrajeto.hidden = !lojaId || opcoes.length > 0;
    }
    atualizarTransporte(form, limparSelecao);
  }

  function moedaNumero(valor) {
    return Number(valor || 0).toFixed(2).replace('.', ',');
  }

  function composicao(trajeto, direcao) {
    if (!trajeto) return 'selecione um trajeto.';
    var trechos = (trajeto.trechos || []).filter(function (trecho) { return trecho.direcao === direcao; });
    if (!trechos.length) return 'R$ ' + moedaNumero(trajeto['valor_' + direcao]) + ' (sem trechos detalhados).';
    return trechos.map(function (trecho) {
      return trecho.descricao + ': ' + trecho.quantidade + ' × R$ ' + moedaNumero(trecho.tarifa_unitaria);
    }).join(' + ');
  }

  function atualizarTransporte(form, preencherValores) {
    var origemJson = form.querySelector('[data-ponto-trajetos-json]');
    var loja = form.querySelector('[data-ponto-loja]');
    if (!origemJson || !loja) return;
    var dados = {}; try { dados = JSON.parse(origemJson.textContent || '{}'); } catch (erro) {}
    var opcao = loja.options[loja.selectedIndex];
    var codigo = opcao ? opcao.getAttribute('data-loja-codigo') : '';
    var trajetos = dados[codigo] || [];
    function selecionado(direcao) {
      var select = form.querySelector('[data-ponto-trajeto="' + direcao + '"]');
      return trajetos.filter(function (t) { return select && String(t.id) === select.value; })[0];
    }
    var ida = selecionado('ida'); var volta = selecionado('volta');
    var total = Number(ida ? ida.valor_ida : 0) + Number(volta ? volta.valor_volta : 0);
    var idaTexto = form.querySelector('[data-ponto-composicao-ida]');
    var voltaTexto = form.querySelector('[data-ponto-composicao-volta]');
    var totalTexto = form.querySelector('[data-ponto-total-transporte]');
    if (idaTexto) idaTexto.textContent = 'Ida: ' + composicao(ida, 'ida');
    if (voltaTexto) voltaTexto.textContent = 'Volta: ' + composicao(volta, 'volta');
    if (totalTexto) totalTexto.textContent = total > 0 ? 'Total diário: R$ ' + moedaNumero(total) : 'Transporte ainda não configurado.';
    if (preencherValores) {
      var previsto = form.querySelector('[data-ponto-transporte-previsto]');
      var recebido = form.querySelector('[data-ponto-transporte-recebido]');
      if (previsto) previsto.value = total > 0 ? moedaNumero(total) : '';
      if (recebido) recebido.value = total > 0 ? moedaNumero(total) : '';
    }
  }

  function atualizarStatusDia(form) {
    var statusSelect = form.querySelector('[data-ponto-status]');
    var lojaSelect = form.querySelector('[data-ponto-loja]');
    var trabalhado = !statusSelect || ['trabalhado', 'feriado_trabalhado'].indexOf(statusSelect.value) !== -1;

    form.querySelectorAll('[data-ponto-trabalhado]').forEach(function (elemento) {
      elemento.hidden = !trabalhado;

      elemento.querySelectorAll('input, select, textarea, button').forEach(function (campo) {
        campo.disabled = !trabalhado;
      });
    });

    if (lojaSelect) {
      lojaSelect.required = trabalhado;
      lojaSelect.disabled = !trabalhado;
    }

    atualizarTrajetos(form, false);
  }

  function inicializarPontoForm() {
    var form = document.querySelector('[data-ponto-form]');

    if (!form) {
      return;
    }

    if (form.getAttribute('data-ponto-js-inicializado') === '1') {
      atualizarStatusDia(form);
      return;
    }

    form.setAttribute('data-ponto-js-inicializado', '1');

    var dataInput = form.querySelector('[data-ponto-data]');
    var diaSemana = form.querySelector('[data-ponto-dia-semana]');
    atualizarDiaSemana(dataInput, diaSemana);

    if (dataInput) {
      dataInput.addEventListener('change', function () {
        atualizarDiaSemana(dataInput, diaSemana);
      });
    }

    atualizarStatusDia(form);

    var statusSelect = form.querySelector('[data-ponto-status]');
    if (statusSelect) {
      statusSelect.addEventListener('change', function () {
        atualizarStatusDia(form);
      });
    }

    var lojaSelect = form.querySelector('[data-ponto-loja]');
    if (lojaSelect) {
      lojaSelect.addEventListener('change', function () {
        atualizarTrajetos(form, true);
      });

      lojaSelect.addEventListener('input', function () {
        atualizarTrajetos(form, true);
      });
    }

    form.querySelectorAll('[data-ponto-trajeto]').forEach(function (select) {
      select.addEventListener('change', function () {
        atualizarTransporte(form, true);
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

  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarPontoForm);
  } else {
    inicializarPontoForm();
  }

  window.addEventListener('pageshow', inicializarPontoForm);
}());
