<?php
require_once("User.php");
require_once("functions.php");

class Right
{
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Cria um direito 
	 * @param array $data Parametro para criacao do direito
	 *		array(
	 *			'displayName' => string Nome do Direito,
	 *			'description' => string Descricao do Direito
	 *			'mailNickname' => string Identificador do direito
	 *			'item_1' => string Atributos customizado
	 *			'item_N' => string Atributos customizado
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function create_right($data)
	{
		$return_data = array();
		$action_type = "create";
		$error = "Request_BadRequest";

		if(isset($data['displayName']) && isset($data['description']))
		{
			unset($data['action']);

			$display_name = $data['displayName'];
			unset($data['displayName']);

			$description = $data['description'];
			unset($data['description']);

			$mail_nickname = isset($data['mailNickname']) ? $data['mailNickname'] : '';
			unset($data['mailNickname']);

			$custon_atrib = $data;

			$return_data = self::insert_right($display_name, $description, $mail_nickname, $custon_atrib);
			$error = "";
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Right created');
			}
		}
		else
		{
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Right not created');
				syslog(LOG_INFO, 'Structural error: missing "displayName", "rightPrincipalName", "mailNickname"');
			}
		}

		$return_formated_data = self::format_return_data($action_type, $return_data, $error);

		return $return_formated_data;
	}

	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Verifica a existencia de um direito
	 * @param array $data Dados para verificar existencia de direito
	 *		array(
	 *			'mailNickname' => string Identificador do direito
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function check_right($data)
	{
		$return_data = array();
		$action_type = "check";
		$error = "Request_BadRequest";
		
		if(isset($data['mailNickname']))
		{
			$find_right_sql = "	SELECT 
									internal_right_id
								FROM 
									atributo 
								WHERE 
									right_id ='" . MySQLConnector::escape_string($data['mailNickname']) . "'";
			
			$rights_found = MySQLConnector::bd_search($find_right_sql);
			if(count($rights_found) > 0)
			{
				$error = "";
				$return_data['internal_right_id'] = $rights_found[0]['internal_right_id'];
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'Right exists');
				}
			}
			else
			{
				$error = "Request_ResourceNotFound";
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "Right doesn't exist");
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
	 * Insere direito no banco de dados
	 * @param string $display_name Nome do direito
	 * @param string $description Descricao do Direito
	 * @param string $mailNickname Identificador do direito
	 * @param array $field_custom_atrib Array com os campos do atributo do tipo customizado
	 *		array(
	 *			'nome_do_campo' => 'valor do campo'
	 *		)
	 * @return int ID interno do direito inserido
	 */
	private static function insert_right($display_name, $description, $mail_nickname, $field_custom_atrib)
	{
		$insert_right = "INSERT INTO
							atributo 
							(	
								display_name, 
								right_id,
								description
							)
						VALUES
							(
								'" . MySQLConnector::escape_string($display_name) . "',
								'" . MySQLConnector::escape_string($mail_nickname) . "',
								'" . MySQLConnector::escape_string($description) . "'
							)";
		$internal_right_id = MySQLConnector::bd_insert($insert_right);
		$return_data['internal_right_id'] = $internal_right_id;
		$keys = array_keys($field_custom_atrib);
		
		for($i = 0; $i < count($keys); $i++)
		{
			$insert_custom ="INSERT INTO
								valor_campo_atributo_customizado 
								(	
									internal_right_id, 
									name,
									value
								)
							 VALUES
								(
									'" . MySQLConnector::escape_string($internal_right_id) . "',
									'" . MySQLConnector::escape_string($keys[$i]) . "',
									'" . MySQLConnector::escape_string($field_custom_atrib[$keys[$i]]) . "'
								)";
		
			MySQLConnector::bd_insert($insert_custom);
		}
		
		return $return_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Busca o id interno do direito
	 * @param string $right_id ID que sera buscado
	 * @param string $type_id Indica se o id fornecido eh interno ou nao
	 *			Valores possiveis: 'internal_right_id' => O valor eh o id interno, 'right_id' => O valor eh o id externo do direito
	 * @return int ID interno do direito ou -1 caso nao exista o direito
	 */
	private static function search_right_by_type($right_id, $type_id)
	{
		if($type_id == 'internal_right_id')
		{
			$search_restriction = "internal_right_id ='" . MySQLConnector::escape_string($right_id) . "'";
		}
		else
		{
			$search_restriction = "right_id ='" . MySQLConnector::escape_string($right_id) . "'";
		}
		
		$find_right_sql = "	SELECT 
								internal_right_id
							FROM	
								atributo 
							WHERE ";
				
		$right_exist = MySQLConnector::bd_search($find_right_sql.$search_restriction);
		
		$right_internal_id = -1;
		
		if(count($right_exist) > 0)
		{
			$right_internal_id = $right_exist[0]['internal_right_id'];
		}
		
		return $right_internal_id;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Atualiza um campo do direito
	 * @param array $field_value Array com o novo valor do atributo
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
	private static function update_right_field($field_value, $right_id, $type_id)
	{
		$right_internal_id = self::search_right_by_type($right_id, $type_id);
		
		if($right_internal_id != -1)
		{
			$update_right_sql = "UPDATE
									atributo 
								SET
									[%1]
								WHERE 
									internal_right_id ='" . MySQLConnector::escape_string($right_internal_id) . "'";
			$update_fields_array = array();
			for($i = 0; $i < count($field_value); $i++)
			{
				$update_fields_array[] = $field_value[$i]['key'] . " = '" . MySQLConnector::escape_string($field_value[$i]['value']) . "'";
			}
			
			$update_fields = implode(", ",$update_fields_array);	
			$update_right_sql = str_replace("[%1]", $update_fields, $update_right_sql);
			MySQLConnector::bd_update($update_right_sql);
		}
		
		return $right_internal_id;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Atualiza um campo do direito
	 * @param array $data Dados para atualizar direito
	 *		array(
	 *			'type' => string Indica se o id fornecido eh interno ou nao
	 *							Valores possiveis:	'internal_right_id' => O valor eh o id interno, 
	 *												'right_id' => O valor eh o id externo do direito
	 *			'id' => string ID do direito
	 *			'displayName' => string Nome do Direito,
	 *			'description' => string Descricao do Direito
	 *			'item_N' => string Campo atributo customizado
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function update_right($data)
	{
		$return_data = array();
		$error = "";
		$action_type = "update";
		$internal_right_id = -1;
		$found_error = false;
		
		unset($data['action']);
		
		if(isset($data['type'], $data['id']))
		{
			$internal_right_id = self::search_right_by_type($data['id'], $data['type']);
			if($internal_right_id == "-1")
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'Right not found! Cannot update right');
				}
				$found_error == true;
			}
		}
		else
		{
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Structural error: missing "type", "id"');
			}
			$error = "Request_BadRequest";
			$found_error = true;
		}
		
		if(!$found_error && isset($data['right_id'], $data['type'], $data['id']))
		{
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Cannot change id of right - Not Allowed');
			}
			$error = "Request_ResourceNotAllowed";
			$found_error = true;
		}
		
		if(!$found_error && isset($data['displayName'], $data['type'], $data['id']))
		{
			$field_value = array(
				array(
					'key' => 'display_name',
					'value' => $data['displayName']
				)
			);
			
			$result = self::update_right_field($field_value, $data['id'], $data['type']);
			
			if($result != "-1")
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'Right found! Updated displayName');
				}
				$error = "";
				$return_data['internal_right_id'] = $result;
				$internal_right_id = $result;
			}
			else
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'Right not found! Cannot update displayName');
				}
				$error = "Request_ResourceNotFound";
				$found_error = true;
			}
			
			unset($data['displayName']);
		}
		
		if(!$found_error && isset($data['description'], $data['type'], $data['id']))
		{
			$field_value = array(
				array(
					'key' => 'description',
					'value' => $data['description']
				)
			);
			
			$result = self::update_right_field($field_value, $data['id'], $data['type']);
			
			if($result != "-1")
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'Right found! Updated description');
				}
				$error = "";
				$return_data['internal_right_id'] = $result;
				$internal_right_id = $result;
			}
			else
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'Right not found! Cannot update description');
				}
				$error = "Request_ResourceNotFound";
				$found_error = true;
			}
			
			unset($data['description']);
		}
		
		if(!$found_error && $internal_right_id != "-1")
		{
			unset($data['id'], $data['type']);
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Right found! Updated field of custom atribute');
			}
			self::update_right_custom($internal_right_id, $data);
			if(count($data) > 0)
			{
				$return_data['internal_right_id'] = $internal_right_id;
			}
		}
		
		$return_formated_data = self::format_return_data($action_type, $return_data, $error);
		
		return $return_formated_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Busca um campo do atributo customizado atraves do nome do campo
	 * @param int $internal_right_id ID interno do direito
	 * @param string $name Nome do campo do atributo customizado
	 * @return int ID do campo customizado
	 */
	private static function find_right_custom($internal_right_id, $name)
	{
		$internal_custom_atrib_id = -1;
		
		$busca_atributo_customizado = "	SELECT 
											internal_custom_atrib_id
										FROM
											valor_campo_atributo_customizado
										WHERE
												internal_right_id = '" . MySQLConnector::escape_string($internal_right_id) . "'
											AND
												name = '" . MySQLConnector::escape_string($name) . "'";
												
		$atributo_customizado = MySQLConnector::bd_search($busca_atributo_customizado);
		
		if(count($atributo_customizado) > 0)
		{
			$internal_custom_atrib_id = $atributo_customizado[0]['internal_custom_atrib_id'];
		}
		
		return $internal_custom_atrib_id;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Atualiza/Insere um campo do atributo customizado
	 * @param int $internal_right_id ID interno do direito
	 * @param array $field_custom_atrib Array com os campos do atributo customizado a serem atualizados
	 *		array(
	 *			'nome_do_campo' => 'novo_valor_campo'
	 *		)
	 */
	private static function update_right_custom($internal_right_id, $field_custom_atrib)
	{
		$keys = array_keys($field_custom_atrib);
		for($i = 0; $i < count($keys); $i++)
		{
			
			$internal_custom_atrib_id = self::find_right_custom($internal_right_id, $keys[$i]);
			
			if($internal_custom_atrib_id == "-1")
			{
				$insert_custom ="INSERT INTO
									valor_campo_atributo_customizado 
									(	
										internal_right_id, 
										name,
										value
									)
								 VALUES
									(
										'" . MySQLConnector::escape_string($internal_right_id) . "',
										'" . MySQLConnector::escape_string($keys[$i]) . "',
										'" . MySQLConnector::escape_string($field_custom_atrib[$keys[$i]]) . "'
									)";
				MySQLConnector::bd_insert($insert_custom);
			}
			else
			{
				$update_custom_sql ="UPDATE
										valor_campo_atributo_customizado 
									SET
										value = '" . MySQLConnector::escape_string($field_custom_atrib[$keys[$i]]) . "'
									WHERE 
										internal_custom_atrib_id ='" . MySQLConnector::escape_string($internal_custom_atrib_id) . "'";;
				MySQLConnector::bd_update($update_custom_sql);
			}
		}
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Remove um direito
	 * @param array $data Dados para remover direito
	 *		array(
	 *			'type' => string Indica se o id fornecido eh interno ou nao
	 *							Valores possiveis:	'internal_right_id' => O valor eh o id interno, 
	 *												'right_id' => O valor eh o id externo do direito
	 *			'id' => string ID do direito
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function remove_right($data)
	{
		$return_data = array();
		$error = "Request_BadRequest";
		$action_type = "remove_right";
		
		if(isset($data['type'], $data['id']))
		{
			$internal_right_id = self::search_right_by_type($data['id'], $data['type']);
			
			if($internal_right_id != "-1")
			{
				$delete_right_sql = "DELETE FROM
										atributo
									WHERE 
										internal_right_id ='" . MySQLConnector::escape_string($internal_right_id) . "'";
				
				MySQLConnector::bd_delete($delete_right_sql);
				
				$delete_custom_right_sql = "DELETE FROM
												valor_campo_atributo_customizado
											WHERE 
												internal_right_id ='" . MySQLConnector::escape_string($internal_right_id) . "'";
				
				MySQLConnector::bd_delete($delete_custom_right_sql);
				
				$delete_rights_to_user_sql = " DELETE FROM
												rel_usuario_atributo
											WHERE 
												internal_right_id ='" . MySQLConnector::escape_string($internal_right_id) . "'";
				MySQLConnector::bd_delete($delete_rights_to_user_sql);
				
				$error = "";
				$return_data['internal_right_id'] = $internal_right_id;
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "Right removed");
				}
			}
			else
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "Right not found! Right not removed");
				}
				$error = "Request_ResourceNotFound";
			}
		}
		else
		{
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Structural error: missing "type", "id"');
			}
		}
		
		$return_formated_data = self::format_return_data($action_type, $return_data, $error);
		
		return $return_formated_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Formata dados para o retorno
	 * @param string $action_type Tipo de acao
	 * @param string $return_data Dado a ser retornado
	 * @param string $erro String que indica o erro a ser retornado
	 * @return array Array com a resposta da requisicao no seguinte formato
	 */
	private static function format_return_data($action_type, $return_data, $erro)
	{
		switch ($erro)
		{
			case "Request_ResourceNotAllowed":
			{
				$return_data = array(
					'odata.error' => array(
						'code' => 'Request_ResourceNotAllowed',
						'message' => array(
							'value' => "Cannot change ID of a right! Not Allowed"
						)
					)
				);
			}
			break;
			
			case "Request_BadRequest":
			{
				$return_data = array(
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
				$return_data = array(
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
				switch($action_type)
				{
					case "find_all_rights":
					case "find_all_rights_user":
					{
						$return_data	 = array(
							'value' => $return_data
						);
					}
					break;
					
					case "associate_right_to_user":
					case "unassociate_right_to_user":
					case "change_user_right":
					{
						$return_data = array(
							'status' => 'OK'
						);
					}
					break;
					
					default:
					{
						$return_data = array(
							'objectId' => $return_data['internal_right_id']
						);
					}
					break;
				}
			}
			break;
		}	
		return $return_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Busca todos os direitos
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function find_all_rights()
	{
		$find_right_sql = "	SELECT 
								display_name as displayName, right_id as mailNickname, description, internal_right_id
							FROM	
								atributo";
		$action_type = "find_all_rights";
		$error = "";
		
		$all_rights = MySQLConnector::bd_search($find_right_sql);
		
		for($i = 0; $i < count($all_rights); $i++)
		{
			$find_custom_sql = "SELECT 
									name, value
								FROM	
									valor_campo_atributo_customizado
								WHERE
									internal_right_id = '" . MySQLConnector::escape_string($all_rights[$i]['internal_right_id']) . "'";
			unset($all_rights[$i]['internal_right_id']);		
			
			$field_custom_atrib = MySQLConnector::bd_search($find_custom_sql);
			if(!empty($field_custom_atrib))
			{
				$all_rights[$i] = array_merge($all_rights[$i], array_combine(array_column($field_custom_atrib, 'name'), array_column($field_custom_atrib, 'value')));
			}
		}
		
		$return_formated_data = self::format_return_data($action_type, $all_rights, $error);
		if($GLOBALS['debug'])
		{
			syslog(LOG_INFO, 'Return all rights');
		}
		return $return_formated_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Associa usuario a um direito
	 * @param array $data Dados para associar direito a um usuario
	 *		array(
	 *			'type_user' => string Indica se o id fornecido do usuario eh interno ou nao
	 *							Valores possiveis:	'internal_user_id' => O valor eh o id usuario, 
	 *												'user_id' => O valor eh o id externo do usuario
	 *			'id_user' => string ID do usuario
	 *			'type_right' => string Indica se o id fornecido eh interno ou nao
	 *							Valores possiveis:	'internal_right_id' => O valor eh o id interno, 
	 *												'right_id' => O valor eh o id externo do direito
	 *			'id_right' => string ID do direito
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function associate_right_to_user($data)
	{
		$action_type = "associate_right_to_user";
		$return_data = array();
		
		if(isset($data['type_user'], $data['id_user'], $data['type_right'], $data['id_right']))
		{
			$user_internal_id = User::search_user_by_type($data['id_user'], $data['type_user']);
			$right_internal_id = self::search_right_by_type($data['id_right'], $data['type_right']);
			if($user_internal_id == "-1" || $right_internal_id == "-1")
			{
				$error = "Request_ResourceNotFound";
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'User or Right not found!');
				}
			}
			else
			{
				$insert_rel = "REPLACE INTO
									rel_usuario_atributo 
									(	
										internal_right_id, 
										internal_user_id
									)
								VALUES
									(
										'" . MySQLConnector::escape_string($right_internal_id) . "',
										'" . MySQLConnector::escape_string($user_internal_id) . "'
									)";
				
				MySQLConnector::bd_insert($insert_rel);
				$error = "";
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'User associated to right!');
				}
			}
		}
		else
		{
			$error = "Request_BadRequest";
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Structural error: missing "type_user", "id_user", "type_right", "id_right"');
			}
		}
		
		$return_formated_data = self::format_return_data($action_type, $return_data, $error);
		
		return $return_formated_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Desassocia usuario a um direito
	 * @param array $data Dados para desassocioar direito a um usuario
	 *		array(
	 *			'type_user' => string Indica se o id fornecido do usuario eh interno ou nao
	 *							Valores possiveis:	'internal_user_id' => O valor eh o id usuario, 
	 *												'user_id' => O valor eh o id externo do usuario
	 *			'id_user' => string ID do usuario
	 *			'type_right' => string Indica se o id fornecido eh interno ou nao
	 *							Valores possiveis:	'internal_right_id' => O valor eh o id interno, 
	 *												'right_id' => O valor eh o id externo do direito
	 *			'id_right' => string ID do direito
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function unassociate_right_to_user($data)
	{
		$action_type = "unassociate_right_to_user";
		$return_data = array();
		
		if(isset($data['type_user'], $data['id_user'], $data['type_right'], $data['id_right']))
		{
			$user_internal_id = User::search_user_by_type($data['id_user'], $data['type_user']);
			$right_internal_id = self::search_right_by_type($data['id_right'], $data['type_right']);
			
			if($user_internal_id == "-1" || $right_internal_id == "-1")
			{
				$error = "Request_ResourceNotFound";
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'User or Right not found!');
				}
			}
			else
			{
				$remove_rel = "	DELETE FROM
									rel_usuario_atributo 
								WHERE
										internal_right_id = '" . MySQLConnector::escape_string($right_internal_id) . "'
									AND
										internal_user_id = '" . MySQLConnector::escape_string($user_internal_id) . "'";
				
				MySQLConnector::bd_delete($remove_rel);
				$error = "";
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'User dissociated to right!');
				}
			}
		}
		else
		{
			$error = "Request_BadRequest";
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Structural error: missing "type_user", "id_user", "type_right", "id_right"');
			}
		}

		$return_formated_data = self::format_return_data($action_type, $return_data, $error);
		return $return_formated_data;
	}


	/**
	 * Altera direito do usuário
	 * @param array $data Dados para desassocioar direito a um usuario
	 *		array(
	 *			'action' => Identificador da interação
	 *			'type_user' => string Indica se o id fornecido do usuario eh interno ou nao
	 *							Valores possiveis:	'internal_user_id' => O valor eh o id usuario, 
	 *												'user_id' => O valor eh o id externo do usuario
	 *			'id_user' => string ID do usuario
	 *			'type_right_unassociate' => string Indica se o id fornecido para desassociacao eh interno ou nao
	 *							Valores possiveis:	'internal_right_id' => O valor eh o id interno, 
	 *												'right_id' => O valor eh o id externo do direito
	 *			'id_right_unassociate' => string ID do direito a ser desassociado
	 *			'type_right_associate' => string Indica se o id fornecido para associacao eh interno ou nao
	 *							Valores possiveis:	'internal_right_id' => O valor eh o id interno, 
	 *												'right_id' => O valor eh o id externo do direito
	 *			'id_right_associate' => string ID do direito a ser associado
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function change_user_right($data)
	{
		$action_type = "change_user_right";
		$return_data = array();
		$error = "";

		if(
			isset(
				$data['action'], $data['type_user'], $data['id_user'], $data['type_right_unassociate'],
				$data['id_right_unassociate'], $data['type_right_associate'], $data['id_right_associate']
			)
		)
		{
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Has met the prerequisites of the rights change service');
			}

			// Para simular falha, descomente a linha abaixo
			// $error = "Request_ResourceNotFound";
		}

		$return_formated_data = self::format_return_data($action_type, $return_data, $error);
		return $return_formated_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Busca todos direitos de um usuario
	 * @param array $data Parametro para buscar direitos de um usuario
	 *		array(
	 *			'type_user' => string Indica se o id fornecido eh interno ou nao
	 *							Valores possiveis:	'internal_user_id' => O valor eh o id interno, 
	 *												'user_id' => O valor eh o id externo do direito
	 *			'id_user' => string ID do usuario
	 *		)
	 * @return array Array com a resposta da requisicao no formato da funcao format_return_data
	 */
	public static function find_all_rights_user($data)
	{
		$all_rights = array();
		$action_type = "find_all_rights_user";
		
		if(isset($data['type_user'], $data['id_user']))
		{
			$user_internal_id = User::search_user_by_type($data['id_user'], $data['type_user']);
			if($user_internal_id == "-1")
			{
				$error = "Request_ResourceNotFound";
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'User not found! Cannot find rights!');
				}
			}
			else
			{
				$all_rights_user_sql = 
					"SELECT
						`atributo`.`display_name` as displayName, 
						`atributo`.`right_id` as mailNickname, 
						`atributo`.`description`, 
						`atributo`.`internal_right_id`
					FROM
						`rel_usuario_atributo`
					INNER JOIN
						`atributo`
					ON
						`rel_usuario_atributo`.`internal_right_id` = `atributo`.`internal_right_id`
					WHERE
						`rel_usuario_atributo`.`internal_user_id` = '" . MySQLConnector::escape_string($user_internal_id) . "'";

				$all_rights = MySQLConnector::bd_search($all_rights_user_sql);

				for($i = 0; $i < count($all_rights); $i++)
				{
					$find_custom_sql = "SELECT 
											name, value
										FROM	
											valor_campo_atributo_customizado
										WHERE
											internal_right_id = '" . MySQLConnector::escape_string($all_rights[$i]['internal_right_id']) . "'";
					unset($all_rights[$i]['internal_right_id']);

					$field_custom_atrib = MySQLConnector::bd_search($find_custom_sql);

					if(!empty($field_custom_atrib))
					{
						$all_rights[$i] = array_merge($all_rights[$i], array_combine(array_column($field_custom_atrib, 'name'), array_column($field_custom_atrib, 'value')));
					}
				}
				
				$error = "";
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, 'User found! Return all rights!');
				}
			}
		}
		else
		{
			$error = "Request_BadRequest";
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, 'Structural error: missing "type_user", "id_user"');
			}
		}
		
		$return_formated_data = self::format_return_data($action_type, $all_rights, $error);
		return $return_formated_data;
	}
}