--
-- Banco de dados: `ws_rest_sincrono`
--
CREATE DATABASE IF NOT EXISTS `ws_rest_sincrono` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `ws_rest_sincrono`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `atributo`
--

DROP TABLE IF EXISTS `atributo`;
CREATE TABLE IF NOT EXISTS `atributo` (
  `display_name` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(512) NOT NULL DEFAULT '',
  `internal_right_id` int(11) NOT NULL AUTO_INCREMENT,
  `right_id` varchar(255) NOT NULL,
  PRIMARY KEY (`internal_right_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `autenticacao`
--

DROP TABLE IF EXISTS `autenticacao`;
CREATE TABLE IF NOT EXISTS `autenticacao` (
  `id_interno_usuario` int(11) NOT NULL,
  `data_criacao_token` datetime NOT NULL,
  `token` varchar(256) NOT NULL,
  PRIMARY KEY (`id_interno_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `rel_usuario_atributo`
--

DROP TABLE IF EXISTS `rel_usuario_atributo`;
CREATE TABLE IF NOT EXISTS `rel_usuario_atributo` (
  `internal_right_id` int(11) NOT NULL,
  `internal_user_id` int(11) NOT NULL,
  PRIMARY KEY (`internal_right_id`,`internal_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `internal_user_id` int(11) NOT NULL AUTO_INCREMENT,
  `display_name` varchar(500) NOT NULL,
  `user_principal_name` varchar(256) NOT NULL,
  `account_enabled` int(1) NOT NULL DEFAULT '1',
  `user_id` varchar(255) NOT NULL,
  `force_change_password_next_login` tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(64) NOT NULL,
  PRIMARY KEY (`internal_user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `valor_campo_atributo_customizado`
--

DROP TABLE IF EXISTS `valor_campo_atributo_customizado`;
CREATE TABLE IF NOT EXISTS `valor_campo_atributo_customizado` (
  `internal_custom_atrib_id` int(11) NOT NULL AUTO_INCREMENT,
  `internal_right_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(512) NOT NULL DEFAULT '',
  PRIMARY KEY (`internal_custom_atrib_id`,`internal_right_id`,`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;



GRANT SELECT, INSERT, UPDATE, DELETE ON  `ws_rest_sincrono`.`atributo` TO  'test'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON  `ws_rest_sincrono`.`autenticacao` TO  'test'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON  `ws_rest_sincrono`.`rel_usuario_atributo` TO  'test'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON  `ws_rest_sincrono`.`usuario` TO  'test'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON  `ws_rest_sincrono`.`valor_campo_atributo_customizado` TO  'test'@'localhost';