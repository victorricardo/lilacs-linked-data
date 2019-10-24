<?php
//se comenta lo q sigue pq al llamar a estos metodod siempre se chequea usuario primero, q lo incluye 
//si se van a utilizar para la migracion descomentar!!
/*require_once("conexionDB.php");
if(!defined('PATH_COMMON_LD'))  
  require_once($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require_once(PATH_DECS_LD."common.inc");
require_once("validation.inc");*/

/**
 * obtiene un solo registro dado su identificador
 * 
 * @param string $table nombre de la tabla de donde obtener la entidad
 * @param string $identifier identificador unico de entidad 
 * @param string $fields campos especificos a retornar, separados por coma. Si no se especifican se retornan todos los campos
 * 
 * @return array ('data': arreglo asociativo con la entidad obtenida de la base de datos) o
 *               ('err': nro error http, 'msg':mensaje del error)  
 */
function getEntityDB($table, $identifier, $fields = '', $allowed_prop){
  global $link_decs, $decs_ctx;
                        
  //validacion de parametros
  $res = validateIdentifier($identifier, $table);
  if($res!='OK'){
    return array('err'=>400, 'msg'=>$res); 
  }

  $entity = '';

  //se valida fields y se construye string con campos del select
  $select = prepareFields($table, $fields, $allowed_prop);
  if(!$select){
    return array('err'=>400, 'msg'=>"The fields ($fields) are not valid properties."); 
  }
  $sql = "SELECT $select FROM $table WHERE identifier='".$identifier."'";  
  //echo json_encode($sql); exit;
                                                                                                    
  $result = mysqli_query($link_decs, $sql);          
  if($result AND mysqli_num_rows($result)){ 
    $row = mysqli_fetch_assoc($result);
    $ctxSelected = (strpos($fields,'context')!==false OR strpos($fields,'data')!==false OR $fields=='' OR $fields=='*') ? 1 : 0;  
    $data_arr = json_decode($row['data'],1);  
    if($ctxSelected){  
      $context_arr = getDataContext($data_arr, $decs_ctx);
      $entity = array_merge(array('@context'=>$context_arr), $data_arr);
    }
    else{
      $entity = $data_arr;
    }

    mysqli_free_result($result);
  }
  return array('data'=>$entity);                
}            
/**
 * obtiene todos los registros de la tabla especificada
 * 
 * @param string $table (obligatorio) nombre de la tabla de donde obtener los registros
 * @param array $allowed_prop (obligatorio) arreglo de nombres de las propiedades del modelo de datos (a partir de context)
 * @param array $params (opcional) arreglo asociativo (campo=>valor) de campos especificos donde buscar un valor (va al where)
 * @param string $q (opcional) valor a buscar en cualquier campo texto (va al where)
 * @param string $fields (opcional) campos especificos a retornar, separados por coma
 * @param string $where (opcional) condicion del WHERE de la consulta, para condiciones + complejas q $params y $q
 * @param string $sort_by (opcional) campo por el que se ordenan los resultados
 * @param string $sort_order (opcional) tipo de orden ASC (por defecto) o DESC
 * @param int $page_size (opcional) cantidad de registros a devolver, 10 por defecto 
 * @param int $page (opcional) numero de pagina a recuperar por defecto la primera (1)
 * 
 * @return array (list: arreglo asociativo con los registros obtenidos de la base de datos, 
 *                total: total de registros encontrados, page_size, page) o
 *               ('err': nro error http, 'msg':mensaje del error)  
 */
