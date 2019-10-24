<?php
/**
 * @file
 * Convierte entidad del DeCS al modelo de MeSH RDF, incluyendo traducciones, inserta entidad en su tabla (Descriptor o Qualifier) y 
 * en tablas relacionadas: treeNumber, Concept, Term, DescriptorQualifierPair 
 */
require_once("get_decs_data.inc");

set_time_limit(0); 


/**
 * Convierte  descriptor o calificador del DeCS al modelo de MeSH RDF y lo inserta en tablas correspondientes.
 * 
 * Se incluyen traducciones en propieddes de texto libre. 
 * Inserta decsriptor o calificador en su tabla. Los valores de las algunas propiedades se insertan en otras tablas:
 * en Concept el preferredConcept, en Term el preferredTerm, en TreeNumber los treeNumber, en DescriptorQualifierPair los 
 * allowableQualifier y disallowedQualifier
 *
 * @param $item
 *   Array asociativo: datos del descriptor o calificador devuelto por el servicio del decs
 * @param $lastId_type
 *   integer: valor del ultimo id de un descriptor o calificador
 * @param $lastId
 *   integer: valor del ultimo id general (lastId descriptores + lastId calificadores)
 * @param $type
 *   string: Tipo del item, Descriptor o Qualifier
 * @param $lang
 *   string: lenguaje de los textos ('es', 'en' o 'pt')
 * @param $f_log
 *   string: camino del fichero para guardar log
 *
 * @return
 *   array: con los valores de $lastId_type y $lastId que se actualizan con las inserciones realizadas
 */
