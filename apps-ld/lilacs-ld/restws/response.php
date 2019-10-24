<?php
/**
 * Respuesta al cliente
 * @param int $code Codigo de respuesta HTTP
 * @param String $status indica el estado de la respuesta puede ser "success", "error" o "fail"
 * @param String $message Usado solo para "error" o "fail", contiene el mensaje de error
 * @param String $data Contiene el cuerpo de la respuesta
 */
function response($code=200, $message="", $data="") {
    global $format;
    
    $httpMsg = array(
        200 => 'OK',    
        201 => 'Created',  
        204 => 'No Content',  
        400 => 'Bad Request',  
        401 => 'Unauthorized',  
        403 => 'Forbidden',  
        404 => 'Not Found',  
        405 => 'Method Not Allowed',  
        409 => 'Conflict',  
        413 => 'Request Entity Too Large',  
        414 => 'Request-URI Too Long',  
        416 => 'Requested Range Not Satisfiable',  
        500 => 'Internal Server Error',  
        501 => 'Not Implemented');

    //http_response_code($code);
    
    switch(intval($code/100)){
      case 1:
      case 2:
      case 3:
        $status = 'success';
        break;
      case 4:
        $status = 'error';
        break;
      case 5:
        $status = 'fail';
        break;
          
    }
    
    if( empty($message) ){
      $message = $httpMsg[$code];  
    }       
    
    //aqui se pueden enviar los http headers (content type, code y status, location del insert)
       
    if($status == 'success'){
      //$data = json_decode($data);  //es un json
      $response = array("code" => $code, "status" => $status, "message"=>$message, "data"=>$data);  
    }  
    else{
      $response = array("code" => $code, "status" => $status, "message"=>$message);  
    }
    if($format=='json'){
      header("Content-Type:application/json; charset=utf-8");  
      echo json_encode($response,JSON_PRETTY_PRINT); //JSON_PRETTY_PRINT: json recogido en {...} y [...]
      //echo json_encode($response);  //descomentar para ver json desplegado  
    }
    //exit;//comentar?
}     
?>
