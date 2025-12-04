SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- CREATE DATABASE farmacia;
USE farmacia;

CREATE TABLE IF NOT EXISTS `farmaceuticos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `crf` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pacientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `dtnascimento` date NOT NULL,
  `email` varchar(100) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `tipo_paciente` enum('cronico','agudo') DEFAULT 'agudo',
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `taxa_adesao` decimal(5,2) DEFAULT 0.00,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `medicamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `principio_ativo` varchar(255) NOT NULL,
  `dosagem` varchar(50) NOT NULL,
  `laboratorio` varchar(255) NOT NULL,
  `tipo` enum('Comprimido','Cápsula','Xarope','Injeção','Pomada','Gotas') NOT NULL,
  `numero_lote` varchar(100) NOT NULL,
  `data_validade` date NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 0,
  `preco` decimal(10,2) DEFAULT 0.00,
  `descricao` text DEFAULT NULL,
  `requer_receita` enum('Não','Sim','Controlado') DEFAULT 'Não',
  `condicao_armazenamento` varchar(100) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_nome` (`nome`),
  KEY `idx_principio_ativo` (`principio_ativo`),
  KEY `idx_laboratorio` (`laboratorio`),
  KEY `idx_quantidade` (`quantidade`),
  KEY `idx_data_validade` (`data_validade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `unidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cnpj` varchar(18) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `endereco` varchar(500) NOT NULL,
  `farmaceutico_responsavel` varchar(255) DEFAULT NULL,
  `crf_responsavel` varchar(50) DEFAULT NULL,
  `horario_funcionamento` varchar(100) DEFAULT NULL,
  `status` enum('Ativa','Inativa','Manutenção') NOT NULL DEFAULT 'Ativa',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnpj` (`cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `atendimentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `farmaceutico_id` int(11) NOT NULL,
  `tipo_atendimento` enum('Primeira Consulta', 'Retorno', 'Acompanhamento', 'Orientação') NOT NULL DEFAULT 'Primeira Consulta',
  `status_atendimento` enum('Agendado', 'Concluído', 'Cancelado', 'Em Andamento') NOT NULL DEFAULT 'Concluído',
  `respostas_json` text NOT NULL,
  `notas_farmaceutico` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_agendamento` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `farmaceutico_id` (`farmaceutico_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `agendamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `farmaceutico_id` int(11) NOT NULL,
  `tipo_consulta` enum('Retorno', 'Acompanhamento', 'Nova Consulta') NOT NULL,
  `status` enum('Agendado', 'Confirmado', 'Cancelado', 'Realizado') NOT NULL DEFAULT 'Agendado',
  `notas` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`paciente_id`) REFERENCES `pacientes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`farmaceutico_id`) REFERENCES `farmaceuticos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `kpis_diarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `total_atendimentos` int(11) DEFAULT 0,
  `atendimentos_cronicos` int(11) DEFAULT 0,
  `atendimentos_agudos` int(11) DEFAULT 0,
  `taxa_adesao_media` decimal(5,2) DEFAULT 0.00,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_data_unica` (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `atendimento_medicamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `atendimento_id` int(11) NOT NULL,
  `medicamento_id` int(11) NOT NULL,
  `quantidade_dispensada` int(11) NOT NULL DEFAULT 1,
  `preco_no_momento` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_atendimento_medicamento` (`atendimento_id`, `medicamento_id`),
  CONSTRAINT `fk_atendimento` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_medicamento` FOREIGN KEY (`medicamento_id`) REFERENCES `medicamentos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `atendimentos`
  ADD CONSTRAINT `atendimentos_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `atendimentos_ibfk_2` FOREIGN KEY (`farmaceutico_id`) REFERENCES `farmaceuticos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

ALTER TABLE `farmaceuticos` 
ADD COLUMN `api_key_gemini` VARCHAR(255) NULL AFTER `telefone`;