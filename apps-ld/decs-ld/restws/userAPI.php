<?php
require_once(PATH_DECS_LD."db/userDB.php");

function userAPI($request_data){ 
  $method = $_SERVER['REQUEST_METHOD'];

  if(isset($request_data['api_key'])){
    //Autenticacion: chequear el usuario por api_key 
    $user = getUserDB(array('api_key'=>$request_data['api_key']));
    if($user AND isset($user['data'])){  
      //Autorizacion: si el usuario tiene permiso para realizar la peticion 
      $perm_arr = json_decode($user['data']['permisos'],true);  
      if(!isset($perm_arr[$method]) OR !in_array($request_data['entityType'], $perm_arr[$method]))
        response(400, "Lo sentimos no tiene permiso para realizar una peticion $method - ". $request_data['entityType']);    
      else{
        switch ($method) {
          case 'GET'://consulta
            getUsers($request_data);    
            break;     
          case 'POST'://inserta
            insertUser($request_data);    
            break;                
          case 'PUT'://actualiza
            updateUser($request_data);    
            break;      
          case 'DELETE'://elimina
            deleteUser($request_data);    
            break;
          default://metodo NO soportado
            response(405);
            break;
        }
      }  
    }
    elseif(isset($user['err'])){
      response($user['err'], $user['msg']);    
    }
    else{
      response(400, "El 'api_key' no existe en DECS-ld.");    
    }
  }
  else{
    response(400, "El parametro 'api_key' es obligatorio. Codigo de 32 caracteres que identifica a un usuario de forma unica en DECS-ld.");  }
}

function getUsers($request_data){ 
  
  $fields = isset($request_data['fields']) ? $request_data['fields'] : '';

  if(isset($request_data['id'])){         
    $result = getUserDB(array('id'=>$request_data['id']), $fields); 
  }
  /*elseif(isset($request_data['api_key'])){ //es el del usuario que hace el request        
    $result = getUserDB(array('api_key'=>$request_data['api_key']), $fields); 
  }*/
  elseif(isset($request_data['email'])){         
    $result = getUserDB(array('email'=>$request_data['email']), $fields); 
  }
  else{
    $params = array();
    $q = $sort_by = $sort_order = '';
    $page_size = 10; $page = 1; //valores por defecto 
    foreach ($request_data as $key => $value){
      switch ($key){
        case 'q': //texto en cualquier campo?
          $q = $value;
          break;  
        case 'sort_by'://fechas, correo
          $sort_by = $value;
          break;  
        case 'sort_order':
          $sort_order = $value;
          break;  
        case 'page_size':
          $page_size = $value;
          break;  
        case 'page':
          $page = $value;
          break;  
        case 'created':
        case 'modified':
        case 'permisos':
          $params[$key]=$value;
          break;    
        default:
          break;    
      }  
    }  
    $result = getUsersDB($params, $q, $fields, $sort_by, $sort_order, $page_size, $page);
  }    
  
  if(isset($result['err'])){
    response($result['err'], $result['msg']);//Bad request, error en parametros
  }
  elseif($result['data']){
    response(200, "", $result['data']);//OK, msg puede contener warning de fields inexistentes
  }  
  else{
    response(404);//Recurso no encontrado
  }
} 
function insertUser($request_data){ 

  if(!isset($request_data['email']))
    response(400, 'Correo electronico obligatorio');
  else{
    $result = insertUserDB($request_data);  
    if(isset($result['api_key'])){  //recuperar api_key en insert o hacer otro metodo para esto????
      //enviar api_key recuperada al email
      //mail($email,"Email ya existe en LILACS-ld, 'api_key' ha sido recuperada.", "Su api_key es: ". $result['api_key']);
      response(200, "Usuario ya existe, su 'api_key' ha sido recuperada y enviada al correo-e ". $result['api_key']);  
    }
    elseif(isset($result['err'])){
      response($result['err'], $result['msg']);
    }
    elseif(!$result['data']){
      response(500);
    }
    else{
      //enviar api_key al email
      //mail($email,"Creado el usuario en LILACS-ld.", "Su 'api_key' es: ". $result['data']);
      response(201, "Creado el usuario, su 'api_key' ha sido enviada al correo-e ". $result['data']);  
    }
  }
} 

function updateUser($request_data){

  if(isset($request_data['id']) AND isset($request_data['data'])){
    $result = updateUserDB($request_data['id'], $request_data['data']);
    //response(200);exit;
    if(isset($result['err'])){
      response($result['err'], $result['msg']);//error en param o no encontrado
    }
    elseif($result['data']){
      response(200, "Actualizado"); //OK: Actualizado
    }  
    else{
      response(500);//Error al actualizar (Internal server error)
    }
  }
  else{
    response(400, "Los parametros 'id' y 'data' son obligatorios");
  }
}  

function deleteUser($request_data){ 
  if(isset($request_data['id'])){
    $result = deleteUserDB($request_data['id']);
    if(isset($result['err'])){
      response($result['err'], $result['msg']);//error en param (400) o enlazado en otros doc (409)
    }
    elseif($result['data']){
      response(200, "Eliminado"); //OK: Eliminado
    }  
    else{
      response(404);//Recurso no encontrado
    }
  }
  else{
    response(400, "El parametro 'id' es obligatorio");
  }
} 

function generateAPIkey(){
  global $link_decs;

  do{
    $random = mt_rand();//aleatorio pero se puede repetir
    $api_key = md5($random);
    $count = mysqli_fetch_row(mysqli_query($link_decs, "SELECT count(id) FROM user WHERE api_key='".$api_key."'"));
  }while($count!==false AND $count[0]);  
  //}while(getCount($link_decs, "user", "api_key='".$api_key."'"));
  
  return $api_key;
}  
function getCount($link_decs, $table, $where='', $field='id'){
  if($where)
    $where = "WHERE ". $where;
  $count = mysqli_fetch_row(mysqli_query($link_decs, "SELECT count($field) FROM $table $where"));
  if($count!==false)
    return $count[0];
  else
    return false;  
}

?>
