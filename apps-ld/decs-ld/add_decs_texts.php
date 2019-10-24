<?php
/**
 * @file
 * Agrega traducciones a las propiedades de texto libre de las entidades migradas desde MeSH.
 */

include_once("get_decs_data.inc");

set_time_limit(0); 

//para ejecutar en infomed
/* 
add_texts_DoQ('Descriptor');
add_texts_DoQ('Qualifier');
add_texts_DQP();
*/

//pasa la tabla en la url para probar
if(isset($_GET['table']))
  $table = $_GET['table'];
else{
  echo 'Pasar nombre de tabla (Descriptor, Qualifier o DescriptorQualifierPair) por la URL. Ej: http://localhost/decs-ld/add_decs_texts.php/?table=Descriptor';  
  exit;
}

if($table == 'Descriptor' OR $table == 'Qualifier')
  add_texts_DoQ($table);
elseif($table == 'DescriptorQualifierPair'){
  //Para probar, quitar en infomed
  $translated = get_descriptor_translated();
  add_texts_DQP();
    
}  
else{
  echo 'Solo para las tablas Descriptor, Qualifier o DescriptorQualifierPair';  
}
    
/* Agrega traducciones en Descriptores o Calificadores a las propiedades q son texto libre, con datos q obtiene del sw del DeCS 
 *
 * @param $table
 *   string: nombre de la tabla Descriptor o Qualifier
 *
 */
