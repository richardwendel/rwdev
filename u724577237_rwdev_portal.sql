-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 10-Jun-2026 às 03:47
-- Versão do servidor: 11.8.6-MariaDB-log
-- versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de dados: `u724577237_rwdev_portal`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `admins`
--

INSERT INTO `admins` (`id`, `nome`, `email`, `senha`, `criado_em`) VALUES
(1, 'RWDEV Administrador', 'rwdevtech@gmail.com', '$2y$12$sZk3w5J44s5VSUYubvX5rOesY9F.bgKvYs8AGDb/M4lha9UhVANZq', '2026-05-22 13:29:44');

-- --------------------------------------------------------

--
-- Estrutura da tabela `arquivos_solicitacao`
--

CREATE TABLE `arquivos_solicitacao` (
  `id` int(10) UNSIGNED NOT NULL,
  `solicitacao_id` int(10) UNSIGNED NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `caminho` varchar(255) NOT NULL,
  `tipo` varchar(120) NOT NULL,
  `tamanho` int(10) UNSIGNED NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `empresa` varchar(160) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `email`, `senha`, `empresa`, `telefone`, `status`, `criado_em`) VALUES
(1, 'Flavio Barbosa', 'ricardo159753@gmail.com', '$2y$10$aiQJv0nHIXt6yyniHTSbM.s8Dx1ueSVm0Q/3sQnH1nTl4hvfv0NqO', 'Santos Estrutura', '11963552384', 'ativo', '2026-05-22 17:03:05'),
(2, 'Joao', 'jctoldos@gmail.com', '$2y$10$BoRkZiPtNQZH160xl1LPcesnEMLJgY.ZZBRrdmH3E3Lq6tE3KLuOq', 'JCtoldos', '11958973342', 'ativo', '2026-05-22 18:25:58');

-- --------------------------------------------------------

--
-- Estrutura da tabela `convites_cliente`
--

