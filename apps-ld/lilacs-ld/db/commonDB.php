<?php
require_once("conexionDB.php");
require_once(PATH_LILACS_LD."common.php");
require_once(PATH_COMMON_LD."validation.inc");

/**
 * Actualiza una entidad en la BD. Actualiza los campos data, context y modified
 * @param string $table nombre de la tabla 
 * @param int $id Identificador unico de registro
 * @param array $data_arr arreglo asociativo con datos a actualizar en el recurso, como pares (propiedad, valor)
 * @param bool $path 
 *   si 1: actualizar json, se actualiza con JSON_SET (se actualizan los campos existentes y se agregan los nuevos)
 *   si 0: remplazar json con nuevo objeto, (se actualiza con JSON_OBJECT)
 * @param $allowed_key  
 *   Array de nombres de las propiedades permitidas en el json  
 *  
 * @return Bool TRUE|FALSE Resultado de la consulta de actualizacion
 */
function _updateEntityDB($table, $id, $data_arr, $path = 0){
  global $link, $lilacs_ctx;
  
  //Asegurarse de que tengan los valores correctos, no dejar sobrescribir
  $data_arr['@id'] = URL_LILACS_LD.$table."s/".$id;
  $data_arr['id'] = $id;

  //data preparado para pasar por JSON_OBJECT (path:0) o JSON_SET (path:1, con '$.' delante) 
  $data_str = array_to_objStr($data_arr, $link, 0, $path);

  //agregar campos fecha para incluirlos en context (no estan en data)
  $data_arr['created'] = 1;
  $data_arr['modified'] = 1;
  if($table=='document'){
    $data_arr['transfered'] = 1;
    $data_arr['originalRecord'] = 1;
  }  

  //obtener context preparado para pasar por JSON_OBJECT o JSON_SET
  $context_arr = getDataContext($data_arr, $lilacs_ctx);
  $context_str = array_to_objStr(array('@context' => $context_arr), $link, 0, $path);

  if($path) //ya existe data y context se utiliza JSON_SET para actualizar prop existentes e incluir las nuevas
    $sql = "UPDATE $table SET data = JSON_SET(data, $data_str), context = JSON_SET(context, $context_str), modified=NOW() WHERE id = $id";
  else //data y context se reemplazan, se utiliza JSON_OBJECT para asignarles valor
    $sql = "UPDATE $table SET data = JSON_OBJECT($data_str), context = JSON_OBJECT($context_str), modified=NOW() WHERE id = $id";
  
  $r = mysqli_query($link, $sql);
  return $r;
}

/**
 * Devuelve la ctdad de registros de una tabla que cumplen con la condicion especificada
 * 
 * @param resource $link identificador de la conexion a la BD 
 * @param string $table (obligatorio) nombre de la tabla 
 * @param string $where (opcional) condicion de la clausula WHERE de la consulta (por ej:)
 * @param string $field (opcional) nombre del campo a contar (por defecto 'id')
 *  
 * @return integer: ctdad de registros de la tabla, false: si ocurrio error
 */
function getCount($link, $table, $where='', $field='id'){
  if($where)
    $where = "WHERE ". $where;
  $count = mysqli_fetch_row(mysqli_query($link, "SELECT count($field) FROM $table $where"));
  if($count!==false)
    return $count[0];
  else
    return false;  
}
?>
