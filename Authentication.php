<?php
	require "MySQLConnector.php";
	
	class Authentication
	{
		/**
		 * DI-134: Utilitarios: WebServer REST para testes do conector
		 * Executa a criacao de um token para um usuario valida (Existe usuario e ele esta habilitado)
		 * @param array $data Parametros recebidos para criacao do token
		 *		array(
		 *			'grant_type' => string 'client_credentials',
		 *			'client_id' => int Identificador interno do usuario,
		 *			'client_secret' => string  Senha do usuario
		 *		)
		 * @return array Retorno da requisicao formatado
		 *		array(
		 *			'token_type' => string 'SWT',
		 *			'access_token' => string Token de acesso
		 *		)
		 */
		public static function oauth2($data)
		{
			$return_data['token_type'] = 'SWT';
			$return_data['access_token'] = '';
			$return_data['status'] = "ERROR";
			
			if(isset($data['grant_type'], $data['client_secret'], $data['client_id']))
			{
				if($data['grant_type'] == 'client_credentials')
				{
					$search_user_sql = "
						SELECT
							COUNT(1) AS user_exist
						FROM
							usuario
						WHERE
							internal_user_id ='" . MySQLConnector::escape_string($data['client_id']) . "' AND
							password ='" . MySQLConnector::escape_string($data['client_secret']) . "' AND
							account_enabled = '0'";

					$search_result = MySQLConnector::bd_search($search_user_sql);
					if($search_result[0]['user_exist'])
					{
						$return_data['access_token'] = self::create_token($data['client_id']);
						$return_data['status'] = "OK";
						if($GLOBALS['debug'])
						{
							syslog(LOG_INFO, "Created token");
						}
					}
					else
					{
						if($GLOBALS['debug'])
						{
							syslog(LOG_INFO, "Invalid User - Valid token not created");
						}
					}
				}
				else
				{
					if($GLOBALS['debug'])
					{
						syslog(LOG_INFO, "Invalid Grant Type - Valid token not created");
					}
				}
			}
			else
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'Structural error: missing "grant_type", "client_secret", "client_id"');
				}
			}
			
			return $return_data;
		}
		
		/**
		 * DI-134: Utilitarios: WebServer REST para testes do conector
		 * Cria o token e insere no banco de dados
		 * @param int $id_interno_usuario ID interno do usuario
		 * @return string Token criado
		 */
		private static function create_token($id_interno_usuario)
		{
			$token = md5(uniqid(rand().$id_interno_usuario, true));
			$insert_token= "REPLACE INTO
								autenticacao 
								(id_interno_usuario, data_criacao_token, token)
							VALUES
								('" . MySQLConnector::escape_string($id_interno_usuario) . "', NOW(), '" . MySQLConnector::escape_string($token) . "')";
									
			MySQLConnector::bd_insert($insert_token);
			return $token;
		}
		
		/**
		 * DI-134: Utilitarios: WebServer REST para testes do conector
		 * Valida o header recebido, se o token de autenticacao eh valido
		 * @param array $header Header recebido pelo servidor
		 * @return string Token criado
		 */
		public static function validate_header($header)
		{
			$error = false;
			
			if(!isset($header['Authorization']))
			{
				$error = true;
			}
			else
			{
				$token = explode(" ", $header['Authorization']);
				
				$busca_token = "SELECT 
									COUNT(1) AS valid_acess
								FROM 
									autenticacao
								WHERE 
									token ='" . MySQLConnector::escape_string($token[count($token) - 1]) . "'";
												
				$search_result = MySQLConnector::bd_search($busca_token);
				
				if($search_result[0]['valid_acess'] == 0)
				{
					$error = true;
				}
			}
			
			if($error)
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "Invalid token");
				}
				return false;
			}
			else
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "Valid token");
				}
				return true;
			}
		}
		
		/**
		 * DI-134: Utilitarios: WebServer REST para testes do conector
		 * Remove os tokens expirados (Tempo de criacao for maior que o tempo fornecido)
		 * @param int $time_in_minutes Tempo em minutos
		 */
		public static function remove_expired_tokens($time_in_minutes)
		{
			$remove_old_tokens = "DELETE FROM autenticacao WHERE TIMESTAMPDIFF(MINUTE, data_criacao_token, NOW()) > " . MySQLConnector::escape_string($time_in_minutes);
			MySQLConnector::bd_delete($remove_old_tokens);
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, "Removed expired tokens");
			}
		}
	}