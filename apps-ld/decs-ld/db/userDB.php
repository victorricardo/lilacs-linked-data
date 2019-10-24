<?php
require_once("conexionDB.php");
if(!defined('PATH_COMMON_LD'))  
  require_once($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require_once(PATH_DECS_LD."common.inc");
require_once(PATH_COMMON_LD."validation.inc");

//api_key no permitido en get ni update
$allowed_ufields=array('id','email','permisos','created','modified');

/**
 * obtiene api_key dado el email
 * 
 * @param string $email correo electronico del usuario
 * 
 * @return string: api_key del usuario buscado o
 *                false si no existe usuario 
 *                ocurrio error
 */
function getUserKeyDB($email){
  global $link_decs;
                        
  if(!valid_email_address($email))
    return array('err'=>400, 'msg'=>"Valor no valido: email");
  
  $sql = "SELECT api_key FROM user WHERE email='". $email . "'";  
  $result = mysqli_query($link_decs, $sql);          
  if($result AND mysqli_num_rows($result)){ 
    $row = mysqli_fetch_assoc($result);
    return $row;                
  }
  else
    return false;
}            

/**
 * obtiene email dado el api_key
 * 
 * @param string $api_key del usuario buscado
 * 
 * @return string: correo electronico del usuario o
 *                false si ocurrio error
 */
function getUserMailDB($api_key){
  global $link_decs;
                        
  if(!validMd5($api_key))
    return array('err'=>400, 'msg'=>"Valor no valido: api_key");
  
  $sql = "SELECT * FROM user WHERE api_key='". $api_key . "'";  
  $result = mysqli_query($link_decs, $sql);          
  if($result AND mysqli_num_rows($result)){ 
    $row = mysql_fetch_assoc($result);
    return array('data'=>$row);                
  }
  else
    return false;
}            
/**
 * Obtiene un usuario que cumple con ls condiciones de where,devuelve los campos especificados en fields
 * 
 * @param array $params arreglo asociativo para filtrar usuario de forma unica(key: email, api_key o id)
 * @param string $fields nombres de campos a devolver separados por coma. Si no se especifican se retornan todos los campos
 * 
 * @return array: arreglo (key=>value) con los nombres de campo y valor solicitados o
 *                false si ocurrio error
 */
function getUserDB($params, $fields=''){
  global $link_decs, $allowed_ufields;
                        
  $res_where = prepareFilterOne($params);
  if(isset($res_where['err']))
    return array('err'=>400, 'msg'=>$res_where['err']); 
  
  $where_str = $res_where['where'];

  if($fields!=''){
    $fields=str_replace(' ', '', $fields); //quitar espacios 
    $fields_arr = explode(',',$fields);
    //elimina los fields q no estan en allowed...
    $fields_arr = array_intersect($fields_arr, $allowed_ufields);
    $select = implode(',', $fields_arr);  
  }
  else
    $select=implode(',', $allowed_ufields);
    
  $sql = "SELECT $select FROM user $where_str";  
                                                                                                    
  $result = mysqli_query($link_decs, $sql);          
  if($result AND mysqli_num_rows($result)){ 
    $row = mysqli_fetch_assoc($result);
    return array('data'=>$row);                
  }
  else
    return false;
}            
/**
 * obtiene lista de usuarios 
 * 
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
function getUsersDB($params, $q='', $fields='', $sort_by='', $sort_order='', $page_size=10, $page=1){       
  global $link_decs, $allowed_ufields;

  $users = NULL;
  
  //se valida fields y se construye string con campos del select
  if($fields!=''){
    $fields=str_replace(' ', '', $fields); //quitar espacios 
    $fields_arr = explode(',',$fields);
    //elimina los fields q no estan en allowed...
    $fields_arr = array_intersect($fields_arr, $allowed_ufields);
    $select = implode(',', $fields_arr);  
  }
  else
    $select=implode(',', $allowed_ufields);

  //q los campos por los q se busca sean permitidos
  $params_keys=validateAllowed(array_keys($params), $allowed_ufields);

  //Permitir campos de fechas y  permisos
  $where = "";
  if(count($params_keys)){
    foreach($params_keys as $key){//solo campos q estan en ctx
      $value = $params[$key];  

      if(!is_integer($value))
        $value = "'%". $value ."%'";

      switch($key){
          case 'created':
          case 'modified':
            //validar q es fecha o parte de fecha
            if(strtotime($value)!==false)
              $where_param[]= "$key LIKE $value";   
            break;
          case 'permisos':
            //$value es param de json_search, no es necesario validar por sql injection?
            $where_param[]= "JSON_SEARCH('permisos', 'one', $value) IS NOT NULL";   
            break;
          default:   
            break;
      }  
    }  
    if(isset($where_param))
      $where = implode(" AND ", $where_param);
  }
  
  if($q){
    if($where)
      $where .= " AND ";
    //$q es param de json_search, no es necesario validar por sql injection?
    $where .= "(email LIKE '%$q%' OR JSON_SEARCH(permisos, 'one', '%$q%') IS NOT NULL)";//busca $q en cualquier parte de email o permisos 
  }
  
  //Permite campos de fecha y permisos?
  $sort_by_str = '';
  if($sort_by AND validateAllowed(array($sort_by), $allowed_ufields)){//si sort_by no es vacio y existe en los permitidos
    switch($sort_by){
      case 'created':
      case 'modified':
        $sort_by_str = "ORDER BY $sort_by";  
        break;
      /*case 'permisos'://?
        $dataField = field2dataField($sort_by);
        $sort_by_str = "ORDER BY data->'$dataField'";  
        break;*/
    }  
    if($sort_by_str AND $sort_order AND strtolower($sort_order)=='desc')
      $sort_by_str .= " $sort_order";  
  }

  $page = validateNatural($page) ? $page : 1;
  $page_size = validateNatural($page_size) ? $page_size : 10;
  $start = ($page-1)*$page_size;  
  $limit = "LIMIT $start, $page_size";

  //total
  $total = getCount($link_decs, 'user', $where);
  if($total){  
    if($where){
      $where = "WHERE ". $where;  
    }
      //users
      $sql = "SELECT $select FROM user $where $sort_by_str $limit";  
      //return array('err'=>409, 'msg'=>$sql);                
      $result = mysqli_query($link_decs, $sql);          
      if($result){
        while ($row = mysqli_fetch_assoc($result)) {
          if(isset($row['permisos'])){
            $row['permisos'] = json_decode($row['permisos'], 1);  
          }
          $arr_users[] = $row; 
        }
        if(isset($arr_users)){
        //PENDIENTE: devolver links de paginacion: 'first', 'last', 'next' and 'prev' , 
        //valorar pq hay q agregar a las uris de paginacion todos los param (p, param, fields, ...)

          //resultado con data y paginacion
          $users=array("total"=>$total, "page_size"=>$page_size, "page"=>$page, "list"=>$arr_users);
        }
        mysqli_free_result($result);
      }
    //}
  }
  return array('data'=>$users);                
}
/**
 * Añade un nuevo registro en la tabla user
 * 
 * @param array $data_arr: arreglo con los datos del usuario a insertar ('email', 'api_key', 'permisos'(opcional))
 * 
 * @return array: 'data': con resultado de insertar o 'err', $msg si param de entrada insuficientes  
 */
