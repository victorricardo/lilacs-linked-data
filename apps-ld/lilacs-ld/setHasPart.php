<?php
require($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require_once(PATH_LILACS_LD."db/commonDB.php");

//PENDIENTE: consultas con capa BD

set_time_limit(0); 
$ini=time(); 

echo "<BR>Asignando hasPart<BR><BR>";

setHasPart($link);

$total=(time()-$ini)/60;
echo "Tiempo de ejecucion: $total minutos<BR>";

function setHasPart($link){
  $upd = $err = 0;  

  $count = getCount($link, 'document', "data->'$.isPartOf' IS NOT NULL");
  $ctdad = intval($count/1000);
  $upd=0;
  for($i=0;$i<=$ctdad;$i++){
    $start = $i*1000;

    $sql = "SELECT id, data FROM document WHERE data->'$.isPartOf' IS NOT NULL LIMIT $start,1000";  
    $result = mysqli_query($link, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
      $data_arr = json_decode($row['data'], true);
      if(isset($data_arr['isPartOf']['id'])){
        $hasPart = array('@id'=>$data_arr['@id'], 'id'=>$data_arr['id'], '@type'=>$data_arr['@type'], 'name'=>$data_arr['name']);
        if(isset($data_arr['issueNumber']))
          $hasPart['issueNumber'] = $data_arr['issueNumber'];
        if(isset($data_arr['volumeNumber']))
          $hasPart['volumeNumber'] = $data_arr['volumeNumber'];
        
        $hasPart_str = array_to_objStr($hasPart,$link);
        //se inicializa hasPart como array, cdo no existe como path en el doc 
        $sql1 = "UPDATE document SET data = JSON_SET(data,'$.hasPart',JSON_ARRAY(JSON_OBJECT($hasPart_str))) WHERE id=". $data_arr['isPartOf']['id']. " AND JSON_CONTAINS_PATH(data, 'one', '$.hasPart')=0";
        $result1 = mysqli_query($link, $sql1);
        if($result1){
          if(mysqli_affected_rows($link)){
            $upd += 1;
          }
          else{
            //se agrega doc al hasPart si no existe ya entre los elem de hasPart 
            $sql2 = "UPDATE document SET data = JSON_ARRAY_APPEND(data, '$.hasPart',JSON_OBJECT($hasPart_str)) WHERE id=". $data_arr['isPartOf']['id']. " AND JSON_CONTAINS(data->'$.hasPart', JSON_OBJECT($hasPart_str))=0";
            $result2 = mysqli_query($link, $sql2);
            if($result2){
              if(mysqli_affected_rows($link)){
                $upd += 1;
              }
           } 
            else{
              $err += 1; 
            }  
          }    
        }
        else{
          $err += 1; 
        }  
      } 
    }
    mysqli_free_result($result);
  }  
  echo "Se actualizaron $upd hasPart<BR>";
  
  if($err)
    echo ", no se pudieron actualizar $err hasPart por errores ocurridos";
  echo "<BR>";    

}  
?>
