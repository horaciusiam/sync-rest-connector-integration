<?
if (!function_exists('array_column'))
{
	/**
	 * Retorna os valores de uma coluna/indice de um Array
	 * 
	 * @param mixed[] $array Array com os valores
	 * @param string $column_key Coluna/indice do array com o valor a ser buscado
	 * @return mixed[] Array numerico com os valores das colunas do array de entrada
	 */
	function array_column($array,$column_key){
		
		$array_return=array();
		
		foreach( $array as &$values ){
			$column_keys=array_keys($values);
			for($x=0;$x<count($column_keys);$x++){
				if ($column_keys[$x]==$column_key){
					array_push($array_return,$values[$column_key]);
				}
			}
		}
		
		return $array_return;
	}
}

/**
 * Aplica uma funcao aos elementos de um array em todos os seus niveis.
 *
 * @param string $funcao Funcao que sera aplicada
 * @param array $array Array cujos elementos terao a funcao aplicada
 * @param string Tipo dos elementos a terem a funcao aplicada, ou NULL para a funcao ser aplicada a todos
 * @param boolean $aplicarFuncaoNasChaves indica se a funcao sera aplicada na chave
 * @return void
 */
function multidimensionalArrayMap($funcao, &$array, $tipo = null, $aplicarFuncaoNasChaves = false)
{
	$nova = array();
	
	if (is_array($array))
	{
		$keys = array_keys($array);
		$total = count($keys);
		
		for ($i = 0; $i < $total; $i++)
		{
			if ($aplicarFuncaoNasChaves && ($tipo === NULL || $tipo === gettype($key)))
			{
				$key = $funcao($keys[$i]);
			}
			else
			{
				$key = $keys[$i];
			}
			
			$nova[$key] = $array[$keys[$i]];
			
			if (is_array($nova[$key]))
			{
				multidimensionalArrayMap($funcao, $nova[$key], $tipo, $aplicarFuncaoNasChaves);
			}
			else if ($tipo === NULL || $tipo === gettype($nova[$key]))
			{
				$nova[$key] = $funcao($nova[$key]);
			}
		}
		
		$array = $nova;
	}
}