function ins_decs_DoQ($item, $lastId_type, $lastId, $type, $lang, $f_log){
  global $link_decs, $decs_path;
  
  $ins_T=$ins_C=$ins_DoQ=0;
  
  $decsData = decs2decsRDF($item, $type, $lang, 1);  
  if($decsData){
    if($decsData['identifier'] AND $decsData['identifier'][0] != '_'){ //la entidad existe tambien en arbol de mesh
      $identifier = $decsData['identifier'];
      //treeNumber de mesh  
      $result = mysqli_query($link_decs, "SELECT id, data->'$.treeNumber', data->'$.allowableQualifier' FROM $type WHERE identifier='".$decsData['identifier']."'");
      if (!$result) {
         echo 'MySQL Error (1): ' . mysqli_error($link_decs) ."<BR>";
      }
      elseif(mysqli_num_rows($result)){
        file_put_contents($f_log, $decsData['identifier']. " - Existe en MeSH\n", FILE_APPEND | LOCK_EX);
          
        $row = mysqli_fetch_row($result);
        $treeN = json_decode($row[1],true);  //treeNumbers del descritor en mesh     
        
        //inserta datos de decs en tabla TreeNumber
        list($decs_TN, $decs_parentTN, $cuantos) = ins_TreeNumber($decsData['decsTreeNumber']);
        file_put_contents($f_log, "Agregados $cuantos treeNumber de DeCS en su tabla\n", FILE_APPEND | LOCK_EX);
        
        //Agregar datos de treeNumber de DeCS a entidad que proviene de MeSH 
        $treeN = array_merge($treeN, $decs_TN);//agrega los treeNumber de decs 
        $treeN_str = array_to_arrStr($treeN,$link_decs);

        $parentTN = '';
        if(count($decs_parentTN)){
          $parentTN_str = array_to_arrStr($decs_parentTN,$link_decs);
          $parentTN = ",'$.decsParentTN',JSON_ARRAY($parentTN_str)";//procesar cdo este migrado todo y buscar broaderDescriptor
        }                                                                                   
        //si entre los calificadores hay alguno propio de decs se agrega 
        $allowQ_str ='';
        if(isset($decsData['allowableQualifier'])){
          $allowableQ =json_decode($row[2],true);
          foreach($decsData['allowableQualifier'] as $q){
            if(isset($q['@id']) AND !in_array($q['@id'], $allowableQ)){
              echo "Calificador de decs en descriptor q esta en mesh: $identifier ". $q['@id'] ."<BR>";
              $allowableQ[] = $q['@id'];
              //Insertar en DQP   
              $ins = ins_decs_DQP($identifier, $decsData['label'], $q);
              if (!$ins){
                echo 'MySQL Error (insert DescriptorQualifierPair): ' . mysqli_error($link_decs)."<BR>";
                file_put_contents($f_log, "Error al Insertar $identifier".$q['identifier']."\n", FILE_APPEND | LOCK_EX);
              } 
              else{
                file_put_contents($f_log, "Insertado $identifier".$q['identifier']." (allowed)\n", FILE_APPEND | LOCK_EX);
              } 
            }
          }
          $allowQ_str = array_to_arrStr($allowableQ,$link_decs);
          $allowQ_str = ",'$.allowableQualifier',JSON_ARRAY($allowQ_str)";
        }
        //Insertar en DQP, con tipo disallowed, useInstead es textual (los disallowed no se guardan en Desciptor)
        if(isset($decsData['disallowedQualifier']) AND count($decsData['disallowedQualifier'])){  
          //se buscan los disallowed del descriptor en tabla DescriptorQualifierPair
          $descr_uri = $decs_path['Descriptors']['path']. $decsData['identifier']; 
          $result1 = mysqli_query($link_decs, "SELECT data->'$.hasQualifier' FROM DescriptorQualifierPair WHERE type= 'DisallowedDescriptorQualifierPair' AND data->'$.hasDescriptor'='".$descr_uri."'");
          if (!$result) {
             echo 'MySQL Error (1): ' . mysqli_error($link_decs) ."<BR>";
          }
          else{
            $disallowQ = array();  
            while($row1 = mysqli_fetch_row($result1)){
              $disallowQ[] = $row1[0];  
            }   
          }    
          //si en datos de decs hay algun calificador q no esta en la tabla se agrega a DQP
          foreach($decsData['disallowedQualifier'] as $q){
            if(isset($q['@id']) AND !in_array($q['@id'], $disallowQ)){
              echo "Calificador de decs en descriptor q esta en mesh: $identifier ". $q['@id'] ."<BR>";
              $ins = ins_decs_DQP($identifier, $decsData['label'], $q, 'DisallowedDescriptorQualifierPair');
              if (!$ins){
                echo 'MySQL Error (insert DescriptorQualifierPair): ' . mysqli_error($link_decs)."<BR>";
                file_put_contents($f_log, "Error al Insertar $identifier".$q['identifier']."\n", FILE_APPEND | LOCK_EX);
              } 
              else{
                file_put_contents($f_log, "Insertado $identifier".$q['identifier']." (disallowed)\n", FILE_APPEND | LOCK_EX);
              }  
            }
          }
        }
        //agregar treeNumbers y calificadores de decs a la entidad
        $sql = "UPDATE $type SET data = JSON_SET(data,'$.treeNumber',JSON_ARRAY($treeN_str) $parentTN $allowQ_str), decsUpdated=NOW() WHERE id=". $row[0];
        $result1 = mysqli_query($link_decs, $sql);
        if (!$result1) {
          echo 'MySQL Error (2): ' . mysqli_error($link_decs) ."<BR>";
        }
        else{
          file_put_contents($f_log, "Se actualizan datos de treeNumber en la entidad agregando los de DeCS\n", FILE_APPEND | LOCK_EX);
        }
      }
      else{
        echo "En $type no existe identificador: $identifier <BR>";  
        file_put_contents($f_log, "No existe $type con identificador: $identifier\n");
      }
    }    
    else {  //solo existe en decs
      //agregar textos en otros idiomas  
      $decsData = get_texts_other_lang($decsData,$type,$lang, 1);
      
      //generar identificador
      $prefix = $type[0];//primera letra D o Q
      $identifier = get_nextIdentifier($lastId_type, $prefix);
      $data['@id'] = $decs_path[$type.'s']['path']. $identifier;
      $data['identifier'] = $identifier;
      $data['@type'] = $prefix=='D' ? array('TopicalDescriptor') : array($type);
      $data['active']= "1";  
      $data['label'] = $decsData['label'];

      if(isset($decsData['allowableQualifier'])){
        foreach($decsData['allowableQualifier'] as $q){
          if(isset($q['@id'])){
            $data['allowableQualifier'][] = $q['@id'];
            //Insertar en DQP  (despues del insert del descriptor, si fue OK) 
          }
          else{
            $data['allowableQualifier'][] = $q['abbr'];  //ver como obtener sus datos con servicio del decs
            echo "Calificador q no existe en decs rdf: ". $q['abbr']. "<BR>";
            file_put_contents($f_log, "Calificador (allowed) propio del decs en $identifier:". $q['abbr'] ."\n", FILE_APPEND | LOCK_EX);
          }  
        }  
      }
      if(isset($decsData['annotation'])){
        $data['annotation'] = $decsData['annotation'];
      }
      if(isset($decsData['considerAlso'])){
        $data['considerAlso'] = $decsData['considerAlso'];
      }
      if(isset($decsData['seeAlso'])){
        $data['seeAlso'] = $decsData['seeAlso'];
      }
     
      //Insertar datos en TreeNumber
      list($decs_TN, $decs_parentTN, $cuantos) = ins_TreeNumber($decsData['decsTreeNumber']);
      file_put_contents($f_log, "Agregados $cuantos treeNumber de DeCS en su tabla\n", FILE_APPEND | LOCK_EX);
      
      $data['treeNumber'] = $decs_TN;
      if($decs_parentTN)
        $data['decsParentTN'] = $decs_parentTN; //procesar cdo este migrado todo y buscar broaderDescriptor
     
      //preferredTerm
      $term_identifier = get_nextIdentifier($lastId, 'T');
      $data['preferredTerm'] = $decs_path['Terms']['path']. $term_identifier;
      
      //preferredConcept
      $concept_identifier = get_nextIdentifier($lastId, 'M');
      $data['preferredConcept'] = $decs_path['Concepts']['path']. $concept_identifier;
      
      //Insertar Descriptor o Calificador en tabla correspondiente
      if($type=='Descriptor'){
        $data_str = array_to_objStr($data, $link_decs);
        $sql= "INSERT INTO Descriptor (identifier, type, data, created, decsUpdated) VALUES ('$identifier', 'TopicalDescriptor', JSON_OBJECT($data_str), NOW(),NOW())"; 
      }
      else{
        $data['orig_decsId'] = $decsData['orig_decsId']; //id original para gregarle abbr cdo se encuentre calif propio en un despcritor
        $data_str = array_to_objStr($data, $link_decs);
        $sql= "INSERT INTO Qualifier (identifier, data, created, decsUpdated) VALUES ('$identifier', JSON_OBJECT($data_str), NOW(),NOW())"; 
      }
      $result = mysqli_query($link_decs, $sql);
      if (!$result) {
        echo "MySQL Error (insert $type): " . mysqli_error($link_decs)."<BR>";
      } 
      else{ 
        $ins_DoQ=1;  
        echo "Insertada entidad $identifier<BR>";  
        file_put_contents($f_log, "Insertada entidad $identifier\n", FILE_APPEND | LOCK_EX);

        //Insertar datos en Term
        $altLabel = isset($decsData['altLabel']) ? $decsData['altLabel'] : NULL;        
        $ins_T = ins_prefTerm($term_identifier, $data, $altLabel);
        if($ins_T){
          echo "Insertado prefTerm $term_identifier<BR>";  
          file_put_contents($f_log, "Insertado prefTerm $term_identifier\n", FILE_APPEND | LOCK_EX);
        }
        else
          file_put_contents($f_log, "NO se inserto prefTerm $term_identifier\n", FILE_APPEND | LOCK_EX);

        //Insertar datos en Concept
        $scopeNote = isset($decsData['scopeNote']) ? $decsData['scopeNote'] : NULL;
        $ins_C = ins_prefConcept($concept_identifier, $data, $scopeNote);
        if($ins_C){
          echo "Insertado prefConcept $concept_identifier<BR>";  
          file_put_contents($f_log, "Insertado prefConcept $concept_identifier\n", FILE_APPEND | LOCK_EX);
        }
        else
          file_put_contents($f_log, "NO se inserto prefConcept $concept_identifier\n", FILE_APPEND | LOCK_EX);
      
        //Insertar en DQP, con tipo allowed
        if(isset($decsData['allowableQualifier'])){
          foreach($decsData['allowableQualifier'] as $q){
            if(isset($q['@id'])){
              //Insertar en DQP   
              $ins = ins_decs_DQP($identifier, $decsData['label'], $q);
              if (!$ins){
                echo 'MySQL Error (insert DescriptorQualifierPair): ' . mysqli_error($link_decs)."<BR>";
                file_put_contents($f_log, "Error al Insertar $identifier".$q['identifier']."\n", FILE_APPEND | LOCK_EX);
              } 
              else{
                file_put_contents($f_log, "Insertado $identifier".$q['identifier']." (allowed)\n", FILE_APPEND | LOCK_EX);
              } 
            }
          }  
        }
        //Insertar en DQP, con tipo disallowed, useInstead es textual (los disallowed no se guardan en Descriptor)
        if(isset($decsData['disallowedQualifier'])){  
          //$qualifiers = json_decode($decsData['disallowedQualifier'], true);  
          foreach($decsData['disallowedQualifier'] as $q){
            if(isset($q['@id'])){
              $ins = ins_decs_DQP($identifier, $decsData['label'], $q, 'DisallowedDescriptorQualifierPair');
              if (!$ins){
                echo 'MySQL Error (insert DescriptorQualifierPair): ' . mysqli_error($link_decs)."<BR>";
                file_put_contents($f_log, "Error al Insertar $identifier".$q['identifier']."\n", FILE_APPEND | LOCK_EX);
              } 
              else{
                file_put_contents($f_log, "Insertado $identifier".$q['identifier']." (disallowed)\n", FILE_APPEND | LOCK_EX);
              }
            }  
            else{
              echo "Calificador q no existe en decs rdf: ". $q['abbr']. "<BR>";
              file_put_contents($f_log, "Calificador (allowed) propio del decs en $identifier:". $q['abbr'] ."\n", FILE_APPEND | LOCK_EX);
            }  
          }
        }
      }
    }
  }  
  //no se inserto termino ni concept
  if(!$ins_T AND !$ins_C)
    $lastId -= 1;
  //no se inserto Descriptor o Qualifier
  if(!$ins_DoQ)
    $lastId_type -= 1;

  return array($lastId_type, $lastId);
}

