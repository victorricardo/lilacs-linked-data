<?php
require_once(PATH_COMMON_LD."array2json.inc");

//PENDIENTE: en context de decs no estan las fechas meshUpdated, decsUpdated

/**
 * Devuelve un arreglo con los nombres de las propiedades de context, a las que se agregan las propiedades de rdf utilizadas
 *  
 * @param array $arr_ctx: context como arreglo asociativo  
 * 
 * @return array Arreglo de propiedades utilizadas en el modelo de datos
 */
function getAllowedProperties($arr_ctx){
  
  $ctx_keys = array_keys($arr_ctx['@context']);

  //propiedades a partir de rdf
  $properties = array('@type', '@id', '@value', '@language', '@context', 'label');
  foreach($ctx_keys as $value){
    //dejar solo las propiedades, se quitan espacios de nombres y clases  
    if(!in_array($value, array('schema','lilacs','meshv')) AND $value[0]==strtolower($value[0])){
      $properties[] = $value;  
    }  
  }

  return $properties;
}

/**
 * Calcula el context asociado al arreglo de los datos de una entidad y lo devuelve como un arreglo asociativo
 * 
 * @param array $arr_ctx: context como arreglo asociativo  
 * @param array $data_arr arreglo asociativo de los datos de una entidad  
 *  
 * @return array Arreglo asociativo del contexto asociado a la entidad
 */
function getDataContext($data_arr, $arr_ctx){

  if(count($data_arr)){
    $data_types = getObjTypes($data_arr);
    $data_keys = array_keys_recursive($data_arr, array('schema', 'lilacs', 'meshv'));
    $data_ctx_keys = array_fill_keys(array_merge($data_types, $data_keys), 1);
    $data_ctx = array_intersect_key($arr_ctx['@context'], $data_ctx_keys);
  }
  else
    $data_ctx = NULL;

  return $data_ctx;
}

/**
 * Devuelve los valores de la propiedad @type en un arreglo asociativo, que es recorrido en todos sus niveles (metodo recursivo)
 * 
 * @param array $obj arreglo asociativo de los datos de una entidad  
 * @param array $types arreglo donde se almacenan los diferentes @type encontrados  
 *  
 * @return array Arreglo de los diferentes @type encontrados en obj 
 */
function getObjTypes($obj, $types = array()){
  foreach($obj as $key=>$value){
    if($key=='@type'){
      if(!is_array($value)){
        if(!in_array($value,$types))
          $types[]=$value;  
      }  
      else{
        foreach($value as $vi){
          if(!in_array($vi,$types))
            $types[]=$vi;  
        }  
      }
    }
    else{
      if(is_array($value)){
        if(is_assoc_array($value)){ //un objeto
          $types = getObjTypes($value, $types);  
        }
        else{ //arreglo indice numerico
          foreach($value as $vi){
            if(is_array($vi) AND is_assoc_array($vi) ){
              $types = getObjTypes($vi, $types);  
            }
            //puede haber dos niveles de arreglos no asociativos [[]] o siempre es [{}]   ?
          } 
        }
      }  
    }  
  }
  return $types;  
}
  
/* Recorre todos los niveles del arreglo (data) y devuelve un arreglo con los todas las llaves (no numericas) encontradas
*
* @param $data
*   array arreglo del que se desean obtener todas sus llaves
* @param $data_keys
*   array arreglo donde se almacenan recursivamente las llaves
*
* @return 
*   arreglo ($data_keys) con todas las llaves de data
*/
function array_keys_recursive($data, $data_keys=array()){
  $is_assoc = is_assoc_array($data);
  foreach($data as $k=>$v){
    if($is_assoc){
      if(!in_array($k, $data_keys)){
        $data_keys[] = $k;
      }    
      if($k=='@type'){
        if(!is_array($v)){
          if(!in_array($v, $data_keys)){
            $data_keys[] = $v;             
          }    
        }
        else{
          foreach($v as $v1){
            if(!in_array($v1, $data_keys)){
              $data_keys[] = $v1;             
            }    
          }  
        } 
      }   
    }  
    if(is_array($v)){
      $data_keys = array_keys_recursive($v, $data_keys);  
    }
  }
  return $data_keys;
}
/**
 * Actualiza todos los context de una tabla a partir de propiedades existentes en campo data
 * 
 * Se utiliza solo en dataContext.php
 * 
 * @param string $table nombre de la tabla 
 *  
 * @return array Arreglo con 'total': ctdad de registros de la tabla, 'upd': ctdad de registros actualizados
 */
function updateAllContextDB($table, $link){
  global $lilacs_ctx;
    
  $count = mysqli_fetch_row(mysqli_query($link, "SELECT count(id) FROM $table"));
  $ctdad = intval($count[0]/1000);
  $upd=0;
  for($i=0;$i<=$ctdad;$i++){
    $start = $i*1000;

    $sql = "SELECT id, data FROM $table LIMIT $start,1000";  
    $result = mysqli_query($link, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
      $data = json_decode($row['data'],1); 

      //agregar campos fecha para incluirlos en context (no estan en data)
      $data['created'] = 1;
      $data['modified'] = 1;
      if($table=='document'){
        $data['transfered'] = 1;
        $data['originalRecord'] = 1;
      }  
      $context_arr = getDataContext($data, $lilacs_ctx);
      $json_data_ctx = array_to_objStr(array('@context' => $context_arr), $link);

      $sql= "UPDATE $table SET context = JSON_OBJECT($json_data_ctx), modified=NOW() WHERE id=". $row['id']; 
      $result1 = mysqli_query($link, $sql);
      if ($result1) {
        $upd += 1;
      }
      //break;//para probar uno solo
    }
    mysqli_free_result($result);
  }  
  return array('total'=>$count[0], 'upd'=>$upd);
} 

?>
