<?php
 
/**
 * Performing the real request
 *
 * @param string $method
 * @param string $url
 * @param array $params
 * @return string
 */
function execRequest($method, $url, $params = array()){
  global $user;
    
  //$headers = setHeaders();
  header("Content-Type:application/json");  

  $s = curl_init();
 
  if(!is_null($user)){
    curl_setopt($s, CURLOPT_USERPWD, $user['user'].':'.$user['pass']);
  }
 
  switch ($method) {                                              
    case 'GET':
        curl_setopt($s, CURLOPT_URL, $url . '?' . http_build_query($params));
        break;
    case 'POST': //insert o api_key
        curl_setopt($s, CURLOPT_URL, $url);
        curl_setopt($s, CURLOPT_POST, true);
        $params_str = json_encode($params);
        curl_setopt($s, CURLOPT_HTTPHEADER, array('Content-Length: '.strlen($params_str)));
        curl_setopt($s, CURLOPT_POSTFIELDS, $params_str);
        break;
    case 'PUT': //update
        curl_setopt($s, CURLOPT_URL, $url);
        curl_setopt($s, CURLOPT_POST, true);
        curl_setopt($s, CURLOPT_CUSTOMREQUEST, "PUT");  //for updating we have to use PUT method.
        $params_str = json_encode($params);
        curl_setopt($s, CURLOPT_HTTPHEADER, array('Content-Length: '.strlen($params_str)));
        curl_setopt($s, CURLOPT_POSTFIELDS, $params_str);
        break;
    case 'DELETE':
        curl_setopt($s, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($s, CURLOPT_CUSTOMREQUEST, 'DELETE');
        break;
  }
 
  curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($s, CURLOPT_HTTPHEADER, array("Content-Type"=>"application/json; charset=utf-8"));
  curl_setopt($s, CURLOPT_ENCODING, "");

  $out = curl_exec($s);

  $status = curl_getinfo($s, CURLINFO_HTTP_CODE);
  curl_close($s);
  /*switch ($status) {
    case HTTP_OK:
    case HTTP_CREATED:
    case HTTP_ACEPTED:
      $out = $_out;
      break;
    default:
      $out = "";
      throw new Http_Exception("http error: {$status}", $status);
  } */
  return $out;
}  
?>