CREATE TABLE `convites_cliente` (
  `id` int(10) UNSIGNED NOT NULL,
  `token` char(64) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `empresa` varchar(160) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `projeto_nome` varchar(160) DEFAULT NULL,
  `projeto_dominio` varchar(180) DEFAULT NULL,
  `projeto_descricao` text DEFAULT NULL,
  `paginas_json` text DEFAULT NULL,
  `status` enum('pendente','usado','expirado') NOT NULL DEFAULT 'pendente',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `convites_cliente`
--

INSERT INTO `convites_cliente` (`id`, `token`, `nome`, `empresa`, `email`, `telefone`, `projeto_nome`, `projeto_dominio`, `projeto_descricao`, `paginas_json`, `status`, `criado_em`, `expira_em`, `usado_em`) VALUES
(6, 'd8b225bcd88fa620febd581cdc79b75f499eb84f305233f51c123e7c83d78c91', 'Fernando', 'Comportalmentalmente', NULL, '11979930651', NULL, NULL, NULL, NULL, 'expirado', '2026-05-22 16:53:07', '2026-05-24 13:53:07', NULL),
(7, '74848da79d7c99c5fa409d1271c6b39229ff7470f5855cdbac95fb5a19a133d2', 'Flavio Barbosa', 'Santos Estrutura', NULL, '11963552384', NULL, NULL, NULL, NULL, 'usado', '2026-05-22 16:57:28', '2026-05-24 13:57:28', '2026-05-22 17:03:05'),
(8, 'a5a5fa01cf3e5bf30831ba0e723d000d7cab1ed30067c796bf9eb9f5908bf9a0', 'Joao', 'JCtoldos', 'jctoldos@gmail.com', '11958973342', 'Site JCtoldos', 'https://rwdev.com.br/jctoldos', '', '[\"Início\",\"Serviços\",\"Contato\",\"trabalhos\"]', 'usado', '2026-05-22 18:24:56', '2026-05-24 18:24:56', '2026-05-22 18:25:58'),
(9, '2932fb3d721dcff97a4e01528926cb26f6d867d8fb5bdb24a285cab96ca526fd', 'Fernando', 'Comportalmentalmente', NULL, '11979930651', 'terapeuta comportamental', 'https://comportalmentalmente.com.br', '', '[\"Início\",\"Sobre\",\"Serviços\",\"Contato\",\"Disc\",\"Conteudos\"]', 'expirado', '2026-05-27 12:00:49', '2026-05-29 12:00:49', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `depoimentos`
--

CREATE TABLE `depoimentos` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `cidade` varchar(150) NOT NULL,
  `rede_social` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `depoimento` text NOT NULL,
  `resposta_admin` text DEFAULT NULL,
  `respondido_em` datetime DEFAULT NULL,
  `tempo_conhece` varchar(100) NOT NULL,
  `autorizacao` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pendente','aprovado','recusado') NOT NULL DEFAULT 'pendente',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `depoimentos`
--

INSERT INTO `depoimentos` (`id`, `nome`, `cidade`, `rede_social`, `foto`, `depoimento`, `resposta_admin`, `respondido_em`, `tempo_conhece`, `autorizacao`, `status`, `criado_em`) VALUES
(1, 'CLAUDIO DO NASCIMENTO FERREIRA', 'Suzano', '', NULL, 'empresa muito profissional dedicada...que realmente fez a diferença em meu estúdio, presta serviços de qualidade.', 'na verdade o claudio que me conhece kkkk, eu tinha uns 8 anos e ele ja tocava na igreja onde eu congregava kkk, e quando eu tinha uns 16 anos começei pegar aulas de teclado com o irmão dele, depois de 2 meses ele voltou do parana ai começei pegar aulas com ele, grande maestro claudio tmj meu amigo...', NULL, 'mais de anos', 1, 'aprovado', '2026-05-25 17:09:20'),
(2, 'Larissa Bernardelli Lima Pereira', 'Itaquaquecetuba', '', NULL, '\"Olá meu nome é Larissa. Estou aqui para deixar meus parabéns ao Ricardo/RWDEV. Muito talentoso, seus trabalhos são um sucesso e sempre superam minhas expectativas. Aguardo com mais surpresas, gosto do seu trabalho e vou continuar acompanhando sua trajetória. Deus abençoe.🙏\"', 'Minha Linda que o pai ama muito, Menina super Inteligente...', NULL, 'Desde 2003', 1, 'aprovado', '2026-05-26 14:29:27'),
(3, 'FERNANDO HENRIQUE DE SOUSA LIMA', 'Cotia', 'https://www.instagram.com/eucomportamentalmente?igsh=OW8yOXA1Zmozd3Jy', NULL, 'Conheci o Ricardo em um momento de bastante dificuldade na vida dele, eu sou sincero em dizer que sempre vi potencial na pessoa, porém, estava adormecida hoje eu o observo como um grande profissional, rápido, assertivo, com um talento inigualável e uma prática sem igual, ele criou meu site em tempo recorde e vários outros que ele fez, sei que ele será um dos maiores criadores de sites da atualidade e Ricardo... Pode ter certeza no que eu puder lhe ajudar ou auxiliar pode contar sempre comigo!\r\nQue Deus continue te abençoando grandemente em nome de Jesus Cristo meu amado irmão!', 'Grande Terapeuta Fernando! trabalhei na padaria do seu pai, realmente essa época foi muito dificil pra mim, mais graças a Deus estamos ai...', NULL, 'Desde 2024', 1, 'aprovado', '2026-05-26 14:51:55'),
(4, 'Hilton ribeiro de carvalho', 'Suzano', '', NULL, 'Excelente profissional, preço justo, ótimo atendimento, recomendo. Estava com meu notebook super lento e travando ele fez uma restauração completa ficou ótimo !', 'Iltão meu amigo e brother, conheço muitos anos mais de 30 anos hein! valeu meu amigo pelo depoimento tmj!', NULL, '30 anos e 4 meses', 1, 'aprovado', '2026-05-26 15:25:35'),
(5, 'Sandro Grisi de Sousa', 'Suzano', '', NULL, 'Realmente um cara de caráter,  conhece bem o que faz e faz com muita dedicação e eu super indico os serviços prestados por ele', 'Valeu meu futuro parceiro na tecnologia, Sandro formado em segurança da Informação, atualmente muito tarefado mais breve estaremos juntos desenvolvendo sera um privilegio meu amigo...', '2026-05-27 14:11:56', '1 ano', 1, 'aprovado', '2026-05-27 16:59:44');

-- --------------------------------------------------------

--
-- Estrutura da tabela `depoimento_reacoes`
--

CREATE TABLE `depoimento_reacoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `depoimento_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('like','love','haha','sad') NOT NULL,
  `identificador_usuario` varchar(120) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `depoimento_reacoes`
--

INSERT INTO `depoimento_reacoes` (`id`, `depoimento_id`, `tipo`, `identificador_usuario`, `criado_em`, `atualizado_em`) VALUES
(1, 5, 'like', 'rwdev_eba8d0da9554c15168220e2e9c205cf0', '2026-05-27 13:02:26', NULL),
(2, 4, 'like', 'rwdev_eba8d0da9554c15168220e2e9c205cf0', '2026-05-27 13:02:36', '2026-05-27 13:03:43'),
(3, 3, 'love', 'rwdev_eba8d0da9554c15168220e2e9c205cf0', '2026-05-27 13:02:41', NULL),
(4, 1, 'like', 'rwdev_eba8d0da9554c15168220e2e9c205cf0', '2026-05-27 13:02:51', NULL),
(6, 6, 'like', 'rwdev_eba8d0da9554c15168220e2e9c205cf0', '2026-05-27 17:12:22', NULL),
(7, 5, 'like', 'rwdev_f88cc7b21cd970c7bdc2053785475cab', '2026-06-10 00:51:08', NULL),
(8, 2, 'like', 'rwdev_f88cc7b21cd970c7bdc2053785475cab', '2026-06-10 00:51:29', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `diagnostico_eventos`
--

CREATE TABLE `diagnostico_eventos` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_type` varchar(40) NOT NULL,
  `page` varchar(120) NOT NULL,
  `referer` varchar(255) DEFAULT NULL,
  `ip_hash` char(64) NOT NULL,
  `user_agent_hash` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `diagnostico_eventos`
--

INSERT INTO `diagnostico_eventos` (`id`, `event_type`, `page`, `referer`, `ip_hash`, `user_agent_hash`, `created_at`) VALUES
(1, 'page_view', '/diagnostico', 'https://www.rwdev.com.br/', 'ab0d1249122174b4393703b778a23c2cc5dd56993830571fb15ee202b21d407d', '2a491504452cc7899a1eda9ef66e8d4a34133224b2bffa04392d44ef8394ecc2', '2026-06-09 19:51:08'),
(2, 'page_view', '/diagnostico', 'https://www.rwdev.com.br/', 'eab38b420368dadecfe9c5b2ace0bc47781fb828bde169f42abae3a45f910a34', '19491c58418328b1cde777d753c2fb6ec78c703cf9b96306306565c7ad094a16', '2026-06-09 20:34:52'),
(3, 'diagnosis_start', '/diagnostico', 'https://www.rwdev.com.br/', 'eab38b420368dadecfe9c5b2ace0bc47781fb828bde169f42abae3a45f910a34', '19491c58418328b1cde777d753c2fb6ec78c703cf9b96306306565c7ad094a16', '2026-06-09 20:37:14'),
(4, 'diagnosis_completed', '/diagnostico', 'https://www.rwdev.com.br/', 'eab38b420368dadecfe9c5b2ace0bc47781fb828bde169f42abae3a45f910a34', '19491c58418328b1cde777d753c2fb6ec78c703cf9b96306306565c7ad094a16', '2026-06-09 20:38:29'),
(5, 'whatsapp_click', '/diagnostico', 'https://www.rwdev.com.br/', 'eab38b420368dadecfe9c5b2ace0bc47781fb828bde169f42abae3a45f910a34', '19491c58418328b1cde777d753c2fb6ec78c703cf9b96306306565c7ad094a16', '2026-06-09 20:38:58'),
(6, 'page_view', '/diagnostico', '', '453caf0e41d891a7a20f68d09517aef38bb809ced68af9d72816d2adb63651a2', 'b4604075ac7c90f96d1b14f5155a07b09358ea98384669232a6d68b3b8e7284a', '2026-06-10 00:50:28'),
(7, 'page_view', '/diagnostico', '', 'c3dc764dbdfcdc2d910d71e32551ceed734244fe002627ef8501328c3925a12b', '19491c58418328b1cde777d753c2fb6ec78c703cf9b96306306565c7ad094a16', '2026-06-10 00:58:03'),
(8, 'page_view', '/diagnostico', 'https://www.rwdev.com.br/index', 'ab0d1249122174b4393703b778a23c2cc5dd56993830571fb15ee202b21d407d', '2a491504452cc7899a1eda9ef66e8d4a34133224b2bffa04392d44ef8394ecc2', '2026-06-10 01:34:47'),
(9, 'page_view', '/diagnostico', '', 'ed429c3fc714d9186d242fb342fc5738444c0de896884891b394c61e648148b2', 'f60c1e5491953084b657d67a707d6264c4de1116f7e7a82bfc56edcac0893d7a', '2026-06-10 03:20:00'),
(10, 'diagnosis_start', '/diagnostico', 'https://www.rwdev.com.br/depoimentos', 'ed429c3fc714d9186d242fb342fc5738444c0de896884891b394c61e648148b2', 'f60c1e5491953084b657d67a707d6264c4de1116f7e7a82bfc56edcac0893d7a', '2026-06-10 03:20:48'),
(11, 'diagnosis_start', '/diagnostico', 'https://www.rwdev.com.br/', 'ed429c3fc714d9186d242fb342fc5738444c0de896884891b394c61e648148b2', 'f60c1e5491953084b657d67a707d6264c4de1116f7e7a82bfc56edcac0893d7a', '2026-06-10 03:22:01'),
(12, 'diagnosis_completed', '/diagnostico', 'https://www.rwdev.com.br/', 'ed429c3fc714d9186d242fb342fc5738444c0de896884891b394c61e648148b2', 'f60c1e5491953084b657d67a707d6264c4de1116f7e7a82bfc56edcac0893d7a', '2026-06-10 03:23:42');

-- --------------------------------------------------------

--
-- Estrutura da tabela `diagnostico_leads`
--

CREATE TABLE `diagnostico_leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa` varchar(150) NOT NULL,
  `cidade` varchar(120) NOT NULL,
  `responsavel` varchar(150) NOT NULL,
  `whatsapp` varchar(30) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `pontuacao` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `classificacao` enum('Muito Quente','Quente','Morno') NOT NULL DEFAULT 'Morno',
  `origem` varchar(40) NOT NULL DEFAULT 'Direto',
  `referer` varchar(255) DEFAULT NULL,
  `status` enum('Novo Lead','Em Contato','Proposta Enviada','Cliente Fechado','Encerrado') NOT NULL DEFAULT 'Novo Lead',
  `respostas_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`respostas_json`)),
  `clicou_whatsapp` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_clicked_at` datetime DEFAULT NULL,
  `ip_hash` char(64) NOT NULL,
  `user_agent_hash` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `diagnostico_leads`
--

INSERT INTO `diagnostico_leads` (`id`, `empresa`, `cidade`, `responsavel`, `whatsapp`, `email`, `pontuacao`, `classificacao`, `origem`, `referer`, `status`, `respostas_json`, `clicou_whatsapp`, `whatsapp_clicked_at`, `ip_hash`, `user_agent_hash`, `created_at`, `updated_at`) VALUES
(1, 'RWDEV', 'Suzano', 'Ricardo Sousa', '11981104971', NULL, 44, 'Quente', 'Outros', 'https://www.rwdev.com.br/', 'Novo Lead', '{\"google_aparece\":\"Não\",\"whatsapp_contatos\":\"Poucos\",\"perfil_google\":\"Sim\",\"instagram_ativo\":\"Sim\",\"google_ads\":\"Não\",\"site_profissional\":\"Sim\",\"visitas_site\":\"Não\",\"contatos_google\":\"Não\"}', 0, NULL, 'ed429c3fc714d9186d242fb342fc5738444c0de896884891b394c61e648148b2', 'f60c1e5491953084b657d67a707d6264c4de1116f7e7a82bfc56edcac0893d7a', '2026-06-10 03:23:42', '2026-06-10 03:23:42');

-- --------------------------------------------------------

--
-- Estrutura da tabela `logs_seguranca`
--

CREATE TABLE `logs_seguranca` (
  `id` int(10) UNSIGNED NOT NULL,
  `tipo_evento` enum('login_sucesso','login_falha','login_bloqueado','logout','sessao_expirada','upload_sucesso','upload_bloqueado','csrf_invalido','form_recebido','csrf_ok','dados_recebidos','insert_executado','insert_erro') NOT NULL,
  `email` varchar(160) DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `tipo_usuario` enum('admin','cliente') DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `logs_seguranca`
--

INSERT INTO `logs_seguranca` (`id`, `tipo_evento`, `email`, `ip`, `tipo_usuario`, `mensagem`, `criado_em`) VALUES
(1, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 1', '2026-05-26 10:36:37'),
(2, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 1', '2026-05-26 10:44:46'),
(3, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 2', '2026-05-26 10:48:53'),
(4, 'login_sucesso', NULL, '131.100.131.253', NULL, 'Total encontrado: 2', '2026-05-26 11:28:45'),
(5, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 3', '2026-05-26 11:32:11'),
(6, 'login_sucesso', NULL, '131.100.131.253', NULL, 'Total encontrado: 3', '2026-05-26 11:38:02'),
(7, 'login_sucesso', NULL, '179.125.19.142', NULL, 'Total encontrado: 3', '2026-05-26 11:45:01'),
(8, 'login_sucesso', NULL, '131.100.131.253', NULL, 'Total encontrado: 3', '2026-05-26 11:45:29'),
(9, 'login_sucesso', NULL, '2804:d59:a106:de00:aa5:cbeb:3607:1905', NULL, 'Total encontrado: 3', '2026-05-26 12:21:42'),
(10, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 5', '2026-05-26 12:32:13'),
(11, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 5', '2026-05-26 13:08:19'),
(12, 'login_sucesso', NULL, '66.249.68.37', NULL, 'Total encontrado: 5', '2026-05-26 13:20:39'),
(13, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 5', '2026-05-27 09:11:56'),
(14, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 5', '2026-05-27 09:13:43'),
(15, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 5', '2026-05-27 09:37:12'),
(16, 'login_sucesso', NULL, '2804:bdc:50d9:7b00:e025:8ba7:50c4:380', NULL, 'Total encontrado: 5', '2026-05-27 09:43:09'),
(17, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 5', '2026-05-27 09:58:25'),
(18, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 5', '2026-05-27 10:00:26'),
(19, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Depoimento: 5 Tipo: like', '2026-05-27 10:02:26'),
(20, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Depoimento: 4 Tipo: like', '2026-05-27 10:02:36'),
(21, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Depoimento: 3 Tipo: love', '2026-05-27 10:02:41'),
(22, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Depoimento: 1 Tipo: like', '2026-05-27 10:02:51'),
(23, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Depoimento: 4 Tipo: like', '2026-05-27 10:03:43'),
(24, 'login_sucesso', NULL, '2804:7f0:67e1:8dce:344c:fda3:2448:3380', NULL, 'Total encontrado: 5', '2026-05-27 13:57:42'),
(25, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 5', '2026-05-27 14:08:00'),
(26, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 6', '2026-05-27 14:12:13'),
(27, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Depoimento: 6 Tipo: like', '2026-05-27 14:12:22'),
(28, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 6', '2026-05-27 14:20:41'),
(29, 'login_sucesso', NULL, '2804:7f0:67e1:b07a:5060:37a9:cc3f:75b4', NULL, 'Total encontrado: 6', '2026-05-31 19:25:26'),
(30, 'login_sucesso', NULL, '2804:1038:250:5501::1', NULL, 'Total encontrado: 6', '2026-06-02 13:35:46'),
(31, 'login_sucesso', NULL, '66.249.68.34', NULL, 'Total encontrado: 6', '2026-06-04 01:13:41'),
(32, 'login_sucesso', NULL, '2804:b34:302c:f500:3da8:fa80:5055:5276', NULL, 'Total encontrado: 6', '2026-06-04 19:15:50'),
(33, 'login_sucesso', NULL, '2804:1038:27a:d601::1', NULL, 'Total encontrado: 6', '2026-06-06 16:37:58'),
(34, 'login_sucesso', NULL, '201.22.223.102', NULL, 'Total encontrado: 6', '2026-06-07 17:51:16'),
(35, 'login_sucesso', NULL, '2804:1038:27a:d601::1', NULL, 'Total encontrado: 6', '2026-06-08 12:01:45'),
(36, 'login_sucesso', NULL, '2804:1038:27a:d601::1', NULL, 'Total encontrado: 6', '2026-06-08 12:06:54'),
(37, 'login_sucesso', NULL, '2804:1038:27a:d601::1', NULL, 'Total encontrado: 5', '2026-06-08 12:51:45'),
(38, 'login_sucesso', NULL, '2804:1038:27a:d601::1', NULL, 'Total encontrado: 5', '2026-06-08 14:23:08'),
(39, 'login_sucesso', NULL, '2804:1038:27a:d601::1', NULL, 'Total encontrado: 5', '2026-06-08 14:23:13'),
(40, 'login_sucesso', NULL, '66.249.68.36', NULL, 'Total encontrado: 5', '2026-06-08 15:25:32'),
(41, 'login_sucesso', NULL, '66.249.68.36', NULL, 'Total encontrado: 5', '2026-06-08 15:25:32'),
(42, 'login_sucesso', NULL, '66.249.68.36', NULL, 'Total encontrado: 5', '2026-06-08 15:29:28'),
(43, 'login_sucesso', NULL, '179.125.18.66', NULL, 'Total encontrado: 5', '2026-06-09 17:39:15'),
(44, 'login_sucesso', NULL, '66.249.68.35', NULL, 'Total encontrado: 5', '2026-06-09 20:45:46'),
(45, 'login_sucesso', NULL, '191.128.232.133', NULL, 'Total encontrado: 5', '2026-06-09 21:50:54'),
(46, 'login_sucesso', NULL, '191.128.232.133', NULL, 'Depoimento: 5 Tipo: like', '2026-06-09 21:51:08'),
(47, 'login_sucesso', NULL, '191.128.232.133', NULL, 'Depoimento: 2 Tipo: like', '2026-06-09 21:51:29'),
(48, 'login_sucesso', NULL, '2804:1038:27a:d601::1', NULL, 'Total encontrado: 5', '2026-06-10 00:20:31');

-- --------------------------------------------------------

--
-- Estrutura da tabela `paginas_projeto`
--

CREATE TABLE `paginas_projeto` (
  `id` int(10) UNSIGNED NOT NULL,
  `projeto_id` int(10) UNSIGNED NOT NULL,
  `nome_pagina` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `paginas_projeto`
--

INSERT INTO `paginas_projeto` (`id`, `projeto_id`, `nome_pagina`) VALUES
(1, 1, 'Início'),
(2, 1, 'Serviços'),
(3, 1, 'Contato'),
(4, 1, 'trabalhos');

-- --------------------------------------------------------

--
-- Estrutura da tabela `projetos`
--

CREATE TABLE `projetos` (
  `id` int(10) UNSIGNED NOT NULL,
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(160) NOT NULL,
  `dominio` varchar(180) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `projetos`
--

INSERT INTO `projetos` (`id`, `cliente_id`, `nome`, `dominio`, `descricao`, `status`, `criado_em`) VALUES
(1, 2, 'Site JCtoldos', 'https://rwdev.com.br/jctoldos', '', 'ativo', '2026-05-22 18:25:58');

-- --------------------------------------------------------

--
-- Estrutura da tabela `solicitacoes`
--

CREATE TABLE `solicitacoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `projeto_id` int(10) UNSIGNED NOT NULL,
  `pagina` varchar(120) NOT NULL,
  `tipo_alteracao` varchar(80) NOT NULL,
  `descricao` text NOT NULL,
  `status` enum('Recebido','Em análise','Em desenvolvimento','Aguardando cliente','Concluído') NOT NULL DEFAULT 'Recebido',
  `resposta_admin` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `solicitacoes`
--

INSERT INTO `solicitacoes` (`id`, `cliente_id`, `projeto_id`, `pagina`, `tipo_alteracao`, `descricao`, `status`, `resposta_admin`, `criado_em`, `atualizado_em`) VALUES
(1, 2, 1, 'Contato', 'Corrigir erro', 'deixar o telefone 11958973342 como principal', 'Concluído', 'Foi corrigido, inclui um mapa do endereço Google maps', '2026-05-22 18:28:13', '2026-05-23 01:17:54');

-- --------------------------------------------------------

--
-- Estrutura da tabela `tentativas_login`
--

CREATE TABLE `tentativas_login` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(160) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `tipo_usuario` enum('admin','cliente') NOT NULL,
  `sucesso` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `arquivos_solicitacao`
--
ALTER TABLE `arquivos_solicitacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_arquivos_solicitacao` (`solicitacao_id`);

--
-- Índices para tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `convites_cliente`
--
ALTER TABLE `convites_cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_convites_token` (`token`),
  ADD KEY `idx_convites_email` (`email`),
  ADD KEY `idx_convites_status` (`status`);

--
-- Índices para tabela `depoimentos`
--
ALTER TABLE `depoimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_depoimentos_status` (`status`),
  ADD KEY `idx_depoimentos_criado_em` (`criado_em`);

--
-- Índices para tabela `depoimento_reacoes`
--
ALTER TABLE `depoimento_reacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reacao_usuario` (`depoimento_id`,`identificador_usuario`),
  ADD KEY `idx_depoimento_id` (`depoimento_id`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Índices para tabela `diagnostico_eventos`
--
ALTER TABLE `diagnostico_eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diagnostico_event_type_created_at` (`event_type`,`created_at`),
  ADD KEY `idx_diagnostico_referer` (`referer`),
  ADD KEY `idx_diagnostico_unicos_dia` (`event_type`,`page`,`ip_hash`,`user_agent_hash`,`created_at`);

--
-- Índices para tabela `diagnostico_leads`
--
ALTER TABLE `diagnostico_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diagnostico_leads_created_at` (`created_at`),
  ADD KEY `idx_diagnostico_leads_status` (`status`),
  ADD KEY `idx_diagnostico_leads_classificacao` (`classificacao`),
  ADD KEY `idx_diagnostico_leads_origem` (`origem`),
  ADD KEY `idx_diagnostico_leads_visitante` (`ip_hash`,`user_agent_hash`,`created_at`);

--
-- Índices para tabela `logs_seguranca`
--
ALTER TABLE `logs_seguranca`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_seguranca_evento` (`tipo_evento`),
  ADD KEY `idx_logs_seguranca_email` (`email`),
  ADD KEY `idx_logs_seguranca_criado_em` (`criado_em`);

--
-- Índices para tabela `paginas_projeto`
--
ALTER TABLE `paginas_projeto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_paginas_projeto` (`projeto_id`);

--
-- Índices para tabela `projetos`
--
ALTER TABLE `projetos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_projetos_clientes` (`cliente_id`);

--
-- Índices para tabela `solicitacoes`
--
ALTER TABLE `solicitacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_solicitacoes_clientes` (`cliente_id`),
  ADD KEY `fk_solicitacoes_projetos` (`projeto_id`);

--
-- Índices para tabela `tentativas_login`
--
ALTER TABLE `tentativas_login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tentativas_login_bloqueio` (`email`,`ip`,`tipo_usuario`,`sucesso`,`criado_em`),
  ADD KEY `idx_tentativas_login_criado_em` (`criado_em`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `arquivos_solicitacao`
--
ALTER TABLE `arquivos_solicitacao`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `convites_cliente`
--
ALTER TABLE `convites_cliente`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `depoimentos`
--
ALTER TABLE `depoimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `depoimento_reacoes`
--
ALTER TABLE `depoimento_reacoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `diagnostico_eventos`
--
ALTER TABLE `diagnostico_eventos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `diagnostico_leads`
--
ALTER TABLE `diagnostico_leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `logs_seguranca`
--
ALTER TABLE `logs_seguranca`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de tabela `paginas_projeto`
--
ALTER TABLE `paginas_projeto`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `projetos`
--
ALTER TABLE `projetos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `solicitacoes`
--
ALTER TABLE `solicitacoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `tentativas_login`
--
ALTER TABLE `tentativas_login`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `arquivos_solicitacao`
--
ALTER TABLE `arquivos_solicitacao`
  ADD CONSTRAINT `fk_arquivos_solicitacao` FOREIGN KEY (`solicitacao_id`) REFERENCES `solicitacoes` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `paginas_projeto`
--
ALTER TABLE `paginas_projeto`
  ADD CONSTRAINT `fk_paginas_projeto` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `projetos`
--
ALTER TABLE `projetos`
  ADD CONSTRAINT `fk_projetos_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `solicitacoes`
--
ALTER TABLE `solicitacoes`
  ADD CONSTRAINT `fk_solicitacoes_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_solicitacoes_projetos` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