function add_texts_DoQ($table){
  global $link_decs, $decs_path;
  $ini=time(); 
  
  $lang = 'en';//los de mesh estan en ingles => la 1ra busqueda se hace en ingles
  
  //solo las q son de mesh?
  $prefix = $table[0];
  $sql0 = "SELECT count(id) FROM $table WHERE identifier LIKE '".$prefix."%' AND JSON_LENGTH(data->'$.label') = 1 ";
  $result0 = mysqli_query($link_decs, $sql0);
  if (!$result0) {
    echo 'MySQL Error (result0): ' . mysqli_error($link_decs);
  }
  else{
    echo "Tabla:$table<BR>"; 
    $date=@date('Y-m-d'); 
    $f_log = PATH_LOGS."log_decs_texts_$table$date.txt";
    file_put_contents($f_log, "Iniciando log: $date\n");

    $count = mysqli_fetch_row($result0);
    $ctdad = intval($count[0]/1000);
    echo "ctdad:$count[0]<BR>";  
    file_put_contents($f_log, "Procesando $table, total entidades: $count[0]\n", FILE_APPEND | LOCK_EX);

    switch($table){
      case 'Descriptor':  
        $index = "101";
        break;
      case 'Qualifier':  
        $index = "401";
        break;
    }
    for($i=0;$i<=$ctdad;$i++){
      $start = $i*1000;
      $sql = "SELECT id, data->'$.label', data->'$.treeNumber', JSON_UNQUOTE(data->'$.preferredConcept'), 
                    data->'$.annotation', data->'$.considerAlso', identifier, JSON_UNQUOTE(data->'$.preferredTerm') 
              FROM $table WHERE identifier LIKE '".$prefix."%' AND JSON_LENGTH(data->'$.label') = 1 LIMIT $start,1000";     
      $result = mysqli_query($link_decs, $sql);
      if (!$result) {
        echo 'MySQL Error (result): ' . mysqli_error($link_decs);
      }  
      else{
        while($row = mysqli_fetch_row($result)){
          $j++;  
          $label_en = json_decode($row[1],true);
          $label_str = $label_en[0]['@value'];
          $treeNs = json_decode($row[2],true);
          $treeNumber = substr($treeNs[0],$decs_path['TreeNumbers']['len']); 
          
          if($label_str OR $treeNumber){
            echo "$j- $label_str  ".$treeNumber."<BR>";
            file_put_contents($f_log, "$label_str  ".$treeNumber."\n", FILE_APPEND | LOCK_EX);

            //busca en DeCS items por treeNumber y si no lo encuentra por label
            $item = get_item_by_treeN($treeNumber, $lang);  
            if(!$item){
              file_put_contents($f_log, "No se encontro por treeNumber, busca por label\n", FILE_APPEND | LOCK_EX);
              $item = get_item_by_label($label_str, $index, $lang);  
            }
            if($item){
              if($row[6] == $item['record_list']['record']['unique_identifier_nlm'] ){ //si tienen el mismo identificador
                $decsData = decs2decsRDF($item, $table, $lang);  
                if($decsData){
                  //mantener valores en ingles del mesh  
                  $decsData['label'][0] = $label_en[0];  
                  if($row[4]){
                    $annotation_en = json_decode($row[4],true);       
                    $decsData['annotation'] = array($annotation_en[0]);
                  }
                  if($row[5]){
                    $considerAlso_en = json_decode($row[5],true);     
                    $decsData['considerAlso'] = array($considerAlso_en[0]);
                  }
                  //agregar textos en otros idiomas  
                  $decsData = get_texts_other_lang($decsData,$table,$lang);
                  //Actualizar textos en Descriptor o Calificador
                  $label = array_to_arrStr($decsData['label'],$link_decs);
                  $sql2 = "UPDATE $table SET data = JSON_SET(data,'$.label',JSON_ARRAY($label)";
                
                  if(isset($decsData['annotation'])){
                    $annotation = array_to_arrStr($decsData['annotation'],$link_decs);
                    $sql2 = $sql2 .",'$.annotation',JSON_ARRAY($annotation)";
                  }
               
                  if(isset($decsData['considerAlso'])){
                    $considerAlso = array_to_arrStr($decsData['considerAlso'],$link_decs);
                    $sql2 = $sql2 .",'$.considerAlso',JSON_ARRAY($considerAlso)";
                  }

                  $sql2= $sql2 ."), decsUpdated=NOW() WHERE id=". $row[0];
                  //echo $sql2."<BR>";
                  $result2 = mysqli_query($link_decs, $sql2);
                  if (!$result2) {
                    echo 'MySQL Error (result2): ' . mysqli_error($link_decs)."<BR>";
                  } 
                  else{
                    echo "Actualizados textos de $row[6]<BR>";  
                    file_put_contents($f_log, "Actualizados textos de $row[6]\n", FILE_APPEND | LOCK_EX);
                  }
                 //actualizar label y scopeNote en preferredConcept 
                 $scopeNote = isset($decsData['scopeNote']) ? $decsData['scopeNote'] : NULL;
                 $upd = upd_prefConcept($row[3], $label, $scopeNote);
                 if($upd){
                   echo "Actualizados textos de prefConcept $row[3]<BR>";  
                   file_put_contents($f_log, "Actualizados textos de prefConcept $row[3]\n", FILE_APPEND | LOCK_EX);
                 }
                 //actualizar label y altLabel en preferredTerm 
                 $altLabel = isset($decsData['altLabel']) ? $decsData['altLabel'] : NULL;
                 $upd = upd_prefTerm($row[7], $label, $altLabel);
                 if($upd){
                   echo "Actualizados textos de prefTerm $row[7]<BR>";  
                   file_put_contents($f_log, "Actualizados textos de prefTerm $row[7]\n", FILE_APPEND | LOCK_EX);
                 }  
                 //print_r($decsData);
                }
              } 
              else{
                echo "$treeNumber: No coinciden los identificadores (mesh)$row[6] != (decs)".$item['unique_identifier_nlm']." <BR>";
                file_put_contents($f_log, "$treeNumber: Identificadores (mesh)$row[6] != (decs)".$item['unique_identifier_nlm']." \n", FILE_APPEND | LOCK_EX);
              }
            }
            else{
              echo "No se encontro <BR>";
              file_put_contents($f_log, "No se encontro \n", FILE_APPEND | LOCK_EX);
            }
          }
        }
        mysqli_free_result($result);
      }                                                           
    }
  }
  $total=(time()-$ini)/60;
  echo "Tiempo de ejecucion: $total min<BR>";
  file_put_contents($f_log, "Tiempo de ejecucion: $total min \n", FILE_APPEND | LOCK_EX);
}


