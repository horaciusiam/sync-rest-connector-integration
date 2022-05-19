<?PHP
require_once ("server.conf");
require_once ("functions.php");

class RestSincClient
{
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Autentica um usuario no webservice rest sincrono de teste 
	 * @return string Token de retorno ou string vazia
	 */
	public static function create_token()
	{
		$return_token = "";
		$authentication_data['action'] = 'oauth2';
		$authentication_data['grant_type'] = 'client_credentials';
		$authentication_data['client_id'] = $GLOBALS['adm_user'];
		$authentication_data['client_secret'] = $GLOBALS['adm_pass'];
		
		$output = self::send_server_requisiton($authentication_data, null, "POST");
		if(isset($output->{'token_type'}, $output->{'access_token'}))
		{
			$return_token = "Authorization: " . $output->{'token_type'} . " " . $output->{'access_token'};
		}
		
		return $return_token;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Envia uma requisacao para o webservice de teste
	 * @param array $data Dados que serao enviado para o servidor
	 * @param string $authentication_token Token de authenticacao que sera enviado para o servidor no formato:
	 *				"Authorization: SWT token"
	 * @param string $type Tipo de metodo HTTP. POST, GET, PUT, PATCH, DELETE
	 * @return string Retorno do comando curl, enviado pelo servidor
	 */
	public static function send_server_requisiton($data, $authentication_token = null, $type = "POST")
	{
		multidimensionalArrayMap('utf8_encode', $data, null, true);
		$data = http_build_query($data);
		$url = $GLOBALS['url'];
			
		$ch = curl_init();
		if($authentication_token != null)
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, array($authentication_token));
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		if($type != 'GET')
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			if($type != "POST")
			{
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
			}
			
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		else
		{
			$url .= "?" . $data;
		}
		
		curl_setopt($ch, CURLOPT_URL, $url);
		$output = curl_exec($ch);
		$output = json_decode($output);
		multidimensionalArrayMap('utf8_decode', $output, null, true);
		
		return $output;
	}
	
}