function getEntitysDB($table, $allowed_prop, $params=array(), $q='', $fields='', $where='', $sort_by='', $sort_order='', $page_size=10,$page=1){
  global $link_decs, $decs_ctx, $decs_objectsArray_prop;

  $entitys = NULL;
  
  //se valida fields y se construye string con campos del select
  $select = prepareFields($table, $fields, $allowed_prop, $decs_objectsArray_prop);
  if(!$select){
    return array('err'=>400, 'msg'=>"The fields ($fields) are not valid properties."); 
  }

  if($where)
    $where = "(" .$where .") AND ";
  
  if($params){
    //q los campos por los q se busca existan en ctx, construye prop array con [*]
    $params_keys=validateAllowed(array_keys($params), $allowed_prop, $decs_objectsArray_prop);

    //Permitir campos de data y fechas de registro
    if(count($params_keys)){
      foreach($params_keys as $key){//solo campos q estan en ctx
        if(strpos($key,'[*]')!==false){
          //si tiene [*] (array de obj con subprop), se quita para obtener value
          $key0 = str_replace('[*]','',$key);
          $value = $params[$key0]; 
        }
        else
          $value = $params[$key]; 
        
        if(!is_integer($value))
          $value = "'%". $value ."%'";//en cualquier parte del texto
          
        switch($key){
          case 'created':
          case 'modified':
          case 'meshUpdated':
          case 'decsUpdated':
            //validar q es fecha o parte de fecha
            if(strtotime($value)!==false)
              $where_param[]= "$key LIKE $value";   
            break;
          default:
            if($key == '@type'){
              $res = validateType($params[$key], $table);      
              if($res != 'OK'){
                return array('err'=>400, 'msg'=>$res); 
              }
            }   
            $dataField = field2dataField($key);
            //$value es param de json_search, no es necesario validar por sql injection?
            $where_param[]= "JSON_SEARCH(data->'$dataField', 'one', $value) IS NOT NULL";   
            break;
        }
      }  
      if(isset($where_param))
        $where .= implode(" AND ", $where_param);
    }
  }
  
  if($q){
    if($where)
      $where .= " AND ";
    //$q es param de json_search, no es necesario validar por sql injection?
    $where .= "JSON_SEARCH(data, 'one', '%$q%') IS NOT NULL";//busca $q en cualquier parte de cualquier prop de data
  }
  if($where){
    $where = "WHERE ". $where;  
  }
  
  //Permite campos de data y los de fecha
  $sort_by_str = '';
  if($sort_by AND validateAllowed(array($sort_by), $allowed_prop)){//si sort_by no es vacio y existe en ctx
    switch($sort_by){
      case 'created':
      case 'modified':
      case 'meshUpdated':
      case 'decsUpdated':
        $sort_by_str = "ORDER BY $sort_by";  
        break;
      default:   
        $dataField = field2dataField($sort_by);
        $sort_by_str = "ORDER BY data->'$dataField'";  
    }  
    if($sort_by_str AND $sort_order AND strtolower($sort_order)=='desc')
      $sort_by_str .= " $sort_order";  
  }

  $page = validateNatural($page) ? $page : 1;
  $page_size = validateNatural($page_size) ? $page_size : 10;
  $start = ($page-1)*$page_size;  
  $limit = "LIMIT $start, $page_size";

  //total
  //echo json_encode("SELECT count(id) FROM $table $where"); exit;
  $count = mysqli_fetch_row(mysqli_query($link_decs, "SELECT count(id) FROM $table $where"));
  if($count!==false)
    $total = $count[0];
  else
    $total = 0;  

  if($total){  
    //entitys
    $sql = "SELECT $select FROM $table $where $sort_by_str $limit";  
    //return array('err'=>409, 'msg'=>$sql);                
    $result = mysqli_query($link_decs, $sql);          
    if($result){
      $ctxSelected = (strpos($fields,'context')!==false OR strpos($fields,'data')!==false OR $fields=='' OR $fields=='*') ? 1: 0;        
      while ($row = mysqli_fetch_assoc($result)) {
        $data_arr = json_decode($row['data'],1);  
        if($ctxSelected){  
          $context_arr = getDataContext($data_arr, $decs_ctx);
          $arr_entitys[] = array_merge(array('@context'=>$context_arr), $data_arr);
        }
        else{
          $arr_entitys[] = $data_arr;
        }
      }
      if(isset($arr_entitys)){
        //PENDIENTE: devolver links de paginacion: 'first', 'last', 'next' and 'prev' , 
        //valorar pq hay q agregar a las uris de paginacion todos los param (p, param, fields, ...)

        //resultado con data y paginacion
        $entitys=array("total"=>$total, "page_size"=>$page_size, "page"=>$page, "list"=>$arr_entitys);
      }
      mysqli_free_result($result);
    }
  }
  return array('data'=>$entitys);                
}
//PENDIENTE: Generar identifier si no existe en data?, solo en caso de que se agreguen directamente
//los propios de decs, los de mesh vienen con todo y los q se migran de decs too
/**
 * Añade un nuevo registro en la tabla del tipo de entidad
 * 
 * Valida JSON del parametro data, 
 * Valida identifier: q sea correcto y q no exista, 
 * Valida la existencia de propiedades obligatorias:
 *  -@id o identifier
 *  -label
 *  -@type
 * 
 * @param string $table nombre de la tabla donde insertar el registro
 * @param String $data JSON con los datos de la entidad a insertar
 * 
 * @return int id del recurso insertado o 0 en caso de error en la consulta, o
 *         string Mensaje de error de validacion del json
 */