/* Agrega traducciones en DescriptorQualifierPair a las propiedades q son texto libre, a partir de traducciones 
 * de labels de descriptor y calificador en BD de Decs RDF
 *
 */
function add_texts_DQP(){
  global $link_decs, $decs_path;
    
  $ini=time(); 
  
  //$sql0 = "SELECT count(id) FROM DescriptorQualifierPair";
  $sql0 = "SELECT count(id) FROM DescriptorQualifierPair WHERE JSON_LENGTH(data->'$.label')=1";
  $result0 = mysqli_query($link_decs, $sql0);
  if (!$result0) {
    echo 'MySQL Error (count DescriptorQualifierPair): ' . mysqli_error($link_decs);
  }
  else{
    echo "Tabla: DescriptorQualifierPair<BR>";  

    $date=@date('Y-m-d'); 
    $f_log = PATH_LOGS."log_decs_texts_DescriptorQualifierPair$date.txt";
    file_put_contents($f_log, "Iniciando log: $date\n");

    $count = mysqli_fetch_array($result0);
    $ctdad = intval($count[0]/1000);
    echo "ctdad:$count[0]<BR>";  
    file_put_contents($f_log, "Procesando DescriptorQualifierPair, total entidades: $count[0]\n", FILE_APPEND | LOCK_EX);

    for($i=0;$i<=$ctdad;$i++){
      $start = $i*1000;
      //$sql = "SELECT id, data->'$.label', JSON_UNQUOTE(data->'$.hasDescriptor'), JSON_UNQUOTE(data->'$.hasQualifier') FROM DescriptorQualifierPair LIMIT $start,1000";  
      $sql = "SELECT id, data->'$.label', JSON_UNQUOTE(data->'$.hasDescriptor'), JSON_UNQUOTE(data->'$.hasQualifier') ".
             "FROM DescriptorQualifierPair WHERE JSON_LENGTH(data->'$.label')=1 LIMIT $start,1000";  
      $result = mysqli_query($link_decs, $sql);
      if (!$result) {
        echo "MySQL Error (result DescriptorQualifierPair): " . mysqli_error($link_decs);
      }  
      else{
        while($row = mysqli_fetch_row($result)){
          $labelDQ = json_decode($row[1],true);
          if(count($labelDQ)==1){//solo tiene en ingles
            $labelDescriptor = get_label('Descriptor', $row[2], $decs_path['Descriptors']['len']); 
            $labelQualifier = get_label('Qualifier', $row[3], $decs_path['Qualifiers']['len']);
            
            //existe label de descr y calificador y estan en mas idiomas q el ingles 
            if($labelDescriptor AND count($labelDescriptor)>1 AND $labelQualifier AND count($labelQualifier)>1 ){
              foreach($labelDescriptor as $valueD){
                if($valueD['@language'] != 'en'){
                  foreach($labelQualifier as $valueQ){
                    if($valueQ['@language'] == $valueD['@language']){
                      break;  
                    }  
                  }  
                  $labelDQ[] = array('@value' => $valueD['@value'] .'/'. $valueQ['@value'], '@language' => $valueD['@language']);
                }  
              }
              $labelDQ_str = array_to_arrStr($labelDQ, $link_decs);
              $sql2 = "UPDATE DescriptorQualifierPair SET data = JSON_SET(data,'$.label',JSON_ARRAY($labelDQ_str)), decsUpdated=NOW() WHERE id=". $row[0];
              $result2 = mysqli_query($link_decs, $sql2);
              if (!$result2) {
                echo "MySQL Error (upd DescriptorQualifierPair): " . mysqli_error($link_decs)."<BR>";
              } 
              else{
                echo "DQP id:$row[0]<BR>";  
                file_put_contents($f_log, "Actualizado DQP $row[0]\n", FILE_APPEND | LOCK_EX);
              }
            }    
          }
        }
      }
    } 
  }
  $total=(time()-$ini)/60;
  echo "Tiempo de ejecucion: $total min<BR>";
  file_put_contents($f_log, "Tiempo de ejecucion: $total min\n", FILE_APPEND | LOCK_EX);
}

