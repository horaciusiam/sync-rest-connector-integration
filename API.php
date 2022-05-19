<?php
require_once("server.conf");
require_once("User.php");
require_once("Right.php");
require_once("Authentication.php");

mysqli_connect($GLOBALS['bd']['server'], 'test', 'passwordWS');

/**
 * DI-134: Utilitarios: WebServer REST para testes do conector
 * Processa dados recebidos pelo servidor
 * @param array $data Parametros recebidos pelo servidor
 * @param string $type Tipo de metodo HTTP
 * @param array $headers Headers da requisicao (Autenticacao)
 * @return array Retorno da requisicao formatado
 */
function process_method($data, $type, $headers)
{
	$return_processed_data = array(
		'odata.error' => array(
			'code' => 'Request_BadRequest',
			'message' => array(
				'value' => "Unknown action"
			)
		)
	);

	if(isset($data['action']))
	{
		if($data['action'] == 'oauth2')
		{
			$return_processed_data = Authentication::oauth2($data);
		}
		else
		{
			if(Authentication::validate_header($headers) == false)
			{
				syslog(LOG_INFO, 'Authentication failure');
				$return_processed_data = array(
						'odata.error' => array(
							'code' => 'Request_BadRequest',
							'message' => array(
								'value' => "Authentication failure"
							)
						)
					);
			}
			else
			{
				switch($data['action'])
				{
					case 'create_user':
					{
						if($type == "POST")
						{
							$return_processed_data = User::create_user($data);
						}
					}
					break;

					case 'check_user':
					{
						if($type == "POST")
						{
							$return_processed_data = User::check_user($data);
						}
					}
					break;

					case 'update_user':
					{
						//Restringe para comandos do tipo PATCH
						if($type == "PATCH")
						{
							$return_processed_data = User::update_user($data);
						}
					}
					break;

					case 'lock_user':
					{
						if($type == "PATCH")
						{
							$return_processed_data = User::lock_user($data);
						}
					}
					break;

					case 'reset_password':
					{
						if($type == "POST")
						{
							$return_processed_data = User::reset_password($data);
						}
					}
					break;

					case 'remove_user':
					{
						if($type == "DELETE")
						{
							$return_processed_data = User::remove_user($data);
						}
					}
					break;

					case 'find_all_users':
					{
						if($type == "GET")
						{
							$return_processed_data = User::find_all_users();
						}
					}
					break;

					case 'create_right':
					{
						if($type == "POST")
						{
							$return_processed_data = Right::create_right($data);
						}
					}
					break;

					case 'remove_right':
					{
						if($type == "DELETE")
						{
							$return_processed_data = Right::remove_right($data);
						}
					}
					break;

					case 'update_right':
					{
						if($type == "PATCH")
						{
							$return_processed_data = Right::update_right($data);
						}
					}
					break;

					case 'check_right':
					{
						if($type == "POST")
						{
							$return_processed_data = Right::check_right($data);
						}
					}
					break;

					case 'find_all_rights':
					{
						if($type == "GET")
						{
							$return_processed_data = Right::find_all_rights();
						}
					}
					break;

					case 'associate_right_to_user':
					{
						if($type == "POST")
						{
							$return_processed_data = Right::associate_right_to_user($data);
						}
					}
					break;

					case 'unassociate_right_to_user':
					{
						if($type == "POST")
						{
							$return_processed_data = Right::unassociate_right_to_user($data);
						}
					}
					break;

					case 'change_user_right':
					{
						if($type == "POST")
						{
							$return_processed_data = Right::change_user_right($data);
						}

						break;
					}

					case 'find_all_rights_user':
					{
						if($type == "POST")
						{
							$return_processed_data = Right::find_all_rights_user($data);
						}
					}
					break;
				}
			}
		}
	}

	return $return_processed_data;
}

//Remove expired token
if($GLOBALS['token_expiration_time'] > 0)
{
	Authentication::remove_expired_tokens($GLOBALS['token_expiration_time']);
}
//Process actions
$data = file_get_contents('php://input');
$output = array();

if($_SERVER['REQUEST_METHOD'] != 'GET' && !empty($data))
{
	$output = json_decode($data, true);
}

multidimensionalArrayMap("utf8_decode", $output, null, true);

$dadosRecebidos = array_merge($output, $_GET);

if( $GLOBALS['debug'] )
{
	syslog(LOG_INFO, "Received data: " . json_encode($dadosRecebidos) );
}

$response_data = process_method($dadosRecebidos, $_SERVER['REQUEST_METHOD'], apache_request_headers());
multidimensionalArrayMap('utf8_encode', $response_data, null, true);
$response_data = json_encode($response_data);
if($GLOBALS['debug'])
{
	syslog(LOG_INFO, "Response data: " . $response_data);
}
echo($response_data);
