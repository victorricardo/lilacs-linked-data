<?php
/**
 * @file
 * Convierte arreglos en string valido para pasar por las funciones JSON_OBJECT o JSON_ARRAY
 */

/**
 * Convierte un array (recorriendo todos sus niveles de profundidad) en un string válido 
 * para pasar por la funcion JSON_OBJECT() de MySQL
 *
 * @param $arr
 *   Array asociativo, cuyas llaves son las propiedades del objeto  
 *   Ej: array("@type"=>"Person","name"=> "Elinor Dulzaides Iglesias","jobTitle"=>"Responsable")
 * @param $link
 *   identificador de la base de datos en la que se realiza la consulta
 * @param $encode
 *   Boolean
 *   si 1: se pasa por utf8_encode() el valor del campo cuando su tipo es string
 *   si 0: no se pasa por utf8_encode() el valor del campo cuando su tipo es string
 * @param $path
 *   Boolean
 *   si 1: se concatena delante de key el identificador de path '$.' solo en el primer nivel, para JSON_REPLACE 
 *   si 0: no se modifica key
 *
 * @return
 *   string con formato datos del array como un json válido para pasar por la funcion 
 *   JSON_OBJECT() de MySQL   
 *   Ej: "@type","Person","name","Elinor Dulzaides Iglesias","jobTitle","Responsable"
 */
function array_to_objStr($arr, $link, $encode=0, $path = 0){
  
  $str = '';  
  foreach($arr as $key => $value){
    if($path){
      $key = field2dataField($key);  
      //$key = '$.'. $key;  
    }  
    if(is_numeric($value)){
      $str .= "'$key', $value, ";
    }
    elseif(is_string($value)){
      if($encode)  
        $value = utf8_encode($value);
      $value = mysqli_real_escape_string($link, $value);
      $str .= "'$key', '$value', ";
    }
    elseif(is_array($value) AND count($value)){
      if(is_assoc_array($value)){
        //es un array asociativo por tanto un object  
        $obj_str = array_to_objStr($value, $link, $encode, 0);
        $str .= "'$key', JSON_OBJECT($obj_str), ";
      }
      else{
        //sino es un array  
        $arr_str = array_to_arrStr($value, $link, $encode);
        $str .= "'$key', JSON_ARRAY($arr_str), ";
      }
    }  
  }  
  $str = substr($str,0,-2); 
  return $str;
}  

/**
 * Convierte un array (recorriendo todos sus niveles de profundidad) en un string válido 
 * para pasar por la funcion JSON_ARRAY() de MySQL
 *
 * @param $arr
 *   Array con los datos a convertir en un arreglo json
 *   Ej: array('Facultad de Ciencias Médicas','Universidad Médica de Cienfuegos')
 * @param $encode
 *   si 1: se pasa por utf8_encode() el valor del campo cuando su tipo es string
 *   si 0: no se pasa por utf8_encode() el valor del campo cuando su tipo es string
 *
 * @return
 *   string con formato datos del array como un json válido para pasar por la funcion 
 *   JSON_ARRAY() de MySQL   
 *   Ej: 'Facultad de Ciencias Médicas','Universidad Médica de Cienfuegos'
 */
function array_to_arrStr($arr, $link, $encode=0){
  $str = '';  
  foreach($arr as $key => $value){
    if(is_numeric($value)){
      $str .= "$value, ";
    }
    elseif(is_string($value)){
      if($encode)  
        $value = utf8_encode($value);
      $value = mysqli_real_escape_string($link, $value);
      $str .= "'$value', ";
    }
    elseif(is_array($value) AND count($value)){
      if( is_assoc_array($value)){
        //es un array asociativo y por tanto un object  
        $obj_str = array_to_objStr($value, $link, $encode);
        $str .= "JSON_OBJECT($obj_str), ";
      }
      else{
        //es un array  
        $arr_str = array_to_arrStr($value, $link, $encode);
        $str .= "JSON_ARRAY($arr_str), ";
      }
    }
    
  }  
  $str = substr($str,0,-2); 
  return $str;
}  

/**
 * Chequea si el string es válido para pasar por la funcion JSON_OBJECT() de MySQL
 *
 * @param $json_str
 *   String a chequear
 *   Ej: 
 * @param $link
 *   Identificador de la base de datos activa 
 *
 * @return
 *   1: si el string no es válido
 *   0: en caso contrario
 */
function json_argument_error($json_str,$link){
  $query = "SELECT JSON_OBJECT($json_str)";
  if ($res = mysqli_query($link, $query)) {
    //$row = mysqli_fetch_row($res);
    return 0;
  } else {
    printf("SQL is %s!<BR>", $query);
    printf("Error en json: %s<BR>", mysqli_error($link));
    return 1;
  }
}
 
/**
 * Chequea si el array es asociativo o no, util para saber que debe traducirse como object o array respectivamente
 *
 * @param $arr
 *   Arreglo a chequear
 *   Ej:  array("@type"=>"Person","name"=> "Lázaro de León Rosales") es asociativo
 *      array("Centro Cooperante da BVS", "SCAD", "Rede LILACS") no es asociativo
 * @return
 *   1: si el array es asociativo
 *   0: en caso contrario
 */
function is_assoc_array($arr){
  if(!is_array($arr))
    echo $arr."<BR>";  
  $keys = array_keys($arr);
  foreach($keys as $key){
    if(is_string($key)) {
      return true; //la llave es string, es asociativo
    }
  }
  return false; 
}

function field2dataField($field){
  $pos = strpos($field, '@'); 
  if($pos !== FALSE){
    $field = str_replace('@', "\"@", $field) ."\"";
    //$field = substr($field, 0, $pos). "\"@" . substr($field, $pos+1) ."\"";
  }
  return "$.$field";  
}

?>
