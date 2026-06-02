# RWDEV

Portal institucional da RWDEV para apresentação de serviços de desenvolvimento web e soluções digitais. O projeto reúne uma vitrine profissional para sites e sistemas web, SEO, Google Ads e integração com WhatsApp, além de um sistema de depoimentos e um portal de atendimento a clientes.

🌐 **Projeto online:** [https://rwdev.com.br](https://rwdev.com.br)

## Funcionalidades

### Site institucional

- Página inicial com apresentação da RWDEV e portfólio.
- Páginas de serviços, contato, parceiros, depoimentos e política de privacidade.
- Divulgação de soluções para sites profissionais, sistemas web, SEO e Google Ads.
- Formulário de contato com direcionamento para o WhatsApp.
- Atalhos de contato via WhatsApp nas páginas públicas.
- Configuração de SEO com metadados, `robots.txt` e `sitemap.xml`.

### Sistema de depoimentos

- Envio público de depoimentos com foto opcional.
- Validação e armazenamento de uploads.
- Publicação somente após aprovação administrativa.
- Resposta opcional da RWDEV aos depoimentos aprovados.
- Reações de visitantes aos depoimentos publicados.
- Área administrativa para aprovar, recusar ou excluir depoimentos.

### Portal RWDEV

- Login separado para clientes e administradores.
- Cadastro de clientes por convite privado.
- Área administrativa para gestão de clientes, convites e projetos.
- Área do cliente com visualização de projetos.
- Criação e acompanhamento de solicitações de alteração.
- Upload de anexos em solicitações.
- Atualização administrativa do status e da resposta de cada solicitação.

## Tecnologias Utilizadas

- HTML5
- CSS3
- JavaScript
- PHP
- MySQL
- SEO
- Git e GitHub

## Estrutura do Projeto

```text
rwdev/
├── audio/                         # Arquivos de áudio do site
├── css/                           # Estilos do site institucional
├── images/                        # Logos, ícones e imagens do portfólio
├── js/                            # Scripts do site institucional
├── portal/
│   ├── admin/                     # Área administrativa
│   ├── assets/                    # CSS e JavaScript do portal
│   ├── cliente/                   # Área autenticada do cliente
│   ├── config/                    # Conexão com o banco de dados
│   ├── includes/                  # Funções, autenticação e notificações
│   └── uploads/solicitacoes/      # Anexos enviados em solicitações
├── uploads/depoimentos/           # Fotos enviadas com depoimentos
├── index.html                     # Página inicial
├── servicos.html                  # Serviços oferecidos
├── contato.html                   # Página de contato
├── parceiros.html                 # Parceiros
├── depoimentos.html               # Envio e exibição de depoimentos
├── politica-de-privacidade.html   # Política de privacidade
├── salvar_depoimento.php          # Recebimento de depoimentos
├── listar_depoimentos.php         # Listagem pública de depoimentos aprovados
├── salvar_reacao_depoimento.php   # Registro de reações
├── banco.sql                      # Estrutura principal do banco
├── banco-depoimentos.sql          # Estrutura do módulo de depoimentos
├── banco-convites.sql             # Estrutura do módulo de convites
├── robots.txt                     # Orientações para mecanismos de busca
├── sitemap.xml                    # Mapa do site para SEO
└── .env.example                   # Modelo de variáveis de ambiente
```

## Segurança

As credenciais reais de ambiente não devem ser versionadas. O arquivo `.env` é ignorado pelo Git e o arquivo `.env.example` funciona somente como modelo para a configuração local.

Antes de publicar alterações, confirme que dados sensíveis, senhas e credenciais não foram adicionados ao histórico do repositório.

## Configuração Local

### Pré-requisitos

- Servidor local compatível com PHP
- MySQL
- Git

### Instalação

1. Clone o repositório:

```bash
git clone <url-do-repositorio>
cd rwdev
```

2. Crie um arquivo `.env` local baseado no modelo `.env.example`.

3. Preencha no `.env` local as configurações do banco de dados do seu ambiente.

4. Crie o banco de dados e importe os scripts SQL necessários:

```text
banco.sql
banco-depoimentos.sql
banco-convites.sql
```

5. Sirva o diretório do projeto em um ambiente local com PHP e MySQL configurados.

6. Acesse a página inicial pelo endereço definido no seu servidor local.

> O arquivo `banco.sql` contém a estrutura principal. Os demais scripts SQL podem ser utilizados conforme a necessidade de instalação ou atualização dos módulos.

## Autor

**Ricardo Sousa**  
Fundador da RWDEV

---

**RWDEV: transformando ideias em soluções digitais profissionais, funcionais e preparadas para gerar resultados.**
