<?php
/**
 * @file
 * Contiene las funciones para conectarse a la BD del DeCS RDF y obtener los datos por label (texto e idioma).
 */
require_once($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require_once(PATH_DECS_LD."db/conexionDB.php");//pq se llama desde carpeta de lilacs too

/* Busca Uri de Descriptor o DQP a partir de su label en diferentes idiomas. Primero busca en ingles, si no encuentra busca por espanol o portugues
 *
 * @param $json_label
 *   array: arreglo de jsons con los label en cada idioma, con llaves 'en', 'es', 'pt'
 * @param $table
 *   string: nombre de la tabla donde buscar el label, Descriptor o DescriptorQualifierPair
 *
 * @return
 *   string: uri del descriptor o DQP encontrado o '' si no se encontro
 */
function get_OneUriByLabel($json_label, $table){
  global $link_decs;

  $uri='';  
  if(isset($json_label['en'])){ 
    //JSON_CONTAINS no responde bien si el 2do argumento es funcion JSON_OBJECT() o JSON_ARRAY, hay que pasar un string
    $sql = "SELECT JSON_UNQUOTE(data->'$.\"@id\"') FROM $table WHERE JSON_CONTAINS(data->'$.label','". $json_label['en'] ."')";
    $result = mysqli_query($link_decs, $sql);
    if (!$result) {
      echo 'MySQL Error: ' . mysqli_error($link_decs);
    }  
    elseif(mysqli_num_rows($result)){
      $row = mysqli_fetch_row($result);  
      $uri=$row[0];
    }
  }
  if(!$uri){ //si no se  encontro n ingles se busca con es o pt
    if(isset($json_label['es'])){
      $part[]=" JSON_CONTAINS(data->'$.label','". $json_label['es'] ."') ";
    }
    if(isset($json_label['pt'])){
      $part[]=" JSON_CONTAINS(data->'$.label','". $json_label['pt'] ."') ";
    }  
    if(isset($part)){
      $where = implode("OR", $part);
      $sql1 = "SELECT JSON_UNQUOTE(data->'$.\"@id\"') FROM $table WHERE $where";
      $result1 = mysqli_query($link_decs, $sql1);
      if (!$result1) {
        echo 'MySQL Error: ' . mysqli_error($link_decs);
      }  
      elseif(mysqli_num_rows($result1)){
        $row1 = mysqli_fetch_row($result1);  
        $uri=$row1[0];
      }
    }
  }  

  return $uri;  
}

/* Recorre los descriptores expresados en label por su valor textual y busca sus URIs a partir de su label 
 *
 * @param $label
 *   string: json de los labels de los descriptores a buscar
 * @param $useInstead
 *   integer: 1 si es para la propiedad $useInstead, o 0 en caso contrario
 *
 * @return
 *   array: arreglo con uris de los descriptores encontrados o 
 *   string: uri corrspondiente a useInstead si $useInstead=1 o
 *   NULL si no se encontro descriptor
 */
function get_UrisByLabel($label, $useInstead=0){

  $label_uri = array();

  $arr_label = json_decode($label,true);
  if($useInstead){//solo tiene labels de un descriptor
    $arr_label = array($arr_label);
  }  

  foreach($arr_label as $un_label){
    $json_label = array();  
    if(strpos($un_label[0]['@value'], "/")===FALSE){
      $entity = "Descriptor";
    }
    else{
      $entity = "DescriptorQualifierPair";  
    }

    foreach($un_label as $label_lang){
      $lang = $label_lang['@language'];  
      $json_label[$lang] = '{"@value":"'.$label_lang['@value'].'", "@language":"'. $lang.'"}';
    }
    
    $uri = '';
    if($json_label){ 
      $uri = get_OneUriByLabel($json_label, $entity);
      if($uri){
        $label_uri[]=$uri;  
      }
      else{
        echo "No existe $entity con label: ".$un_label[0]['@value']."<BR>";  
      }
    }  
  }  
  if($label_uri){
    if($useInstead)
      return $label_uri[0];
    else
      return $label_uri;
  }
  else{
    return NULL;  
  }
}
  
?>
