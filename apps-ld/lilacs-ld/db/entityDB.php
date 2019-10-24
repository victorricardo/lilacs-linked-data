<?php
require_once("commonDB.php");

//PENDIENTE: Insertar validar si entidad existe (document por name, @type, typeOfLiterature, LevelOfTreatment, author, otros por name, ...? )
//PENDIENTE: Validar campos q NO se pueden modificar
//PENDIENTE: Validacion de parametros, rectificar errores y ejecutar consultas; o devolver errores y salir? 

/**
 * obtiene un solo registro dado su ID
 * 
 * @param string $table nombre de la tabla de donde obtener la entidad
 * @param int $id identificador unico de registro
 * @param string $fields campos especificos a retornar, separados por coma. Si no se especifican se retornan todos los campos
 * 
 * @return array ('data': arreglo asociativo con la entidad obtenida de la base de datos) o
 *               ('err': nro error http, 'msg':mensaje del error)  
 */
function getEntityDB($table, $id=0, $fields = '', $allowed_prop){
  global $link;
                        
  //validacion de parametros
  if(!validateNatural($id)){
    return array('err'=>400, 'msg'=>"Error en parametro: id debe ser entero sin signo"); 
  }

  //se valida fields y se construye string con campos del select
  $select = prepareFields($table, $fields, $allowed_prop);
  if(!$select){
    return array('err'=>400, 'msg'=>"The fields ($fields) are not valid properties."); 
  }
  
  $sql = "SELECT $select FROM $table WHERE id=$id";  
  //echo json_encode($sql); exit;
                                                                                                    
  $entity = '';
  $result = mysqli_query($link, $sql);          
  if($result AND mysqli_num_rows($result)){ 
    $row = mysqli_fetch_assoc($result);
    $ctxSelected = strpos($fields, 'context')!==false ? 1 : 0; 
    $entity = getRowData($row, $ctxSelected); 

    mysqli_free_result($result);
  }
  return array('data'=>$entity);                
}            
/**
 * obtiene todos los registros de la tabla especificada
 * 
 * @param string $table nombre de la tabla de donde obtener los registros
 * @param array $params arreglo asociativo (nombreCampo => valor) de campos especificos donde buscar un valor
 * @param string $q valor a buscar en cualquier campo texto
 * @param string $fields campos especificos a retornar, separados por coma
 * @param string $sort_by campo por el que se ordenan los resultados
 * @param string $sort_order tipo de orden ASC (por defecto) o DESC
 * @param int $page_size cantidad de registros a devolver, 10 por defecto 
 * @param int $page numero de pagina a recuperar por defecto la primera (1)
 * 
 * @return array (list: arreglo asociativo con los registros obtenidos de la base de datos, 
 *                total: total de registros encontrados, page_size, page) o
 *               ('err': nro error http, 'msg':mensaje del error)  
 */