/**
 * Asigna las propiedades de DescriptorQualifierPair a partir de las del descriptor y calificador y lo inserta en su tabla
 *
 * @param $descriptor
 *   string: identificador del descriptor
 * @param $labelDescriptor
 *   array: arreglo con los label de descriptor en los diferentes idiomas
 * @param $qualifier
 *   array asociativo: datos del calificador ('identifier','@id','label','useInstead')
 * @param $type
 *   string: Tipo del item, AllowedDescriptorQualifierPair o DisallowedDescriptorQualifierPair
 *
 * @return
 *   integer: 1 si se inserto y 0 sino
 */
function ins_decs_DQP($descriptor, $labelDescriptor, $qualifier, $type='AllowedDescriptorQualifierPair'){
  global $link_decs, $decs_path;
  
  $identifier = $descriptor . $qualifier['identifier'];
  $data['@id']= $decs_path['DescriptorQualifierPairs']['path']. $identifier;
  $data['@type']=$type;
  $data['hasDescriptor']=$decs_path['Descriptors']['path']. $descriptor;
  $data['hasQualifier']=$qualifier['@id'];
  
  $labelQualifier = json_decode($qualifier['label'],true);
  foreach($labelDescriptor as $valueD){
    foreach($labelQualifier as $valueQ){
      if($valueQ['@language'] == $valueD['@language']){
        $labelDQ[] = array('@value' => $valueD['@value'] .'/'. $valueQ['@value'], '@language' => $valueD['@language']);
        break;  
      }  
    }  
  }
  $data['label'] = $labelDQ;
  
  if($type=='DisallowedDescriptorQualifierPair'){
    $data['useInstead'] = $qualifier['useInstead'];
  }
  
  //Insertar DQP en tabla correspondiente
  $data_str = array_to_objStr($data, $link_decs);
  $sql= "INSERT INTO DescriptorQualifierPair (identifier, type, data, created, decsUpdated) VALUES ('$identifier', '$type', JSON_OBJECT($data_str), NOW(), NOW())"; 
  $result = mysqli_query($link_decs, $sql);
  if (!$result) {
    $ret = 0;  
    echo 'MySQL Error (insert DescriptorQualifierPair): ' . mysqli_error($link_decs)."<BR>";
  } 
  else{
    $ret = 1;  
    echo "Insertada entidad $identifier<BR>";  
  }
  return $ret;
}

