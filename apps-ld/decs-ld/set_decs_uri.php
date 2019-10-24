<?php
/**
 * @file
 * En datos propios de decs asigna Uris de DeCS a propiedades que se guardaron como treeNumber o texto y que su valor es Uri, 
 * como: Descriptor.broaderDescriptor, Qualifier.broaderQualifier, Descriptor.seeAlso, DescriptorQualifierPair.useInstead 
 */
include_once("get_decs_uri.inc");//decs rdf
require_once(PATH_COMMON_LD."array2json.inc");
require_once(PATH_DECS_LD."common.inc");  //pq se llama desde carpeta de lilacs too
//include_once("get_decs_data.inc");//ws decs bireme

set_time_limit(0); 

//para ejecutar en infomed
/* 
setUri_DoQ('Descriptor');
setUri_DoQ('Qualifier');
setUri_DQP();
*/

//pasa la tabla en la url para probar
if(isset($_GET['table']))
  $table = $_GET['table'];
else{
  echo 'Pasar nombre de tabla (Descriptor, Qualifier o DescriptorQualifierPair) por la URL. Ej: http://localhost/decs-ld/setUri_decsData.php/?table=Descriptor';  
  exit;
}

if($table == 'Descriptor' OR $table == 'Qualifier')
  setUri_DoQ($table);
elseif($table == 'DescriptorQualifierPair')  
  setUri_DQP();
else{
  echo 'Solo para las tablas Descriptor, Qualifier o DescriptorQualifierPair';  
} 

/* En tabla Descriptor o Qualifier asigna Uris de DeCS a Descriptor.broaderDescriptor o Qualifier.broaderQualifier, 
 * guardadas como treeNumber y a Descriptor.seeAlso guardado como texto
 *
 * @param $table
 *   string: nombre de la tabla Descriptor o Qualifier
 *
 */
function setUri_DoQ($table){
  global $link_decs, $decs_path;
  $ini=time(); 
  
  //solo las q son de decs
  $prefix = $table[0];
  $sql0 = "SELECT count(id) FROM $table WHERE identifier LIKE '_".$prefix."%' ";
  $result0 = mysqli_query($link_decs, $sql0);
  if (!$result0) {
    echo 'MySQL Error (result0): ' . mysqli_error($link_decs);
  }
  else{
    echo "Tabla: $table<BR>";
    $date=@date('Y-m-d');  
    $f_log = PATH_LOGS."log_set_decs_uriDoQ$date.txt";
    file_put_contents($f_log, "Iniciando log: $date\n");

    $count = mysqli_fetch_row($result0);
    $ctdad = intval($count[0]/1000);
    echo "ctdad:$count[0]<BR>";  
    file_put_contents($f_log, "Procesando $table, total entidades: $count[0]\n", FILE_APPEND | LOCK_EX);

    $str_seeAlso = ($table=='Descriptor') ? ", data->'$.seeAlso'" : ""; 
    for($i=0;$i<=$ctdad;$i++){
      $start = $i*1000;
      $sql = "SELECT id, data->'$.decsParentTN' $str_seeAlso FROM $table WHERE identifier LIKE '_".$prefix."%' LIMIT $start,1000";     
      $result = mysqli_query($link_decs, $sql);
      if (!$result) {
        echo 'MySQL Error (result): ' . mysqli_error($link_decs);
      }  
      else{
        while($row = mysqli_fetch_row($result)){
          echo "Procesando $table (id=".$row[0].")<BR>>";  
          file_put_contents($f_log, "Procesando $table (id=".$row[0].")\n", FILE_APPEND | LOCK_EX);
          //buscar Descriptor o Calificador por treeNumber con decsParentTN, para asignarlo a broaderDesriptor o broaderQualifier
          if($row[1]){
            $pTN = substr($row[1],1,strlen($row[1])-2); //quita corchetes 
            //los decsParentTN deben estar incluidos en el treeNumber del broader, sino no se puede determinar de forma unica el broader
            //pq la entidad esta en varios arboles con mas padres diferentes 
            $sql1 = "SELECT data->'$.\"@id\"' FROM $table WHERE JSON_CONTAINS(data->'$.treeNumber',JSON_ARRAY($pTN))";
            $result1 = mysqli_query($link_decs, $sql1);
            if (!$result1) {
              echo 'MySQL Error (result): ' . mysqli_error($link_decs);
            }  
            elseif(mysqli_num_rows($result1)){
              $row1 = mysqli_fetch_row($result1);  
              
              //actualizar broader D o Q con uri (@id)
              $result2=mysqli_query($link_decs, "UPDATE $table SET data = JSON_SET(data,'$.broader". $table ."',". $row1[0] .") WHERE id=".$row[0]);
              if (!$result2) {
                echo 'MySQL Error (result broader): ' . mysqli_error($link_decs);
              }  
              else{
                echo "Actualizado broader$table: ".$row1[0]."<BR>";
                file_put_contents($f_log, "Actualizado broader$table: ".$row1[0]."\n", FILE_APPEND | LOCK_EX);
                
                //eliminar propiedad decsParentTN, solo para asignar broader..., no es del modelo de datos
                $result3=mysqli_query($link_decs, "UPDATE $table SET data = JSON_REMOVE(data,'$.decsParentTN') WHERE id=".$row[0]) ;
                if (!$result3) {
                  echo 'MySQL Error (result): ' . mysqli_error($link_decs);
                }  
              }
              
            }
            else{
              echo "No se pudo asignar broader, no existe $table con treeNumber:$pTN o la entidad tiene varios padres <BR>";  
            }
          }    
          //seeAlso, @type descriptor => se busca y actualiza solo en tabla descriptor
          if($str_seeAlso AND $row[2] AND strpos($row[2],"@language")!==false){
            $seeAlso_uri=get_UrisByLabel($row[2]); 
            if($seeAlso_uri){
              //actualizar seeAlso de Descriptor con uri (@id)
              $upd_seeAlso=array_to_arrStr($seeAlso_uri,$link_decs);
              $result2=mysqli_query($link_decs, "UPDATE Descriptor SET data = JSON_SET(data,'$.seeAlso',JSON_ARRAY($upd_seeAlso)) WHERE id=".$row[0]);
              if (!$result2) {
                echo 'MySQL Error (result): ' . mysqli_error($link_decs);
              }  
              else{
                echo "Actualizado seeAlso con uris<BR>";  
                file_put_contents($f_log, "Actualizado $table (id=".$row[0]."), seeAlso con uris\n", FILE_APPEND | LOCK_EX);
              }
            }  
          }
            
        }
      }
    }
  }
  $total=(time()-$ini)/60;
  echo "Tiempo de ejecucion: $total min<BR>";
  file_put_contents($f_log, "Tiempo de ejecucion: $total min \n", FILE_APPEND | LOCK_EX);
}    