function insertUserDB($data_arr){
  global $link_decs;
   
  if(!$data_arr OR !isset($data_arr['email']) /*OR !isset($data_arr['api_key'])*/) {
    return array('err'=> 400, 'msg'=>"Es obligatorio el parametro: 'email'");
  }
  else{
    $u = getUserKeyDB($data_arr['email']);
    if($u){//ya existe o error
      if(isset($u['err'])){  
        return $u;
      }  
      //ya existe usuario  
      return array('err'=> 400, 'msg'=>"Error en parametro: ya existe un usuario con ese 'email'.", 'api_key'=>$u['api_key']);
    }
    else{//inexistente, se inserta
      $api_key = generateAPIkey($data_arr['email']);
      
      if(isset($data_arr['permisos'])){
        $perm_arr = validateJson($data_arr['permisos'], $link_decs);
        if(!$perm_arr) {
          return array('err'=> 400, 'msg'=>"Error en parametro: 'permisos' no es un JSON valido.".$data_arr['permisos']);
        }
        else{
          $perm = preparePerm($perm_arr); 
        }
      }  
      /*if(!isset($perm) OR !$perm){
        //por defecto permiso a get de todas las entidades
        $entidades = array("documents", "persons", "organizations", "events", "projects");
        $perm = array("GET"=>$entidades);
      }*/
      if(isset($perm) AND $perm){
        $permisos_str = array_to_objStr($perm, $link_decs);
        $sql = "INSERT INTO user (email, api_key, permisos, created) VALUES ('".$data_arr['email']."', '".$api_key."', JSON_OBJECT($permisos_str), NOW())";
      }
      else{
        $sql = "INSERT INTO user (email, api_key, created) VALUES ('".$data_arr['email']."', '".$api_key."', NOW())";  
      }     
      $r = mysqli_query($link_decs, $sql);
      
      return array('data'=>$r?$api_key:false);
    }
  } 
}
/**
 * Actualiza un usuario en la BD. 
 * 
 * Para actualizar 'permisos' utiliza JSON_SET que sobrescribe los q existen y agrega los nuevos.
 * Ej: Si data['permisos']= array("GET"=>array("documents","events"), "PUT"=>array("persons"))) y el usuario tenia 
 * los permisos por defecto {"GET":["documents","persons","organizations","events","projects"]}
 * al actualizar tendra los permisos {"GET":["documents","events"],"PUT":["persons"]}, sobrescribe GET y agreaga 'PUT'
 * 
 * @param integer $id identificaor del usuario a actualizar
 * @param string $data json con datos a actualizar (id, api_key no se actualizan). 
 *  
 * @return Bool TRUE|FALSE Resultado de la consulta de actualizacion o array si error 
 */