/**
 * Genera identificador de entidades propias del decs, basandose en el ultimo nro utilizado y el tipo de entidad
 * Mantiene el formato y longitud de identificadores MeSH y agrega '_' al inicio. Ej: _D000025
 * 
 * @param $last
 *   integer: ultimo numero utilizado para insertar entidad de un tipo determinado
 * @param $type
 *   char: caracter q identifica el tipo de entidad: 'D' descriptor, 'Q' calificador, 'M' concept, 'T' term
 *
 * @return
 *   string: identificador de entidad con el formato y longitud de identificadores MeSH, con '_' al inicio. Ej: _D000025  
 */
function get_nextIdentifier($last, $type){
  $len = ($type=='M') ? 7 : 6;    
  $identifier = '_'. $type . str_pad($last, $len, "0", STR_PAD_LEFT);      
  return $identifier;  
}  

/**
 * Inserta los treeNumber de un descriptor o calificador y los inserta en la tabla TreeNumber
 * 
 * @param $decsTreeNumber
 *   array: identificadores de los treeNumbers asociados a la entidad 
 *
 * @return
 *   array of array: 0: (TN)  arreglo de las uris de los treeNumber de la entidad, 
 *                   1: (pTN) arreglo de los identificadores de los parentTreeNumber de la entidad y 
 *                   2: (ret) 0 si no se inserto, 1 si se inserto o esta en la tabla
 */
