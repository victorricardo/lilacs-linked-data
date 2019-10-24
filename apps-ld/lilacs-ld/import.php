<?php
/**
 * @file
 * Se conecta al servicio web para recuperar documentos ISIS de una BD LILACS. 
 * Los datos del registro original se guardan en la tabla document, en el campo originalRecord de tipo json.
 * Se  utiliza para insertar o actualizar los datos originales de los documentos ISIS, en la BD de lildbi ld.
 */
require($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require(PATH_COMMON_LD."xml2array.php");
require_once(PATH_LILACS_LD."db/documentDB.php");

set_time_limit(0); 
$ini=time(); 
$db = 'cumed';
$url = 'http://isis.oai.sld.cu/?verb=ListRecords&resumptionToken=-_--_-isis-_-'.$db.'-_-';

//PENDIENTE: si se va a ejecutar importacion mas de una vez descomentar lo relativo a update
//PENDIENTE: crear fichero de logs

//contador de originalRecords insertados 
$ins = 0;

$fin = 1;  //se asigna despues del 1r llamado al ws, a partir del total de doc de la bd
echo "Iniciando importación database: $db<BR>";
$ii=0;
for($i=0; $i<$fin;$i++) {
  $contenido = '';
  $valor = $url . $i*20; //al ws se pide un max de 20 documentos
  $contenido = file_get_contents($valor);
  //echo $contenido;
  //exit;
   
  if (trim($contenido) != '') {
    $resultado = xml2array($contenido);
    $records = $resultado['OAI-PMH']['ListRecords']['record'];
    if(!isset($total_docs)){
      $tempArray = explode(":",(string)$records[0]['header']['setSpec']);
      $total_docs = $tempArray[1];
      echo "total_docs: $total_docs</BR>";
      $fin = $total_docs/20; //en cada llamado al ws se piden 20 documentos 
      $fin = 10;  
    }
    if (is_array($records) && count($records) > 0) {
      foreach($records as $record ){
        echo "registro:".++$ii."<BR>";  
        $r = insertOriginalRecordDB($record['metadata']['isis'], $link);  
        if($r)
          $ins += 1; //se inserto
        else
          echo "Error al importar: (v1->". $record['metadata']['isis']['v1'] .", v2->". $record['metadata']['isis']['v2'] . ")<BR>";  
        
      }
    }
  }
}
echo "Insertados ".$ins." registros<BR>";
$total=(time()-$ini)/60;
echo "Tiempo de ejecucion: $total minutos<BR>";

//UPDATE
//PENDIENTE: consultas a la BD a traves de capa BD
/*
//arreglo de los ids originales (v2) de los doc de esa $bd
$originalRecord_ids = original_ids($db, $link);

//contadores de originalRecord insertados y actualizados  
$ins = $upd = 0;

$fin = 1;  //se asigna despues del 1r llamado al ws, a partir del total de doc de la bd
echo "Iniciando importación<BR>";
$r=0;
for($i=0; $i<$fin;$i++) {
    $contenido = '';
    $valor = $url . $i*20; //al ws se pide un max de 20 documentos
    $contenido = file_get_contents($valor);
    //echo $contenido;
    //exit;
    
    if (trim($contenido) != '') {
      $resultado = xml2array($contenido);
      $records = $resultado['OAI-PMH']['ListRecords']['record'];
      if(!isset($total_docs)){
        $tempArray = explode(":",(string)$records[0]['header']['setSpec']);
        $total_docs = $tempArray[1];
        echo "total_docs: $total_docs</BR>";
        $fin = $total_docs/20; //en cada llamado al ws se piden 20 documentos 
        $fin = 5;
      }
      if (is_array($records) && count($records) > 0) {
         foreach($records as $record ){
           echo "registro:".++$r."<BR>";  
             $v1_v2 = array($record['metadata']['isis']['v1'], $record['metadata']['isis']['v2']);
             
             if(!in_array($v1_v2,$originalRecord_ids)) {
               //convertir array $record['metadata']['isis'] en cadena válida para pasar por JSON_OBJECT()
               $json_str = array_to_objStr($record['metadata']['isis'], $link); 
               if(!json_argument_error($json_str,$link)){  //chequear cadena $json_str 
                 $ins += create_originalRecord($json_str, $link);  
               }  
             }
             else{
               $id = array_search($v1_v2,$originalRecord_ids);
               //convertir array $record['metadata']['isis'] en cadena válida para pasar por JSON_SET()
               $json_str = array_to_objStr($record['metadata']['isis'], $link, 0, 1); 
               if(!json_argument_error($json_str,$link)){  //chequear cadena $json_str 
                 $upd += update_originalRecord($id, $json_str, $link);  
               }  
             } 
         }
      }
    }
}
echo "Insertados ".$ins." registros<BR>";
echo "Actualizados ".$upd." registros<BR>";
$total=(time()-$ini)/60;
echo "Tiempo de ejecucion: $total minutos<BR>";

//PENDIENTE: si se va a actualizar originalRecord (ejecutar importacion mas de una vez), crear campo newOriginalRecord
//PENDIENTE: si se va a ejecutar importacion solo una vez eliminar esta funcion
function update_originalRecord($id, $json_str, $link){
   $sql= "UPDATE Document SET originalRecord = JSON_SET(originalRecord, $json_str) WHERE id = ". $id; 
   $result = mysqli_query($link, $sql);
   if (!$result) {
     printf("SQL is %s!<BR>", $sql);
     echo 'MySQL Error: ' . mysqli_error($link);
   }
   elseif(mysqli_affected_rows($link)==1){//se actualizo, habia diferencias, originalRecord es nuevo
     $sql= "UPDATE Document SET transfered = NOW() WHERE id = ". $id; 
     //$sql= "UPDATE Document SET transfered = NOW(), newOriginalRecord = 1 WHERE id = ". $id; 
     $result = mysqli_query($link, $sql);
     if (!$result) {
       printf("SQL is %s!<BR>", $sql);
       echo 'MySQL Error: ' . mysqli_error($link);
     }
     else
       return 1;
   }  
   return 0;
}
/*
               //codigo antes de update_originalRecord si se utiliza esta de abajo
               if(isset($record['metadata']['isis']['v93']))
                 $modifiedOriginalR = migrar_fecha($record['metadata']['isis']['v93']);//ultima actualizacion de reg original
               else
                 $modifiedOriginalR = NULL;
               $upd += update_originalRecord($id, $json_str, $modifiedOriginalR, $link);  
               
function update_originalRecord($id, $json_str, $modifiedOriginalR, $link){
   $upd = 0; 
   //Si existe fecha modific comprobar por fecha
   if($modifiedOriginalR){
     $sql1 = "SELECT id, originalRecord->'$.v93' FROM document WHERE id=$id";
     $result1 = mysqli_query($link, $sql1);
     if (!$result1) {
       printf("SQL is %s!<BR>", $sql1);
       echo 'MySQL Error: ' . mysqli_error($link);
     }
     else{
       $row =  mysqli_fetch_row($result1);
       if($row[1] != $modifiedOriginalR){ //son diferentes las fechas de modificacion (v93) del originalRecord y el importado
         $upd = 1;
       }
     }
   }
   else{
     //chequear si hay cambios en original record
     $sql1 = "SELECT id, originalRecord FROM document WHERE id=$id AND originalRecord != JSON_OBJECT($json_str)";
     $result1 = mysqli_query($link, $sql1);
     if (!$result1) {
       printf("SQL is %s!<BR>", $sql1);
       echo 'MySQL Error: ' . mysqli_error($link);
     }
     elseif(mysqli_num_rows($result1)){
       $modifiedOriginalR = NOW();  
       $upd = 1;  
     }
   }  
   if($upd){  
     //si hay cambios se actualizan originalRecord y dateTransfered 
     $sql= "UPDATE Document SET originalRecord = JSON_OBJECT($json_str), modifiedOriginalRecord = NOW() WHERE id = ". $id; 
     $result = mysqli_query($link, $sql);
     if (!$result) {
       printf("SQL is %s!<BR>", $sql);
       echo 'MySQL Error: ' . mysqli_error($link);
     }
     else{
       return 1;
     }
   }  
   return 0;
}
*/                                               
//PENDIENTE: si se va a ejecutar importacion solo una vez eliminar esta funcion
/*function original_ids($db, $link){
  $originalIds = array();  
  $sql = "SELECT id, originalRecord->'$.v1', originalRecord->'$.v2' FROM document WHERE JSON_SEARCH(data->'$.database', 'one', '$db') IS NOT NULL";
  $result = mysqli_query($link, $sql);
  if (!$result) {
   printf("SQL is %s!<BR>", $sql);
   echo 'MySQL Error: ' . mysqli_error($link);
  }
  else{
    while ($row = mysqli_fetch_row($result)) {
      $originalIds[$row[0]] = array($row[1], $row[2]);  
    }
  }
  return $originalIds;
}  
*/

?>
