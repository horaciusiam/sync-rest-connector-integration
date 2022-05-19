<?php
class User
{
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Cria um usuario
	 * @param array $data Parametro para criacao do direito
	 *		array(
	 *			'displayName' => string Nome do Usuario (Obrigatorio)
	 *			'userPrincipalName' => string Email do usuario (Obrigatorio)
	 *			'mailNickname' => string Identificador do usuario (Obrigatorio)
	 *			'passwordProfile' => array Informacoes de senha
	 *				array(
	 *					'forceChangePasswordNextLogin' => string Indica se deve alterar senha no proximo login.  
	 *														Valores possiveis: "True", "False"
	 *					'password' => string Senha do usuario
	 *				)
	 *			'accountEnabled' => string Se a conta esta bloquada ou nao. Valores possiveis: "True", "False"
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function create_user($data)
	{
		$return_data = array();
		$action_type = "create";
		$error = "Request_BadRequest";
		
		if( isset($data['displayName'], $data['userPrincipalName'], $data['mailNickname']) )
		{
			$data['accountEnabled'] = (isset($data['accountEnabled'])) ? $data['accountEnabled'] : null;
			$data['passwordProfile']['forceChangePasswordNextLogin'] = (isset($data['passwordProfile']['forceChangePasswordNextLogin'])) ? $data['passwordProfile']['forceChangePasswordNextLogin'] : null;
		
			if( isset($data['generate_password']) || !isset($data['passwordProfile']['password']))
				$data['passwordProfile']['password'] = self::password_generate(0, 8);
			else
				$data['passwordProfile']['password'] = $data['passwordProfile']['password'];
			
			$return_data = self::insert_user($data['displayName'], $data['userPrincipalName'], $data['mailNickname'], $data['accountEnabled'], $data['passwordProfile']['forceChangePasswordNextLogin'], $data['passwordProfile']['password']);
			
			if( isset($return_data['internal_user_id'], $data['generate_password']) )
			{
				$return_data['password'] = $data['passwordProfile']['password'];
			}

			$error = "";
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'User created');
			}
		}
		else
		{
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'User not created');
				syslog(LOG_INFO, 'Structural error: missing "displayName", "userPrincipalName", "mailNickname"');
			}
		}
		
		$return_formated_data = self::format_return_data($action_type, $return_data, $error);
		
		return $return_formated_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Verifica a existencia de um usuario
	 * @param array $data Dados para verificar existencia de usuario
	 *		array(
	 *			'mailNickname' => string Identificador do usuario
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function check_user($data)
	{
		$return_data = array();
		$action_type = "check";
		$error = "Request_BadRequest";
		
		if(isset($data['mailNickname']))
		{
			$find_user_sql = "	SELECT 
									internal_user_id
								FROM 
									usuario 
								WHERE 
									user_id ='" . MySQLConnector::escape_string($data['mailNickname']) . "'";
			
			$users_found = MySQLConnector::bd_search($find_user_sql);
			if(count($users_found) > 0)
			{
				$error = "";
				$return_data['internal_user_id'] = $users_found[0]['internal_user_id'];
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'User exist');
				}
			}
			else
			{
				$error = "Request_ResourceNotFound";
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "User not found! User doesn't exist");
				}
			}
		}
		else
		{
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Structural error: missing "mailNickname"');
			}
		}
		
		$return_formated_data = self::format_return_data($action_type, $return_data, $error);
		
		return $return_formated_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Insere usuario no banco de dados
	 * @param string $display_name Nome do usuario
	 * @param string $user_principal_name Email do usuario
	 * @param string $user_id Identificador do usuario
	 * @param string $account_enabled Parametro que indica se o usuario esta bloqueado.
	 *									Valores possiveis: "True" : nao bloqueado, "False" : bloqueado
	 * @param string $force_change_password_next_login Identificador se o usuario deve alterar senha no proximo login
	 *									Valores possiveis: "True" : alterar senha, "False" : nao alterar senha
	 * @param string $password Senha do usuario
	 * @return int ID interno do usuario criado
	 */
	private static function insert_user($display_name, $user_principal_name, $user_id, $account_enabled=null, $force_change_password_next_login=null, $password=null)
	{
		$force_change_password_next_login = 1;
		
		if(strtolower($force_change_password_next_login) == "false")
		{
			$force_change_password_next_login = 0;
		}
		
		$account_enabled = 1;
		
		if(strtolower($account_enabled) == "false")
		{
			$account_enabled = 0;
		}
		
		$insert_user = "	INSERT INTO
								usuario 
								(	display_name, 
									user_principal_name,
									user_id, 
									account_enabled, 
									force_change_password_next_login, 
									password
								)
							VALUES
								(
									'" . MySQLConnector::escape_string($display_name) . "',
									'" . MySQLConnector::escape_string($user_principal_name) . "',
									'" . MySQLConnector::escape_string($user_id) . "',
									'" . MySQLConnector::escape_string($account_enabled) . "',
									'" . MySQLConnector::escape_string($force_change_password_next_login) . "',
									'" . MySQLConnector::escape_string($password) . "'
								)";
								
		$internal_user_id = MySQLConnector::bd_insert($insert_user);
		$retorno['internal_user_id'] = $internal_user_id;
		
		return $retorno;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Busca o id interno do usuario
	 * @param string $user_id ID que sera buscado
	 * @param string $type_id Indica se o id fornecido eh interno ou nao
	 *			Valores possiveis: 'internal_user_id' => O valor eh o id interno, 'user_id' => O valor eh o id externo do usuario
	 * @return int ID interno do usuario ou -1 caso nao exista o usuario
	 */
	public static function search_user_by_type($user_id, $type_id)
	{
		if($type_id == 'internal_user_id')
		{
			$search_restriction = "internal_user_id ='" . MySQLConnector::escape_string($user_id) . "'";
		}
		else
		{
			$search_restriction = "user_id ='" . MySQLConnector::escape_string($user_id) . "'";
		}
		
		$find_user_sql = "	SELECT 
								internal_user_id
							FROM	
								usuario 
							WHERE ";
				
		$user_exist = MySQLConnector::bd_search($find_user_sql.$search_restriction);
		
		$user_internal_id = -1;
		
		if(count($user_exist) > 0)
		{
			$user_internal_id = $user_exist[0]['internal_user_id'];
		}
		
		return $user_internal_id;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Atualiza um campo do usuario
	 * @param array $field_value Array com o novo valor do usuario
	 *		array(
	 *			array(
	 *				'key' => 'coluna_tabela_atributo'
	 *				'value' => 'novo_valor'
	 *			)
	 *		)
	 * @param string $right_id ID que sera buscado
	 * @param string $type_id Indica se o id fornecido eh interno ou nao
	 * @return int ID interno do direito ou -1 caso nao exista o direito
	 */
	private static function update_user_field($field_value, $user_id, $type_id)
	{
		$user_internal_id = self::search_user_by_type($user_id, $type_id);
		
		if($user_internal_id != -1)
		{
			$update_user_sql = "UPDATE
									usuario 
								SET
									[%1]
								WHERE 
									internal_user_id ='" . MySQLConnector::escape_string($user_internal_id) . "'";
			$update_fields_array = array();
			for($i = 0; $i < count($field_value); $i++)
			{
				$update_fields_array[] = $field_value[$i]['key'] . " = '" . MySQLConnector::escape_string($field_value[$i]['value']) . "'";
			}
			
			$update_fields = implode(", ",$update_fields_array);	
			$update_user_sql = str_replace("[%1]", $update_fields, $update_user_sql);
			MySQLConnector::bd_update($update_user_sql);
		}
		
		return $user_internal_id;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Atualiza usuario
	 * @param array $data Dados para atualizar usuario
	 *		array(
	 *			'type' => string Indica se o id fornecido eh interno ou nao
	 *							Valores possiveis:	'internal_user_id' => O valor eh o id interno, 
	 *												'user_id' => O valor eh o id externo do usuario
	 *			'id' => string ID do usuario
	 *			'displayName' => string Novo nome do usuario,
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function update_user($data)
	{
		$return_data = array();
		$error = "Request_BadRequest";
		$action_type = "update";
		
		if(isset($data['displayName'], $data['type'], $data['id']))
		{
			$field_value = array(
				array(
					'key' => 'display_name',
					'value' => $data['displayName']
				)
			);
			
			$result = self::update_user_field($field_value, $data['id'], $data['type']);
			
			if($result != "-1")
			{
				$error = "";
				$return_data['internal_user_id'] = $result;
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "User updated");
				}
			}
			else
			{
				$error = "Request_ResourceNotFound";
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "User not found! User not updated");
				}
			}
		}
		else
		{
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Structural error: missing "displayName", "type", "id"');
			}
		}
		
		$return_formated_data = self::format_return_data($action_type, $return_data, $error);
		
		return $return_formated_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Bloqueia/Desbloqueia usuario
	 * @param array $data Dados para bloquear/desbloquear usuario
	 *		array(
	 *			'type' => string Indica se o id fornecido eh interno ou nao
	 *							Valores possiveis:	'internal_user_id' => O valor eh o id interno, 
	 *												'user_id' => O valor eh o id externo do usuario
	 *			'id' => string ID do usuario
	 *			'accountEnabled' => string Indica status do usuario. Valores possiveis: "True" -> Usuario Desbloqueado, "False" -> Usuario Bloqueado
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function lock_user($data)
	{
		$return_data = array();
		$error = "Request_BadRequest";
		$action_type = "lock_user";
		
		if(isset($data['type'], $data['id'], $data['accountEnabled']))
		{
			if(strtolower($data['accountEnabled']) == "true")
			{
				$data['accountEnabled'] = "1";
			}
			else
			{
				$data['accountEnabled'] = "0";
			}
			
			$field_value = array(
				array(
					'key' => 'account_enabled',
					'value' => $data['accountEnabled']
				)
			);
			
			$result = self::update_user_field($field_value, $data['id'], $data['type']);
			
			if($result != "-1")
			{
				$error = "";
				$return_data['internal_user_id'] = $result;
				if($data['accountEnabled'] == "1")
				{
					if($GLOBALS['debug'])
					{
						syslog(LOG_INFO, "User unlocked");
					}
				}
				else
				{
					if($GLOBALS['debug'])
					{
						syslog(LOG_INFO, "User locked");
					}
				}
			}
			else
			{
				$error = "Request_ResourceNotFound";
				if($data['accountEnabled'] == "1")
				{
					if($GLOBALS['debug'])
					{
						syslog(LOG_INFO, "User not found! User not unlocked");
					}
				}
				else
				{
					if($GLOBALS['debug'])
					{
						syslog(LOG_INFO, "User not found! User not locked");
					}
				}
			}
		}
		else
		{
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Structural error: missing "accountEnabled", "type", "id"');
			}
		}
		
		$return_formated_data = self::format_return_data($action_type, $return_data, $error);
		
		return $return_formated_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Redefine senha do usuario
	 * @param array $data Dados para bloquear/desbloquear usuario
	 *		array(
	 *			'type' => string Indica se o id fornecido eh interno ou nao
	 *							Valores possiveis:	'internal_user_id' => O valor eh o id interno, 
	 *												'user_id' => O valor eh o id externo do usuario
	 *			'id' => string ID do usuario
	 '			'passwordProfile' => array Informacoes de senha
	 *				array(
	 *					'forceChangePasswordNextLogin' => string Indica se deve alterar senha no proximo login.  
	 *														Valores possiveis: "True", "False" (Opcional)
	 *					'password' => string Senha do usuario
	 *			)
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function reset_password($data)
	{
		$return_data = array();
		$error = "Request_BadRequest";
		$action_error = "reset_password";

		if( isset($data['generate_password']) || !isset($data['passwordProfile']['password']))
		{
			$data['passwordProfile']['password'] = self::password_generate(0, 8);
		}
		
		if(isset($data['type'], $data['id'], $data['passwordProfile']['password']))
		{
			$field_value = array(
				array(
					'key' => 'password',
					'value' => $data['passwordProfile']['password']
				)
			);
			
			if(isset($data['passwordProfile']['forceChangePasswordNextLogin']))
			{
				if(strtolower($data['passwordProfile']['forceChangePasswordNextLogin']) == "true")
				{
					$field_value[] = array(
										'key' => 'force_change_password_next_login',
										'value' => '1'
									);
				}	
				else
				{
					$field_value[] = array(
										'key' => 'force_change_password_next_login',
										'value' => '0'
									);
				}
			}
			
			$result = self::update_user_field($field_value, $data['id'], $data['type']);
			
			if($result != "-1")
			{
				$error = "";
				$return_data['internal_user_id'] = $result;
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "Password reseted");
				}
			}
			else
			{
				$error = "Request_ResourceNotFound";
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "User not found! Password not reseted");
				}
			}
		}
		else
		{
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Structural error: missing "passwordProfile-password", "type", "id"');
			}
		}
		
		$return_formated_data = self::format_return_data($action_error, $return_data, $error);
		
		return $return_formated_data;
	}
	
	
	public static function remove_user($data)
	{
		$return_data = array();
		$action_type = "remove_user";
		
		if(isset($data['type'], $data['id']))
		{
			$internal_user_id = self::search_user_by_type($data['id'], $data['type']);
			
			if($internal_user_id != "-1")
			{
				$delete_user_sql = "DELETE FROM
										usuario
									WHERE 
										internal_user_id ='" . MySQLConnector::escape_string($internal_user_id) . "'";
				MySQLConnector::bd_delete($delete_user_sql);
				
				$delete_user_rights_sql = " DELETE FROM
												rel_usuario_atributo
											WHERE 
												internal_user_id ='" . MySQLConnector::escape_string($internal_user_id) . "'";
				MySQLConnector::bd_delete($delete_user_rights_sql);
				
				$error = "";
				$return_data['internal_user_id'] = $internal_user_id;
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "User removed");
				}
			}
			else
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "User not found! User not removed");
				}
				$error = "Request_ResourceNotFound";
			}
		}
		else
		{
			$error = "Request_BadRequest";
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Structural error: missing "type", "id"');
			}
		}
		
		
		$return_formated_data = self::format_return_data($action_type, $return_data, $error);
		
		return $return_formated_data;
	}
	
	private static function format_return_data($action_type, $return_data, $erro)
	{
		switch ($erro)
		{
			case "Request_BadRequest":
			{
				$retorno = array(
					'odata.error' => array(
						'code' => 'Request_BadRequest',
						'message' => array(
							'value' => "Mandatory fields are empty or invalid!"
						)
					)
				);
			}
			break;
			
			case "Request_ResourceNotFound":
			{
				$retorno = array(
					'odata.error' => array(
						'code' => 'Request_ResourceNotFound',
						'message' => array(
							'value' => "Resource not found"
						)
					)
				);
			}
			break;
			
			default:
			{
				switch($action_type == "find_all_users")
				{
					case "find_all_users":
					{
						$retorno = array(
							'value' => $return_data
						);
					}
					break;
					
					default:
					{
						$retorno = array(
							'objectId' => $return_data['internal_user_id']
						);
							
						if(isset($return_data['password']))
							$retorno['password'] = $return_data['password'];
					}
					break;
				}
			}
			break;
		}	
		return $retorno;
	}
	
	public static function find_all_users()
	{
		$find_user_sql = "	SELECT 
								display_name as displayName, user_id as mailNickname
							FROM	
								usuario";
		$action_type = "find_all_users";
		$error = "";
		
		$all_users = MySQLConnector::bd_search($find_user_sql);
		$return_formated_data = self::format_return_data($action_type, $all_users, $error);
		if($GLOBALS['debug'])
		{
			syslog(LOG_INFO, 'Return all users');
		}
		return $return_formated_data;
	}
	
	private static function password_generate($min, $max)
	{
		return substr( md5( uniqid( rand() ) ), $min, $max);
	}
	
}