function updateUserDB($id, $data){
  global $link_decs, $allowed_ufields, $entidades;
  
  $err_msg = '';
  //validacion de parametros
  if(!validateNatural($id)){
    $err_msg = "Error en parametro: 'id' debe ser entero sin signo. ";
  }
  
  //validar JSON de data 
  $data_arr = validateJson($data, $link_decs);
  if(!$data_arr) {
    $err_msg .= "Error en parametro: 'data' no es un JSON valido. ";
    return array('err'=>400, 'msg'=>$err_msg); 
  }
  else{
    //validacion y construccion de set
    foreach($data_arr as $key=>$value){
      switch($key){
        case 'email':
          if(!valid_email_address($value)){  
            $err_msg .= "Error en parametro: 'email' no es una direccion de correo valida. ";
          }
          else{
            $u = getUserDB(array('email'=>$value), "id");
            if($u){
              if($u['data']['id'] != $id)//existe otro usuario con ese email  
                $err_msg .= "Error en parametro: 'email' ya existe . ";
            }
            else{//no existe usuario con ese email, se actualiza
              $set[] = "$key='". $value ."'";
            }
          }  
          break;  
        case 'permisos':
          if(is_array($value)){
            $perm = preparePerm($value);  
            if($perm){
              $perm_str = array_to_objStr($perm, $link_decs,0,1);  
              $set[] = "permisos=JSON_SET(permisos,$perm_str)";
            }
            else
              $err_msg .= "Error en parametro: 'permisos' no tiene un valor valido1. ";
          }  
          else
            $err_msg .= "Error en parametro: 'permisos' no tiene un valor valido2. ";
          break;  
      }   
    }  
  }
  if($err_msg){
    return array('err'=>400, 'msg'=>$err_msg); 
  }
  if(isset($set)){
    $set_str = implode(',', $set);  
    $sql = "UPDATE user SET $set_str, modified=NOW() WHERE id=$id";
    $r = mysqli_query($link_decs, $sql);
    return array('data'=>$r);
  }
  else{
    return array('err'=>400, 'msg'=>"No hay datos a actualizar, solo puede modificar 'email' y 'permisos'"); 
  }
}

function deleteUserDB($id){
  global $link_decs;
  
  //validacion de parametros
  if(!validateNatural($id)){
    return array("err"=>400, "msg"=>"Error en parametro: id debe ser entero sin signo"); 
  }
  
  $r = mysqli_query($link_decs, "DELETE FROM user WHERE id = $id");
  return array('data'=>$r);
}


function preparePerm($value){
  global $entities;  
  $entidades = array_merge($entities, array('users'));
    
  $perm = NULL;  
  foreach($value as $k=>$v){
    switch($k){
      case 'GET':  
      case 'POST':  
      case 'PUT':  
      case 'DELETE':
        if(is_array($v)){
          $v = array_intersect($v, $entidades);
          if(count($v))
            $perm[$k]=$v;  
        }   
        elseif(in_array($v,$entidades)){
          $perm[$k]=array($v);  
        } 
        break;
      default:
        break;    
    }  
  }
  return $perm;
}
function prepareFilterOne($param){
  if(!$param OR !array_intersect_key($param,array('api_key'=>1, 'email'=>1, 'id'=>1))){
    return array('err'=>"Parametro para filtrar usuario incorrecto, debe ser email, api_key o id"); 
  }

  $err_msg='';  
  foreach($param as $key=>$value){
    if($key=='api_key'){ 
      if(validMd5($value)){  
        $filter_str = "WHERE $key='". $value ."'";
        break;//una sola condicion es suficiente
      }
      else
        $err_msg = "Incorrecto el valor de 'api_key': $value";
    }   
    elseif($key=='email'){
      if(valid_email_address($value)){  
        $filter_str = "WHERE $key='". $value ."'";
        break;//una sola condicion es suficiente
      }
      else
        $err_msg = "Incorrecto el valor de 'email': $value";
    } 
    elseif($key=='id'){
      if(validateNatural($value)){
        $filter_str = "WHERE $key=". $value;
        break;//una sola condicion es suficiente
      }
      else{
        $err_msg = "Incorrecto el valor de 'id', debe ser entero sin signo"; 
      }
    }
  }
  if($err_msg){
    return array('err'=>$err_msg); 
  }
  else{
    return array('where'=>$filter_str);  
  }

}
?>
