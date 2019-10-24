<?php
/**
 * Valida los campos, deja solo los que estan definidos en context y elimina los repetidos. Si el 
 * campo es un array de objetos y tiene subcampo agrega [*]
 * 
 * @param array $fields campos a validar. 
 * @return array : arreglo de campos validos
 */
function validateAllowed($fields, $allowed_prop, $objectsArray_prop=array()){
  
  $valid_fields=array();
  foreach($fields as $field){
    if(strpos($field,'.')!==false){  //por ej: provider.name.@value
      $sub_fields=explode('.',$field);  
      $is_valid=1;
      $cant = count($sub_fields);
      $i=0;
      foreach($sub_fields as $sub_field){
        if(!in_array($sub_field,$allowed_prop)){
          $is_valid=0;
          break;
        }
        $i +=1;
        //si la prop es array de obj y tiene subprop se agrega [*], ej: label.@language => label[*].@language 
        if($i<$cant AND in_array($sub_field, $objectsArray_prop)){
          $sub_fields[$i-1] .= '[*]';  
          $field = implode('.', $sub_fields);
        }
      }
      if($is_valid AND !in_array($field,$valid_fields)){
        $valid_fields[] = $field;  
      }
    }  
    else{
      if(in_array($field,$allowed_prop) AND !in_array($field,$valid_fields)){
        $valid_fields[] = $field;  
      }
        
    }
  }
  return $valid_fields;
} 

/**
 * Valida que las propiedades requeridas existen en el json $data
 * 
 * @param string $data json con los datos de una entidad. 
 * @param string $required paths de las propiedades requeridas separadas por coma 
 * 
 * @return boolean: true si todas las propiedades requeridas existen en data, false en caso contrario.
 */
function validateRequired($data, $required, $link){
  $row = mysqli_fetch_row(mysqli_query($link, "SELECT JSON_CONTAINS_PATH('$data', 'all', $required)"));
  if( !$row[0]){
    return false; 
  }
  else
    return true;
}

/**
 * Valida si el valor es un numero natural
 * 
 * @param number $value valor a validar 
 * 
 * @return boolean: true si es natural, false en caso contrario.
 */
function validateNatural($value){
  if(!preg_match('/^[0-9]{1,}$/', $value)){
    return false;  
  }
  else{
    return true;  
  }    
}

/**
 * Valida el json con mysql JSON_VALID y json_decode
 * 
 * @param string $data Json a validar 
 * @param resource $link identificador de la conexion a la BD 
 * 
 * @return mixed: arreglo resultado de json_decode de data si el json es valido, false en caso contrario.
 */
function validateJson($data, $link){

  $row = mysqli_fetch_row(mysqli_query($link, "SELECT JSON_VALID('$data')"));
  if( !$row[0]) {
    return false;  
  }
  else{
    $data_arr = json_decode($data,true);
    if(!$data_arr){
      return false;  
    }
    else{  
      return $data_arr;  
    }  
  }    
    
}

/**
 * Verifies the syntax of the given e-mail address. (from Drupal)
 *
 * See @link http://tools.ietf.org/html/rfc5322 RFC 5322 @endlink for details.
 *
 * @param $mail
 *   A string containing an e-mail address.
 * @return
 *   1 if the email address is valid, 0 if it is invalid or empty, and FALSE if
 *   there is an input error (such as passing in an array instead of a string).
 */
function valid_email_address($mail) {
  $user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
  $domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.?)+';
  $ipv4 = '[0-9]{1,3}(\.[0-9]{1,3}){3}';
  $ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';

  return preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $mail);
}

/**
 * Chequea si es hexadecimal (solo a-z y 0-9) y tiene 32 caracteres
 *
 * @param $api_key
 *   string con el api_key a chequear
 * @return
 *   1 si el api_key es valido, 0 si no es valido o vacio, y FALSE si ocurrio un error 
 */
function validMd5($api_key) {

  return preg_match("/^[a-f0-9]{32}$/", $api_key);
}
  
?>