function insertEntityDB($table, $data='', $allowed_prop){
  global $link_decs;
   
  //validar JSON de data 
  $data_arr = validateJson($data, $link_decs);
  if(!$data_arr) {
    $err_msg = "The parameter 'data' is not a valid JSON";
  }
  else{
    //validar datos obligatorios 
    $data_keys = array_keys($data_arr);
    $identifier_in = in_array('identifier', $data_keys);
    $id_in = in_array('@id', $data_keys);
    if( (!$identifier_in AND !$id_in) OR !in_array('@type', $data_keys) OR !in_array('label', $data_keys)){
      $err_msg = "To create a $table, this properties are required: label, @type, (identifier OR @id). ";
    }
    elseif(!$identifier_in AND $id_in){
      $data_arr['identifier'] = substr($data_arr['@id'], strlen(URL_DECS_LD. $table ."s/"));  
    }
    elseif($identifier_in AND !$id_in){
      $data_arr['@id'] = URL_DECS_LD. $table ."s/". $data_arr['identifier'];  
    }

    $res = validateIdentifier($data_arr['identifier'], $table);
    if($res!='OK'){
      $err_msg .= $res;
    }
    else{
      if(existIdentifierDB($table, $data_arr['identifier'])){
        //chequear q no existe ese identifier
        $err_msg .= "Already exists $table with identifier: ". $data_arr['identifier'];
      } 
    }
    if(!validateUri($data_arr['@id'], $table, $data_arr['identifier'])){
      $err_msg .= "The value of '@id' is wrong, must be like this: ".URL_DECS_LD. $table ."s/identifier";
    }  
    $res = validateType($data_arr['@type'], $table);      
    if($res != 'OK'){
      $err_msg .= $res;
    }
  }
  
  if(!isset($err_msg)){
    //se eliminan las propiedades q no estan en ctx
    $data_keys = validateAllowed(array_keys($data_arr), $allowed_prop);
    $data_arr = array_intersect_key($data_arr, array_flip($data_keys));
    
    //obtener data preparado para pasar por JSON_OBJECT
    $data_str = array_to_objStr($data_arr, $link_decs, 0, 0);

    $r = mysqli_query($link_decs, "INSERT INTO $table SET data = JSON_OBJECT($data_str), created = NOW()");
    if($r){
      //retornar uri de la entidad creada ($id)
      return array('data'=>array('Location'=>$data['@id']));        
    }
    else
      return array('data'=>NULL); //error al insertar

  } 
  else{
    return array('err'=>400, 'msg'=>$err_msg);  
  }
}
/**
 * Actualiza registro dado su identifier y data, en la tabla especificada. 
 * Actualiza las propiedades que existen en table.data y $data, e inserta las q solo existen en $data. 
 * 
 * @param string $table nombre de la tabla de donde actualizar el registro
 * @param string $identifier Identificador unico de entidad
 * @param json $data JSON con propiedades a actualizar en data
 * @param array $allowed_prop arreglo con los nombres de las propiedades definidas en context
 * 
 * @return boolean Resultado de la consulta de actualizacion o
 *         string Mensaje de error de validacion del json
 */
