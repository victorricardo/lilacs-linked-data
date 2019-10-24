<?php
require_once(PATH_LILACS_LD."db/entityDB.php");
require_once("userAPI.php");

function entityAPI($request_data){ 
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
            getEntitys($request_data);    
            break;     
          case 'POST'://inserta
            insertEntity($request_data);    
            break;                
          case 'PUT'://actualiza
            updateEntity($request_data);    
            break;      
          case 'DELETE'://elimina
            deleteEntity($request_data);    
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
      response(400, "El 'api_key' no existe en LILACS-ld.");    
    }
  }
  else{
    response(400, "El parametro 'api_key' es obligatorio. Codigo de 32 caracteres que lo identifica de forma unica en LILACS-ld.");  }
}

function getEntitys($request_data){
  global $allowed_prop;

  if(isset($request_data['id'])){         
    $fields = isset($request_data['fields']) ? $request_data['fields'] : '';
    $result = getEntityDB($request_data['table'], $request_data['id'], $fields, $allowed_prop); 
  }
  else{
    $params = array();
    $q = $fields = $sort_by = $sort_order = '';
    $page_size = 10; $page = 1; //valores por defecto 
    foreach ($request_data as $key => $value){
      switch ($key){
        case 'q':
          $q = $value;
          break;  
        case 'fields':
          $fields = $value;
          break;  
        case 'sort_by':
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
        case 'entityType':
          break;
        default:
          //si en la url el nombre de param tiene '.' (provider.name), aqui llega con '_' (provider_name), 
          //se restablece el '.' (provider.name)
          $key=str_replace('_', '.', $key);
          $params[$key]=$value;
          break;    
      }  
    }  
    $result = getEntitysDB($request_data['table'], $params, $q, $fields, $sort_by, $sort_order, $page_size, $page, $allowed_prop);
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
  
function insertEntity($request_data){
  global $allowed_prop;
    
  if(isset($request_data['data'])){
    $result = insertEntityDB($request_data['table'], $request_data['data'], $allowed_prop);
    if(isset($result['err'])){
      response($result['err'], $result['msg']);//Bad request, error en parametros
    }
    elseif($result['data']){
      response(201, "Insertado", $result['data']); //Insertado
    }  
    else{
      response(500);//Recurso no insertado
    }
  }
  else{
    response(400, "El parametro 'data' es obligatorio");
  }
}
  
function updateEntity($request_data){
  global $allowed_prop;

  if(isset($request_data['id']) AND isset($request_data['data'])){
    $result = updateEntityDB($request_data['table'], $request_data['id'], $request_data['data'], $allowed_prop, 1);
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

function deleteEntity($request_data){

  if(isset($request_data['id'])){
    $result = deleteEntityDB($request_data['table'], $request_data['id']);
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
?>