/* Actualiza traducciones en Concept a las propiedades q son texto libre, a partir de datos del DeCS
 *
 * @param $prefConcept
 *   string: uri del concepto a actualizar
 * @param $label
 *   string: valor a asignar en label, como argumento para JSON_ARRAY
 * @param $scopeNoteTranslation
 *   array: valor a asignar en scopeNote con traducciones
 *
 * @return
 *   integer: 1 si se actualizo, 0 en caso contrario
 */
function upd_prefConcept($prefConcept, $label, $scopeNoteTranslation, $link_decs){
global $decs_path;

 $ret = 0;
 $identifier = substr($prefConcept,$decs_path['Concepts']['len']); 
 echo "prefConcept:$prefConcept, identifier:$identifier<BR>";     

 $result = mysqli_query($link_decs, "SELECT id, data->'$.label', data->'$.scopeNote' FROM Concept WHERE identifier='".$identifier."'");
 if (!$result) {
   echo 'MySQL Error (result prefConcept): ' . mysqli_error($link_decs);
 }
 elseif(mysqli_num_rows($result)){
   $row = mysqli_fetch_array($result);
   $sql = '';
   
   $labelConcept = json_decode($row[1],true);       
   if(count($labelConcept)==1){//si solo existe en ingles
     //se actualiza label
     $sql= "UPDATE Concept SET data = JSON_SET(data, '$.label', JSON_ARRAY($label)" ;
   }
   if($scopeNoteTranslation){
     $scopeNoteConcept = json_decode($row[2],true);       
     $strScopeNote = '';
     if(count($scopeNoteConcept)==1){//si solo existe en ingles
       //mantener valor en ingles del mesh  
       $scopeNote_en = array($scopeNoteConcept[0]); 
       $scopeNoteTranslation = array_merge($scopeNote_en,$scopeNoteTranslation);
       $strScopeNote = array_to_arrStr($scopeNoteTranslation,$link_decs);
       //se actualiza scope note
       if(!$sql){
         $sql= "UPDATE Concept SET data = JSON_SET(data, '$.scopeNote', JSON_ARRAY($strScopeNote)";
       }
       else{
         $sql= $sql. ", '$.scopeNote', JSON_ARRAY($strScopeNote)" ;
       }
     }
   }  
   if($sql){
     $sql= $sql. "), decsUpdated=NOW() WHERE id=".$row[0] ;
     //echo $sql."<BR>";
     $result1 = mysqli_query($link_decs, $sql);
     if (!$result1) {
       echo 'MySQL Error (result1 prefConcept): ' . mysqli_error($link_decs)."<BR>";
     }
     else
       $ret = 1;//se actualizo
   } 
 }
 return $ret;   
}

/* Actualiza traducciones en Term a las propiedades q son texto libre, a partir de datos del DeCS
 *
 * @param $prefTerm
 *   string: uri del termino a actualizar
 * @param $label
 *   string: valor a asignar en prefLabel, como argumento para JSON_ARRAY
 * @param $altLabelTranslation
 *   array: valor a asignar en altLabel con traducciones
 *
 * @return
 *   integer: 1 si se actualizo, 0 en caso contrario
 */