function updateEntityDB($table, $identifier, $data, $allowed_prop=array()) {
  global $link_decs;

  $err_msg = '';
  //validacion de parametros
  $res = validateIdentifier($identifier, $table);
  if($res!='OK'){
    $err_msg = $res;
  }
  //validar JSON de data 
  $data_arr = validateJson($data, $link_decs);
  if(!$data_arr) {
    return array('err'=>400, 'msg'=> "The parameter 'data' is not a valid JSON. ");
  }
  if(isset($data_arr[@type])){
    $res = validateType($data_arr['@type'], $table);      
    if($res != 'OK'){
      $err_msg .= $res;
    }
  }
  $data_keys = validateAllowed(array_keys($data_arr), $allowed_prop);
  if(!count($data_keys))
    $err_msg .= "The properties to update don't exist in the domain.";
  else
    //se dejan solo las propiedades permitidas
    $data_arr = array_intersect_key($data_arr, array_flip($data_keys));
  
  if(!$err_msg){
    if(existIdentifierDB($table, $identifier)){
      $r = _updateEntityDB($table, $identifier, $data_arr, 1);  
      return array('data'=>$r); 
    }   
    else{
      return array('err'=>404, 'msg'=>'');//no encontrado
    }
  }
  return array('err'=>400, 'msg'=> $err_msg);  
}
function _updateEntityDB($table, $identifier, $data_arr, $path = 0){
  global $link_decs;
  
  //Asegurarse de que tengan los valores correctos, no dejar sobrescribir
  $data_arr['@id'] = URL_DECS_LD.$table."s/".$identifier;
  $data_arr['identifier'] = $identifier;

  //data preparado para pasar por JSON_OBJECT (path:0) o JSON_SET (path:1, con '$.' delante) 
  $data_str = array_to_objStr($data_arr, $link_decs, 0, $path);

  if($path) //ya existe data, se utiliza JSON_SET para actualizar prop existentes e incluir las nuevas
    $sql = "UPDATE $table SET data = JSON_SET(data, $data_str), modified=NOW() WHERE identifier ='".  $identifier."'";
  else //data y context se reemplazan, se utiliza JSON_OBJECT para asignarles valor
    $sql = "UPDATE $table SET data = JSON_OBJECT($data_str), modified=NOW() WHERE identifier ='".  $identifier."'";
  
  $r = mysqli_query($link_decs, $sql);
  return $r;
}
/**
 * elimina un registro dado el identificador y la tabla 
 * 
 * @param string $table nombre de la tabla donde eliminar el registro
 * @param string $identifier Identificador unico de registro
 * 
 * @return Bool TRUE|FALSE
 */
function deleteEntityDB($table, $identifier) {
  global $link_decs;

  //validacion de parametros
  $res = validateIdentifier($identifier, $table);
  if($res!='OK'){
    return array("err"=>400, "msg"=>$res); 
  }

  //Chequear q no se usa en otras entidades
  if(checkIdentifierIsUsed($table, $identifier)){
    return array("err"=>409, "msg"=>"The entity is in use, can't be deleted.");
  }

  $r = mysqli_query($link_decs, "DELETE FROM $table WHERE identifier = '". $identifier ."'");
  return array('data'=>$r);
}

