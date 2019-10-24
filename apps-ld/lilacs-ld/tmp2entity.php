<?php
require($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require_once(PATH_LILACS_LD."db/conexionDB.php");
require_once("common.php");

/**
 * Recorre los diferentes tipos de entidades y si hay datos temporales validados que no estan en tabla definitiva se llama a tmp2entity
 *
 * @param $entity
 *   string: nombre de la entidad (document, person, organization) que se procesa para pasar datos temporales a tabla definitiva
 * @param $link
 *   identificador de la base de datos activa (para realizar la consulta)
 *
 */
function update_ValidTmp($link){
  $entidades = array('organization', 'person', 'event', 'project', 'document');
  echo "Pasando entidades temporales a definitivas<BR><BR>";
  foreach($entidades as $entity){
    $sql = "SELECT id FROM tmp_$entity WHERE (is_valid=1 AND id_$entity IS NULL) OR sameAs != 0";  
    $q = mysqli_query($link,$sql);
    if (!$q) {
      printf("SQL is %s!<BR>", $sql);
      echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
    }
    elseif(mysqli_num_rows($q))
      tmp2entity($entity, $link);
  } 
}

/**
 * Pasar datos temporales a tabla definitiva, marca datos temporales con id de entidad a la que se asocia en tabla definitiva 
 *
 * @param $entity
 *   string: nombre de la entidad (document, person, organization) que se procesa para pasar datos temporales a tabla definitiva
 * @param $link
 *   identificador de la base de datos activa (para realizar la consulta)
 *
 */
function tmp2entity($entity, $link) {
    global $lilacs_ctx;
    $tmp_table = 'tmp_'.$entity;                                                                       
    $id_entity = 'id_'. $entity;

    //unir los registros marcados con sameAs al registro al cual son igual y se elimina el registro marcado
    merge_sameAs($tmp_table, $link);
    $ins = 0;
    //Insertar datos temporales en la tabla definitiva si el nombre de la entidad no existe  
    $sql = "SELECT id, data, data->'$.name' AS name  FROM $tmp_table WHERE sameAs = 0 AND $id_entity IS NULL and is_valid != 0";  
    $result = mysqli_query($link, $sql);
    if (!$result) {
      printf("SQL is %s!<BR>", $sql);
      echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
    }
    else{   
      while ($row = mysqli_fetch_assoc($result)) {
        $arr_data = json_decode($row['data'],1);   
        $sql00 = "SELECT id FROM $entity WHERE JSON_CONTAINS(data->'$.name' ,'". $row['name'] ."')";  
        switch($arr_data['@type']){//chequear @type too, cdo son periodical se mantiene name en la serie, vol y issue
          case 'Periodical':
          $sql00 .= " AND data->'$.\"@type\"' ='". $arr_data['@type'] ."'";    
            break;  
          case 'PublicationVolume':
            $sql00 .= " AND data->'$.\"@type\"' ='". $arr_data['@type'] ."' AND data->'$.volumeNumber' =". $arr_data['volumeNumber'];          break;  
          case 'PublicationIssue':
            $sql00 .= " AND data->'$.\"@type\"' ='". $arr_data['@type'] ."' AND data->'$.issueNumber' =". $arr_data['issueNumber'] ." AND data->'$.isPartOf.volumeNumber' =". $arr_data['isPartOf']['volumeNumber'];          
            break;  
          default:
            break;  
        }
        $result00 = mysqli_query($link, $sql00);
        if (!$result00) {
          printf("SQL is %s!<BR>", $sql00);
          echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
        }
        elseif (!mysqli_num_rows($result00)) {//si el nombre de la entidad temporal no esta en la tabla definitiva, se inserta
          if(isset($arr_data['alternateName'])){
            unset($arr_data['alternateName']);
          }
          $json_str = array_to_objStr($arr_data, $link); 
  
          $arr_data['created'] = 1;//agregar este campo q no es de data a context
          $context_arr = getDataContext($arr_data, $lilacs_ctx);
          $context_str = array_to_objStr(array('@context' => $context_arr), $link);
          
          $sql0= "INSERT INTO $entity SET data = JSON_OBJECT($json_str), context = JSON_OBJECT($context_str), created = NOW() " ; 
          $result0 = mysqli_query($link, $sql0);
          if (!$result0) {
            printf("SQL is %s!<BR>", $sql0);
            echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
          }
          else{
            $ins += 1;  
            $id=mysqli_insert_id($link);
            $sql1= "UPDATE $tmp_table SET $id_entity = $id, modified = NOW() WHERE id=". $row['id']; 
            $result1 = mysqli_query($link, $sql1);
            if (!$result1) {
              printf("SQL is %s!<BR>", $sql1);
              echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
            }
          }
        }  
      }
    }
    echo "En $entity se agregaron $ins registros<BR>";
}
/**
 * Se unen a la entidad validada los datos de registros marcados con sameAs y estos últimos se eliminan
 *
 * @param $tmp_table
 *   string: nombre de la tabla con datos temporales que hay que procesar(tmp_person, tmp_organization, tmp_document)
 * @param $link
 *   identificador de la base de datos activa (para realizar la consulta)
 *
 */
function  merge_sameAs($tmp_table, $link){
    
    $sql = "SELECT id, data, in_document, sameAs FROM $tmp_table WHERE sameAs !=0";  
    $result = mysqli_query($link, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
      $data = json_decode($row['data'],1);
      $data['alternateName'] = $data['name'];
      unset($data['name']); //se quita name para no agregarlo con el merge 
      //se obtienen datos de la entidad a la que es sameAs
      $sql1 = "SELECT data FROM $tmp_table WHERE id =". $row['sameAs'];  
      $res1 = mysqli_query($link, $sql1);
      if($res1){
        $ret2 = mysqli_fetch_array($res1);
        $data1 = json_decode($ret2['data'],1);
      }    
      //se unen datos de la entidad existente con los de sameAs
      $new_data = array_merge_distinct($data1,$data);
      $json_str_new = array_to_objStr($new_data, $link);        

      $sql2= "UPDATE $tmp_table SET data = JSON_OBJECT($json_str_new), modified = NOW() WHERE id=". $row['sameAs']; 
      $res2 = mysqli_query($link, $sql2);
      if (!$res2) {
        printf("SQL is %s!<BR>", $sql2);
        echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
      }
      else{
        //chequear si in_document ya esta incluido sino se agrega
        $sql3 = "SELECT id FROM $tmp_table WHERE id =". $row['sameAs']. " AND !JSON_CONTAINS(in_document, '".$row['in_document']."')";  
        $res3 = mysqli_query($link, $sql3);
        if (!$res3) {
          printf("SQL is %s!<BR>", $sql2);
          echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
        }
        elseif (mysqli_num_rows($res3)) {
          $sql4= "UPDATE $tmp_table SET in_document = JSON_ARRAY_APPEND(in_document, '$', '".$row['in_document']."'), modified = NOW() WHERE id=". $row['sameAs']; 
          $res4 = mysqli_query($link, $sql4);
          if (!$res4) {
            printf("SQL is %s!<BR>", $sql4);
            echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
          }
        }   
        //despues de agregarlo a la entidad a la que es igual se elimina  
        $res5 = mysqli_query($link, "DELETE FROM $tmp_table WHERE id=". $row['id']);
        if (!$res5) {
          printf("Error eliminando entidad que es sameAs otra!<BR>");
          echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
        }
        
      }    
    }
}
?>
