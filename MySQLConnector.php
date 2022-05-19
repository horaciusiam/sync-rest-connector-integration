<?php 
require_once("server.conf");

class MySQLConnector
{
	protected static $connection;
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Conecta no banco de dados
	 * @return MySQLi Conexao com o banco de dados
	 */
	protected static function bd_connect() 
	{
		if(!isset(self::$connection)) 
		{
			self::$connection = new mysqli($GLOBALS['bd']['server'], $GLOBALS['bd']['user'], $GLOBALS['bd']['password'], $GLOBALS['bd']['database']);
		}
	
		if(self::$connection === false) 
		{
			if($GLOBALS['debug'])
			{
				syslog(LOG_INFO, "Mysql Connection failed");
			}
			return false;
		}
		return self::$connection;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Busca dados
	 * @param string $query Query de busca SQL
	 * @return array Resultado da pesquisa
	 */
	public static function bd_search($query) 
	{
		$connection = self::bd_connect();
		$return_data = array();
		
		if($connection !== false)
		{
			if ($result = $connection->query($query)) 
			{ 
				while($obj = $result->fetch_assoc())
				{ 
					$return_data[] = $obj;
				}
			}
			else
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "Search failed: " . $connection->error);
				}
			}
		}
		return $return_data;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Insere dados
	 * @param string $query Query de insercao SQL
	 * @return int ID do elemento inserido
	 */
	public static function bd_insert($query) 
	{
		$connection = self::bd_connect();
		$insert_id = 0;
		
		if($connection !== false)
		{
			if(!$connection->query($query))
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "Insert failed: " . $connection->error);
				}
			}
			else
			{
				$insert_id = $connection->insert_id;
			}
		}
		
		return $insert_id;
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Atualiza dados no banco
	 * @param string $query Query de update de dados SQL
	 */
	public static function bd_update($query) 
	{
		$connection = self::bd_connect();
		
		if($connection !== false)
		{
			if(!$connection->query($query))
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "Update failed: " . $connection->error);
				}
			}
		}
	}
	
	/**
	 * DI-134: Utilitarios: WebServer REST para testes do conector
	 * Remove dados do banco
	 * @param string $query Query de busca SQL
	 */
	public static function bd_delete($query)
	{
		$connection = self::bd_connect();
		
		if($connection !== false)
		{
			if(!$connection->query($query))
			{
				if($GLOBALS['debug'])
				{
					syslog(LOG_INFO, "Delete failed: " . $connection->error);
				}
			}
		}
	}

	/**
	 * Escapa uma string para ser utilizada em um comando SQL
	 * @param string $string String a ser escapada
	 * @return string String escapada
	 */
	public static function escape_string( $string )
	{
		$connection = self::bd_connect();

		if( $connection !== false )
		{
			$string = $connection->real_escape_string( $string );
		}

		return $string;
	}
}