function prepareFields($entity, $fields, $allowed_prop, $objectsArray_prop=array()){
    
  if($fields AND $fields != '*'){
    $fields=str_replace(' ', '', $fields); //quitar espacios 
    $fields_arr = explode(',',$fields);

    $dataIn = in_array('data', $fields_arr);

    //elimina los fields q no estan definidos en ctx, (se elimina 'data', pero si existia se sabe por $dataIn)
    $fields_arr = validateAllowed($fields_arr, $allowed_prop, $objectsArray_prop);
//echo json_encode($fields_arr); exit;    
  }
  else{//todo
    $dataIn = 1;  //campo data completo
    //adicionar estos campos a data
    $fields_arr = array('id','created', 'modified', 'meshUpdated', 'decsUpdated');
  }
  
  $fields_str = "";
  foreach($fields_arr as $field){
    $field_key = $dataIn ? field2dataField($field) : $field; //si esta data e/ los campos hay q poner el path ($)
    switch($field){
      case 'id':
      case 'created':
      case 'modified':
      case 'meshUpdated':
      case 'decsUpdated':
        $fields_str .= "'$field_key', $field,";  
        break;  
      case '@context':
        break;  
      default:    
        $ctxF = strpos($field, '@context.');//se ignoran, no se permiten en fields
        if($ctxF === false){
          if(!$dataIn){
            $dataField = field2dataField($field);
            $fields_str .= "'$field',data->'$dataField',";  
          }  
        }        
        break;
    }  
  }
  
  if($dataIn){
    if($fields_str){
      $fields_str = substr($fields_str,0,-1); //quitar la ultima ,
      $select =  "JSON_INSERT(data, $fields_str) AS data"; //se agregan al json data los otros campos de la tabla
    }  
    else
      $select =  "data"; 
  }
  else{
    if($fields_str){
      $fields_str = substr($fields_str,0,-1); //quitar la ultima ,
      $select =  "JSON_OBJECT($fields_str) AS data"; 
    }
    else
      $select = '';
  }
  
  return $select;
}
/**
 * Valida que los identificadores de entidades sean correctos
 * 
 * Formato:
 * "X######" o "_X######" o "X#########" -> Descriptors (X=D), Qualifiers (X=Q), Terms (X=T), SupplementaryConceptRecord (X=C)
 * "X#######" o "_X#######" o "X#########" -> Concepts (X=M)
 * "D######Q######","_D######Q######","D######_Q######",..-> DescriptorQualifierPairs combinaciones DescriptorQualifier 
 * XX#(.###)*  -> TreeNumber 
 * 
 * @param string $identifier identificador de la entidad. 
 * @param string $type nombre del tipo de entidad 
 * 
 * @return boolean: true si el identificador es correcto.
 */
function validateIdentifier($identifier, $type){
  
  switch($type){
    case 'Descriptor':
    case 'Qualifier':
    case 'Term':
      $t_prefix = $type[0];
      if (!preg_match("/(^". $t_prefix ."([0-9]{6}|[0-9]{9})$)|(^_". $t_prefix ."([0-9]{6})$)/", $identifier)) {
        $err = "The identifier is not valid: most start with $t_prefix and then 6 or 9 digits; or most start with _$t_prefix and then 6 digits (ex: $t_prefix"."000111, $t_prefix"."000111222, _$t_prefix"."000111). "; 
      }
      break;
    case 'SupplementaryConceptRecord':
      if (!preg_match("/^C([0-9]{6}|[0-9]{9})$/", $identifier)) {
        $err = "The identifier is not valid: most start with C and then 6 o 9 digits (ex: C000111, C000111222). "; 
      }
      break;
    case 'Concept':
      if (!preg_match("/(^M([0-9]{7}|[0-9]{9})$)|(^_M([0-9]{7})$)/", $identifier)) {
        $err = "The identifier is not valid: most start with M and then 7 o 9 digits; or most start with _M and then 7 digits (ex: M0001112, M000111222, _M0001112). "; 
      }
      break;
    case 'DescriptorQualifierPair':
      if (!preg_match("/(^D([0-9]{6}|[0-9]{9})|^_D([0-9]{6}))(Q([0-9]{6}$|[0-9]{9}$)|_Q([0-9]{6}$))/", $identifier)) {
        $err = "The identifier is not valid: most be the union of descriptor and qualifier identifiers (ex: D000111Q000111, _D000111Q000111222, _D000111_Q000111). "; 
      }
      break;
    case 'TreeNumber':
      if (!preg_match("/^([A-Z].{0,1}[1-9])([.][0-9]{3})*$//", $identifier)) {
        $err = "The identifier is not valid: most start with X# o XX# and then cero or more .### (ex:VS1.001.030.020). "; 
      }
      break;
    default:
      $err = "Wrong type of entity. ";
      break; 
  } 
  if(isset($err))
    return $err;
  else
    return 'OK';   
}
/**
 * verifica si una entidad se utiliza en otras entidadea, buscando su identificador en las tablas de todos los tipos de entidad
 * @param string $table nombre de la tabla 
 * @param string $identifier Identificador unico de registro
 * @return Bool TRUE|FALSE
 */