function ins_TreeNumber($decsTreeNumber){
  global $link_decs, $decs_path;
  
  $ret = 0;  
  $pTN = array();  
  foreach($decsTreeNumber as $decsTN){                 
    /*$result0 = mysqli_query($link_decs, "SELECT id, JSON_UNQUOTE(data->'$.parentTreeNumber') FROM TreeNumber WHERE identifier='".$decsTN['treeNumber']."'");
    if (!$result0) {
      echo 'MySQL Error (result0 ins_TreeNumber): ' . mysqli_error($link_decs)."<BR>";
    }
    elseif(!mysqli_num_rows($result0)){
      //si no existe lo inserta  
    */
      $uri = $decs_path['TreeNumbers']['path'] . $decsTN['treeNumber'];
    
      $entidad = array('@id' => $uri, '@type' => array('TreeNumber'), 'active' => "1", 
                     'label' =>  array("@value" => $decsTN['treeNumber'], "@language" => "en"));
      $tN[] = $uri;   
     
      if(isset($decsTN['parentTreeNumber'])){
        $uri_parentTN = $decs_path['TreeNumbers']['path'] . $decsTN['parentTreeNumber'];  
        $entidad['parentTreeNumber'] = $uri_parentTN;   
        $pTN[] = $uri_parentTN;  
      }
      $value = array_to_objStr($entidad,$link_decs);
    
      $sql = "INSERT INTO TreeNumber (identifier, data, created, decsUpdated) VALUES ('". $decsTN['treeNumber'] ."', JSON_OBJECT($value), NOW(), NOW())";
      $result = mysqli_query($link_decs, $sql);
      if (!$result) {
        echo 'MySQL Error (result ins_TreeNumber): ' . mysqli_error($link_decs)."<BR>";
      }
      else
        $ret = 1;//se inserto  
    /*}
    else{
      //existe en la tabla  
      echo "Ya existe TN: ".$decsTN['treeNumber']."<BR>";
      $ret=1;
      $tN[] = $decs_path['TreeNumbers']['path'] . $decsTN['treeNumber'];
      if(isset($decsTN['parentTreeNumber'])){
        $pTN[] = $decsTN['parentTreeNumber'];  
        $row = mysqli_fetch_row($result0);
        if($row[1] != $decs_path['TreeNumbers']['path'] . $decsTN['parentTreeNumber']){
          echo " pero con padre diferente, en tabla:".$row[1].", en datos decs:" . $decsTN['parentTreeNumber']. "<BR>" ;
        }
      }
    }*/

  }
  return array($tN, $pTN, $ret); 
}