function getEntitysDB($table, $params=array(), $q='', $fields='', $sort_by='', $sort_order='', $page_size=10, $page=1, $allowed_prop){       
  global $link;

  //se valida fields y se construye string con campos del select
  $select = prepareFields($table, $fields, $allowed_prop);
  if(!$select){
    return array('err'=>400, 'msg'=>"The fields ($fields) are not valid properties."); 
  }

  $where = "";

  if($params){
    //q los campos por los q se busca existan en ctx
    $params_keys=validateAllowed(array_keys($params), $allowed_prop);

    //Permitir campos de data y fechas de registro, no buscar en originalRecord, ni @context
    if(count($params_keys)){
      foreach($params_keys as $key){//solo campos q estan en ctx
        $value = $params[$key]; 
        if(!is_integer($value))
          $value = "'%". $value ."%'";
          
        switch($key){
          case 'originalRecord':
          case '@context':
            //informar q no se puede filtrar por estos campos?
            break;
          case 'transfered':
            if($table=='document'){
              //validar q es fecha o parte de fecha
              if(strtotime($value)!==false)
                $where_param[]= "$key LIKE $value";   
              }  
            break;
          case 'created':
          case 'modified':
            //validar q es fecha o parte de fecha
            if(strtotime($value)!==false)
              $where_param[]= "$key LIKE $value";   
            break;
          default:   
            $dataField = field2dataField($key);
            //$value es param de json_search, no es necesario validar por sql injection?
            $where_param[]= "JSON_SEARCH(data->'$dataField', 'one', $value) IS NOT NULL";   
            break;
        }
      }  
      if(isset($where_param))
        $where = implode(" AND ", $where_param);
    }    
  }
  
  if($q){
    if($where)
      $where .= " AND ";
    //$q es param de json_search, no es necesario validar por sql injection?
    $where .= "JSON_SEARCH(data, 'one', '%$q%') IS NOT NULL";//busca $q en cualquier parte de cualquier prop de data
  }
  
  //Permite campos de data y los de fecha
  $sort_by_str = '';
  if($sort_by AND validateAllowed(array($sort_by), $allowed_prop)){//si sort_by no es vacio y existe en ctx
    switch($sort_by){
      case 'originalRecord':
      case '@context':
        //informar q no se puede ordenar por estos campos?
        break;
      case 'transfered':
        if($table=='document'){
          $sort_by_str = "ORDER BY $sort_by";  
        }
        break;
      case 'created':
      case 'modified':
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

  $entitys = NULL;
  //total
  $total = getCount($link, $table, $where);
  if($total){  
    if($where){
      $where = "WHERE ". $where;  
    }
    //entitys
    $sql = "SELECT $select FROM $table $where $sort_by_str $limit";  
    //return array('err'=>409, 'msg'=>$sql);                
    $result = mysqli_query($link, $sql);          
    if($result){
      $ctxSelected = strpos($fields, 'context')!==false ? 1 : 0; 
      while ($row = mysqli_fetch_assoc($result)) {
        $arr_entitys[] = getRowData($row, $ctxSelected); 
      }
      if(isset($arr_entitys)){
        //PENDIENTE: devolver links de paginacion: 'first', 'last', 'next' and 'prev' , 
        //valorar pq hay q agregar a las uris de paginacion todos los param (p, param, fields, ...)
        //"pagination"= array("first"=>"http://lilacs.sld.cu/entity/entitys?page=1&page_size=30”, "last"=>"http://lilacs.sld.cu/entity/entitys?page=25&page_size=30”, "prev"=>"http://lilacs.sld.cu/entity/entitys?page=7&page_size=30”, "next"=>"http://lilacs.sld.cu/entity/entitys?page=9&page_size=30”);
        //resultado con data y paginacion
        $entitys=array("total"=>$total, "page_size"=>$page_size, "page"=>$page, "list"=>$arr_entitys);
      }
      mysqli_free_result($result);
    }
  }
  return array('data'=>$entitys);                
}

/**
 * Añade un nuevo registro en la tabla del tipo de entidad
 * 
 * Valida JSON del parametro data, 
 * Valida la existencia de propiedades obligatorias:
 * en document
 *  -name
 *  -author
 *  -inLanguage
 *  -about
 *  -primarySubject
 *  -@type
 *  -typeOfLiterature
 *  -levelOfTreatment
 *  -provider
 *  -database
 * en otras entidades:
 *  -name
 *  -@type
 * 
 * @param string $table nombre de la tabla donde insertar el registro
 * @param String $data JSON con los datos de la entidad a insertar
 * 
 * @return int id del recurso insertado o 0 en caso de error en la consulta, o
 *         string Mensaje de error de validacion del json
 */
function insertEntityDB($table, $data='', $allowed_prop){
  global $link, $lilacs_ctx;
   
  //validar JSON de data 
  $data_arr = validateJson($data, $link);
  if(!$data_arr) {
    $err_msg = "The parameter data is not a valid JSON";
  }
  else{
    $data_type = "$.\"@type\"";  //escapando @
    if($table == 'document')
      $required = "'$.name', '$.author', '$.inLanguage', '$.about', '$.primarySubject', '$data_type', '$.typeOfLiterature', '$.levelOfTreatment', '$.provider', '$.database'";
    else
      $required = "'$.name', '$data_type'";
    
    //validar datos obligatorios 
    if( !validateRequired($data, $required, $link) ){
      $required = str_replace('$.', '', $required);
      $err_msg = "To create a $table, this properties are required: $required ";
    }
  }
  
  //chequear q no existe, por nombre doc , nomb autor??
  
  if(!isset($err_msg)){
    //se eliminan las propiedades q no estan en ctx
    $data_keys = validateAllowed(array_keys($data_arr), $allowed_prop);
    $data_arr = array_intersect_key($data_arr, array_flip($data_keys));
    
    //obtener data preparado para pasar por JSON_OBJECT
    $data_str = array_to_objStr($data_arr, $link, 0, 0);

    //obtener context preparado para pasar por JSON_OBJECT, contiene solo las prop permitidas en data  
    $data_arr['created'] = 1;//agregar este campo q no es de data a context
    $context_arr = getDataContext($data_arr, $lilacs_ctx);
    $context_str = array_to_objStr(array('@context' => $context_arr), $link);

    $r = mysqli_query($link, "INSERT INTO $table SET data = JSON_OBJECT($data_str), context = JSON_OBJECT($context_str), created = NOW()");
    if($r){
      //actualizar @id y datos enlazados en otras propiedades?  
      $id = mysqli_insert_id($link);
      /*list($u, $e) = updateEntityLDbyId($table, $data_arr, $id, $link);  
      //actualizar datos enlazados en propiedades que apuntan al DeCS ?
      list($u, $e) = setDecsUrisById($data_arr, $id, $link);
      */
      //retornar uri de la entidad creada ($id)
      return array('data'=>array('Location'=>URL_LILACS_LD.$table."s/$id"));        
    }
    else
      return array('data'=>NULL); //error al insertar

  } 
  else{
    return array('err'=>400, 'msg'=>$err_msg);  
  }
}

/**
 * Actualiza registro dado su ID y data en la tabla especificada. 
 * Actualiza las propiedades que existen en table.data y $data, e inserta las q solo existen en $data. 
 * Actualiza context
 * 
 * @param string $table nombre de la tabla de donde actualizar el registro
 * @param int $id Identificador unico de registro
 * @param json $data JSON con propiedades a actualizar en data
 * @param array $allowed_prop arreglo con los nombres de las propiedades definidas en context
 * @param boolean $updLD 1 chequear newData para asignar datos enlazados a entidades LILACS y propiedades de DeCS 
 *                       0 no actualizar datos enlazados
 * 
 * @return boolean Resultado de la consulta de actualizacion o
 *         string Mensaje de error de validacion del json
 */
function updateEntityDB($table, $id, $data, $allowed_prop=array(), $updLD = 0) {
  global $link;

  $err_msg = '';
  //validacion de parametros
  if(!validateNatural($id)){
    $err_msg = "Error en parametro: id debe ser entero sin signo. ";
  }
  //validar JSON de data 
  $data_arr = validateJson($data, $link);
  if(!$data_arr) {
    $err_msg .= "El parametro data no es un JSON valido. ";
  }
  
  $data_keys = validateAllowed(array_keys($data_arr), $allowed_prop);
  if(!count($data_keys))
    $err_msg .= "Las propiedades a actualizar no existen en el dominio.";
  else
    //se dejan solo las propiedades permitidas
    $data_arr = array_intersect_key($data_arr, array_flip($data_keys));
  
  if(!$err_msg){
    if(checkEntityIdDB($table, $id)){
      $r = _updateEntityDB($table, $id, $data_arr, 1);  
      //if($r AND $updLD){  
        //actualizar datos enlazados ?
        //list($u, $e) = updateEntityLDbyId("document", $data_arr, $id, $link);  
        //actualizar datos enlazados en propiedades que apuntan al DeCS  ?
        //list($u, $e) = setDecsUrisById($data_arr, $id, $link);
      //}
      return array('data'=>$r); 
    }   
    else{
      return array('err'=>404, 'msg'=>'');//no encontrado
    }
  }
  return array('err'=>400, 'msg'=> $err_msg);  
}

/**
 * elimina un registro dado el ID y la tabla 
 * 
 * @param string $table nombre de la tabla donde eliminar el registro
 * @param int $id Identificador unico de registro
 * 
 * @return Bool TRUE|FALSE
 */
function deleteEntityDB($table, $id=0) {
  global $link;

  //validacion de parametros
  if(!validateNatural($id)){
    return array("err"=>400, "msg"=>"Error en parametro: id debe ser entero sin signo"); 
  }

  //Chequear q no se usa en otras entidades
  if(checkEntityIsUsed($table, $id)){
    return array("err"=>409, "msg"=>"Imposible eliminar, otros recursos se enlazan con el");
  }

  $r = mysqli_query($link, "DELETE FROM $table WHERE id = $id");
  return array('data'=>$r);
}

/**
 * añade un nuevo registro en la tabla document, con el campo originalRecord durante la importacion de datos modelo LILACS 
 * @param Array $originalRecord_arr array con los datos del registro en BD con modelo LILACS (campos v1, v2, ...)
 * @return boolean Resultado de la consulta de insercion 
 */
function insertOriginalRecordDB($originalRecord_arr, $link){

   $originalRecord_str = array_to_objStr($originalRecord_arr, $link); 
   
   $sql= "INSERT INTO Document (originalRecord, created, transfered) VALUES (JSON_OBJECT($originalRecord_str), NOW(), NOW())"; 
   $r = mysqli_query($link, $sql);

   return $r;
}
/**
 * Prepara los campos a retornar en consultas de seleccion. devuelve un string preparado para la 
 * clausula SELECT
 * 
 * Si entre los fields esta 'data' se agrega el campo 'context'.
 * 
 * @param string $entity nombre de la tabla a consultar
 * @param string $fields campos especificos a retornar, separados por coma. 
 * @param array $allowed_prop arreglo con los nombres de las propiedades definidas en context
 * 
 * @return string 'select': string formateado para el select o '' si los campos en fields no son 
 *                          validos
 */
function prepareFields($entity, $fields, $allowed_prop){
    
  if($fields AND $fields != '*'){
    $fields=str_replace(' ', '', $fields); //quitar espacios 
    $fields_arr = explode(',',$fields);
    $dataIn = in_array('data', $fields_arr);

    //elimina los fields q no estan definidos en ctx, (se elimina 'data', pero si existia se sabe por $dataIn)
    $fields_arr = validateAllowed($fields_arr, $allowed_prop);
//echo json_encode($fields_arr); exit;    
  }
  else{
    $dataIn = 1;  
    //adicionar estos campos a data
    $fields_arr = array('created', 'modified', 'originalRecord', 'transfered');
  }
  
  $fields_str = "";
  foreach($fields_arr as $field){
    $field_key = $dataIn ? field2dataField($field) : $field; //si esta data hay  poner el path ($)
    switch($field){
      case 'id':
      case 'created':
      case 'modified':
        $fields_str .= "'$field_key', $field,";  
        break;  
      case 'transfered':
      case 'originalRecord':
        if($entity == "document")
          $fields_str .= "'$field_key', $field,";  
        break;
      case '@context':
        break;  
      default:    
        $origRecF = strpos($field, 'originalRecord.');//se ignoran, no se permiten en fields
        $ctxF = strpos($field, '@context.');//se ignoran, no se permiten en fields
        if($origRecF === false AND $ctxF === false){
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
      $select =  "context, JSON_INSERT(data, $fields_str) AS data"; //se agregan al json data los otros campos de la tabla
    }  
    else
      $select =  "context, data"; 
  }
  else{
    if($fields_str){  
      $fields_str = substr($fields_str,0,-1); //quitar la ultima ,
      $select =  "JSON_OBJECT($fields_str) AS data"; 
    }
    else
      $select =  ""; 
  }
  return $select;
}

/**
 * Devuelve los datos de una entidad a partir de los resultados de una fila de la tabla de la entidad. Se utiliza en metodos get...
 * 
 * @param array $row: arreglo asociativo con resultados de una fila de una tabla con indice data y puede contener tambien context 
 * @param Bool $ctxSelected 1: si el context se incluye en la consulta, 0 en caso contrario
 * 
 * @return array: arreglo asociativo con los datos de una entidad 
 */
function getRowData($row, $ctxSelected){
  global $lilacs_ctx;
    
  $data_arr = json_decode($row['data'],1);  
  if(isset($row['context'])){
    $context_arr = json_decode($row['context'],1);  
    $ret_data = array_merge($context_arr, $data_arr);//ctx al inicio 
  }
  elseif(isset($row['data'])){
    if($ctxSelected){  
      $context_arr = getDataContext($data_arr, $lilacs_ctx);
      $ret_data = array_merge(array('@context'=>$context_arr), $data_arr);
    }
    else{
      $ret_data = $data_arr;
    }
  }
  return $ret_data;
}
/**
 * verifica si un ID existe
 * @param string $table nombre de la tabla 
 * @param int $id Identificador unico de registro
 * @return Bool TRUE|FALSE
 */
function checkEntityIdDB($table, $id){
  global $link;

  $r = mysqli_query($link, "SELECT id FROM $table WHERE id=$id");
  if($r AND mysqli_num_rows($r) == 1){                
    return true;
  }        
  return false;
}
/**
 * verifica si una entidad se utiliza en otras entidadea, buscando su uri en las tablas de todos los tipos de entidad
 * @param string $table nombre de la tabla 
 * @param int $id Identificador unico de registro
 * @return Bool TRUE|FALSE
 */
function checkEntityIsUsed($table, $id){
  global $link;

  $uri = URL_LILACS_LD.$table."s/$id";
  $entidades = array('document', 'person', 'organization', 'event', 'project');
  foreach($entidades as $entidad){
    if($entidad != $table){  
      $where = "JSON_SEARCH(data, 'one', '$uri') IS NOT NULL";  
      //$sql = "SELECT count(id) FROM $entidad WHERE JSON_SEARCH(data, 'one', '$uri') IS NOT NULL"; 
    }
    else{
      $where = "JSON_SEARCH(data, 'one', '$uri') IS NOT NULL AND id != $id";  
      //$sql = "SELECT count(id) FROM $table WHERE JSON_SEARCH(data, 'one', '$uri') IS NOT NULL AND id != $id"; 
    }    
    $count = getCount($link, $table, $where);
    if($count)
      return true;
  }
  
  return false;
}
?>