function checkIdentfierIsUsed($table, $identifier){
  global $link_decs;

  $s_identifier = "/$identifier";//para diferenciar D000001 y _D000001
  $entidades = array('Concept', 'Descriptor', 'DescriptorQualifierPair', 'Qualifier', 'Term', 'TreeNumber','SupplementaryConceptRecord');
  foreach($entidades as $entidad){
    if($entidad != $table){  
      $where = "JSON_SEARCH(data, 'one', '$s_identifier') IS NOT NULL";  
      //$sql = "SELECT count(id) FROM $entidad WHERE JSON_SEARCH(data, 'one', '$uri') IS NOT NULL"; 
    }
    else{
      $where = "JSON_SEARCH(data, 'one', '$s_identifier') IS NOT NULL AND identifier != '".$identifier ."'";  
      //$sql = "SELECT count(id) FROM $table WHERE JSON_SEARCH(data, 'one', '$uri') IS NOT NULL AND id != $id"; 
    }    
    $count = mysqli_fetch_row(mysqli_query($link_decs, "SELECT count(id) FROM $table $where"));
    if($count!==false)
      $total = $count[0];
    else
      $total = 0;  

    if($total)
      return true;
  }
  
  return false;
}
/**
 * verifica si un 'identifier' existe
 * @param string $table nombre de la tabla 
 * @param string $identifier Identificador unico de registro
 * @return Bool TRUE|FALSE
 */
function existIdentifierDB($table, $identifier){
  global $link_decs;

  $r = mysqli_query($link_decs, "SELECT id FROM $table WHERE identifier='" .$identifier ."'");
  if($r AND mysqli_num_rows($r) == 1){                
    return true;
  }        
  return false;
}

/**
 * Valida una uri a partir del nombre del tipo de la entidad y el identificador. Valida q tenga la url 
 * de decs y q el identificador en la uri este correcto
 * 
 * @param string $table nombre del tipo de entidad 
 * @param string $identifier Identificador unico de registro
 * @return Bool TRUE|FALSE
 */
function validateUri($uri, $table, $identifier){

 $parts = explode(URL_DECS_LD. $table ."s/", $uri);   

 if(count($parts)==1 OR $parts[0] != "" OR $parts[1] != $identifier)
   return false; 
 else
   return true; 
}
function validateType($type, $table){
  switch($table){
    case 'Descriptor':
      $types = array('TopicalDescriptor', 'PublicationType', 'CheckTag', 'GeographicalDescriptor');
      break;  
    case 'DescriptorQualifierPair':
      $types = array('AllowedDescriptorQualifierPair', 'DisallowedDescriptorQualifierPair');
      break;  
    case 'SupplementaryConceptRecord':
      $types = array('SCR_Chemical', 'SCR_Disease', 'SCR_Protocol');
      break;  
    case 'Qualifier':
    case 'Concept':
    case 'Term':
    case 'TreeNumber':
      $types = array($table);
      break;  
  } 

  $ret = 'OK';
  if(!isset($types))
    $ret = "Wrong type of entity"; //tipo entidad en la uri
  elseif((is_string($type) AND !in_array($type, $types)) OR 
         (is_array($type) AND array_intersect($type, $types) != $type))
    $ret = "Wrong @type for a $table, valid values: ". implode(",", $types);    

  return $ret;  
}
?>
