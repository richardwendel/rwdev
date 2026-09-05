const fs = require("fs");
const vm = require("vm");

const contexto = {
  document: {
    querySelector: () => null,
    querySelectorAll: () => [],
    getElementById: () => null,
  },
};

vm.createContext(contexto);
vm.runInContext(fs.readFileSync("js/agv.js", "utf8"), contexto);

function testar(condicao, mensagem) {
  if (!condicao) throw new Error(mensagem);
}

testar(contexto.normalizarPlaca(" abc 1234 ") === "ABC-1234", "Falha na placa antiga sem hífen.");
testar(contexto.normalizarPlaca("abc-1234") === "ABC-1234", "Falha na placa antiga com hífen.");
testar(contexto.placaValida("ABC-1234"), "Placa antiga válida foi rejeitada.");
testar(contexto.normalizarPlaca("abc1d23") === "ABC1D23", "Falha na placa Mercosul.");
testar(contexto.placaValida("ABC1D23"), "Placa Mercosul válida foi rejeitada.");
testar(!contexto.placaValida("ABC12D3"), "Placa inválida foi aceita.");
testar(contexto.normalizarWhatsapp("+55 (11) 98765-4321") === "11987654321", "Falha ao normalizar WhatsApp.");
testar(contexto.whatsappValido("(11) 98765-4321"), "WhatsApp válido foi rejeitado.");
testar(!contexto.whatsappValido("11 9876-543"), "WhatsApp inválido foi aceito.");

console.log("AGV frontend: testes concluídos com sucesso.");