/**
 * Asigna las propiedades de un Term a partir de las del descriptor o calificador y lo inserta en su tabla
 *
 * @param $identifier
 *   string: identificador del Term
 * @param $dataDoQ
 *   array: datos del descriptor o calificador
 * @param $altLabel
 *   array: valor de la propiedad altLabel del termino
 *
 * @return
 *   integer: 1 si se inserto y 0 sino
 */
function ins_prefTerm($identifier, $dataDoQ, $altLabel){
 global $link_decs;

 $ret = 0;
 $data['@id'] = $dataDoQ['preferredTerm']; 
 $data['@type'] = array('Term'); 
 $data['identifier'] = $identifier; 
 $data['prefLabel'] = $dataDoQ['label']; 
 $data['active'] = $dataDoQ['active'];
 
 if($altLabel)
   $data['altLabel'] = $altLabel; 

 $data_str = array_to_objStr($data,$link_decs);
 $sql= "INSERT INTO Term (identifier, data, created, decsUpdated) VALUES ('$identifier', JSON_OBJECT($data_str), NOW(), NOW())"; 
 $result = mysqli_query($link_decs, $sql);
 if (!$result) {
   echo 'MySQL Error (result ins_prefTerm): ' . mysqli_error($link_decs)."<BR>";
 }
 else
   $ret = 1;//se actualizo

 return $ret;   
}

/**
 * Asigna las propiedades de un Concepto a partir de las del descriptor o calificador y lo inserta en su tabla
 *
 * @param $identifier
 *   string: identificador del Concept
 * @param $dataDoQ
 *   array: datos del descriptor o calificador
 * @param $scopeNote
 *   array: valor de la propiedad scopeNote del termino
 *
 * @return
 *   integer: 1 si se inserto y 0 sino
 */
function ins_prefConcept($identifier, $dataDoQ, $scopeNote){
 global $link_decs;

 $ret = 0;
 $data['@id'] = $dataDoQ['preferredConcept']; 
 $data['@type'] = array('Concept'); 
 $data['identifier'] = $identifier; 
 $data['label'] = $dataDoQ['label']; 
 $data['active'] = $dataDoQ['active'];
 $data['preferredTerm'] = $dataDoQ['preferredTerm'];
 
 if($scopeNote)
   $data['scopeNote'] = $scopeNote; 

 $data_str = array_to_objStr($data,$link_decs);
 $sql= "INSERT INTO Concept (identifier, data, created, decsUpdated) VALUES ('$identifier', JSON_OBJECT($data_str), NOW(), NOW())"; 
 $result = mysqli_query($link_decs, $sql);
 if (!$result) {
   echo 'MySQL Error (result ins_prefConcept): ' . mysqli_error($link_decs)."<BR>";
 }
 else
   $ret = 1;//se actualizo

 return $ret;   
}

?>