/* En tabla DescriptorQualifierPair asigna Uris de DeCS a propiedad useInstead guardado como texto
 *
 */
function setUri_DQP(){
  global $link_decs, $decs_path;
  $ini=time(); 
  
  //solo las q hay que asignar uri a useInstead
  $sql0 = "SELECT count(id) FROM DescriptorQualifierPair WHERE JSON_CONTAINS_PATH(data->'$.useInstead','one', '$[*].\"@language\"')";
  $result0 = mysqli_query($link_decs, $sql0);
  if (!$result0) {
    echo 'MySQL Error (result0): ' . mysqli_error($link_decs);
  }
  else{
    $date=@date('Y-m-d');  
    $f_log = PATH_LOGS."log_setUri_decsDQP$date.txt";
    file_put_contents($f_log, "Iniciando log: $date\n");

    $count = mysqli_fetch_row($result0);
    $ctdad = intval($count[0]/1000);
    echo "ctdad:$count[0]<BR>";  
    file_put_contents($f_log, "Procesando DescriptorQualifierPair, total entidades: $count[0]\n", FILE_APPEND | LOCK_EX);

    for($i=0;$i<=$ctdad;$i++){
      $start = $i*1000;
      $sql = "SELECT id, data->'$.useInstead'  FROM DescriptorQualifierPair WHERE JSON_CONTAINS_PATH(data->'$.useInstead','one', '$[*].\"@language\"') LIMIT $start,1000";     
      $result = mysqli_query($link_decs, $sql);
      if (!$result) {
        echo 'MySQL Error (result): ' . mysqli_error($link_decs);
      }  
      else{
        while($row = mysqli_fetch_row($result)){
          echo "Procesando DescriptorQualifierPair (id=".$row[0].")<BR>>";  
          file_put_contents($f_log, "Procesando DescriptorQualifierPair (id=".$row[0].")\n", FILE_APPEND | LOCK_EX);
          //buscar Descriptor o DQP por label que sea useInstead
          $uri = get_UrisByLabel($row[1], 1);
          if($uri){
            //actualizar useInstead de DQP con uri (@id)
            $result2=mysqli_query($link_decs, "UPDATE DescriptorQualifierPair SET data = JSON_SET(data,'$.useInstead','".$uri."') WHERE id=".$row[0]);
            if (!$result2) {
              echo 'MySQL Error (result): ' . mysqli_error($link_decs);
            }  
            else{
              echo "Actualizado useInstead con uris<BR>";  
              file_put_contents($f_log, "Actualizado DQP (id=".$row[0]."), useInstead con uris\n", FILE_APPEND | LOCK_EX);
            }
          }  
        }
      }
    }
  }
  $total=(time()-$ini)/60;
  echo "Tiempo de ejecucion: $total min<BR>";
  file_put_contents($f_log, "Tiempo de ejecucion: $total min \n", FILE_APPEND | LOCK_EX);
}    

?>