function upd_prefTerm($prefTerm, $label, $altLabelTranslation){
global $decs_path, $link_decs;

 $ret = 0;
 $identifier = substr($prefTerm,$decs_path['Terms']['len']); 
 $result = mysqli_query($link_decs, "SELECT id, data->'$.prefLabel' FROM Term WHERE identifier='".$identifier."'");
 if (!$result) {
   echo 'MySQL Error (result prefTerm): ' . mysqli_error($link_decs);
 }
 elseif(mysqli_num_rows($result)){
   $row = mysqli_fetch_array($result);
   $sql = '';
   
   $labelTerm = json_decode($row[1],true);       
   if(count($labelTerm)==1){//si solo existe en ingles
     //se actualiza label
     $sql= "UPDATE Term SET data = JSON_SET(data, '$.prefLabel', JSON_ARRAY($label)" ;
   }
   if($altLabelTranslation){
     $strAltLabel = array_to_arrStr($altLabelTranslation,$link_decs);
     //se actualiza altLabelDeCS campo agregado para revisar manualmente correspondencia en dif idiomas 
     if(!$sql){
       $sql= "UPDATE Term SET data = JSON_SET(data, '$.altLabelDeCS', JSON_ARRAY($strAltLabel)";
     }
     else{
       $sql= $sql. ", '$.altLabelDeCS', JSON_ARRAY($strAltLabel)" ;
     }
   }  
   if($sql){
     $sql= $sql. "), decsUpdated=NOW() WHERE id=".$row[0] ;
     //echo $sql."<BR>";
     $result1 = mysqli_query($link_decs, $sql);
     if (!$result1) {
       echo 'MySQL Error (result1 prefTerm): ' . mysqli_error($link_decs)."<BR>";
     }
     else
       $ret = 1;//se actualizo
   } 
 }   
 return $ret;
}
/* Obtiene label de la entidad  (Descriptor o Calificador)
 *
 * @param $table
 *   string: nombre de la tabla de la entidad
 * @param $entidad
 *   string: uri de la entidad
 * @param $len_pathEntidad
 *   string: ctdad de caracteres del camino en la uri de la entidad
 *
 * @return
 *   array of array: lista de label de la entidad con idiomas, o NULL si error al consultar la tabla  
 */
function get_label($table, $entidad, $len_pathEntidad){
  global $link_decs;
  
  $label = NULL;  
  $identifier = substr($entidad,$len_pathEntidad); 

  //quitar en infomed
  global $translated;
  if($table=='Descriptor' AND !in_array($identifier,$translated))
    return NULL;
    
  $result = mysqli_query($link_decs, "SELECT data->'$.label' FROM $table WHERE identifier='".$identifier."'");
  if (!$result) {
     echo 'MySQL Error (result $table): ' . mysqli_error($link_decs);
  }
  elseif(mysqli_num_rows($result)){
    $row1 = mysqli_fetch_array($result);
    $label = json_decode($row1[0],true);       
  }
  return $label;  
}


/* Obtiene Identificadores de los descriptores que tienen label con traducciones
 *
 *
 * @return
 *   array : lista de identificadors de los descriptores que tienen label con traducciones
 */
function get_descriptor_translated(){
  global $link_decs;
  
  $translated = array();  

  $result = mysqli_query($link_decs, "SELECT identifier FROM Descriptor WHERE JSON_LENGTH(data->'$.label')>1");
  if (!$result) {
     echo 'MySQL Error (result $table): ' . mysqli_error($link_decs);
  }
  while($row = mysqli_fetch_array($result)){
    $translated[] = $row[0];       
  }
  return $translated;  
}



/*
- Estas funciones de estan pensadas para adicionar los textos a datos de mesh q solo tienen 'en', 
para update de traducciones tener en cuenta q
- en add_texts_DoQ se procesan solo las entidades de Mesh, a las de decs tambien hay que actualizarles las traducciones
- en add_texts_DoQ, add_texts_DQP se procesan solo las q tienen label en un solo idioma para update quitar esta condicion
- en upd_prefTerm, upd_prefConcept se actualizan solo las q tienen label en un solo idioma para update quitar esta condicion
- actualizar texto en ingles con nueva importacion de mesh
*/
?>
