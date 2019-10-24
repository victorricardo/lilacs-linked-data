<?php
/**
 * @file
 * Recorre todos los ficheros json, lee su contenido, obtiene las entidades y las inserta en la tabla correspondiente.
 */

include_once("common.inc");
include_once(PATH_DECS_LD."db/conexionDB.php");
include_once(PATH_COMMON_LD."array2json.inc");

set_time_limit(0); 
$ini=$ini_j=time(); 

$len_pathMesh = strlen(URL_MESH);

//para no insertar repetidos
$arr_identifiers = array();
//$identifiers = getIdentifiers();//identificadores que existen en cada tabla

$date=@date('Y-m-d'); 
$f_log = PATH_LOGS."log_save_entities$date.txt";
file_put_contents($f_log, "Iniciando log: $date\n");

//151 ctdad de ficheros de entidades
for($j=0; $j<=151; $j++){
//for($j=105; $j<=151; $j++){
    $file = PATH_RDFCONVERT."entidades$j.json";
    
    echo "Procesando $file ";
    file_put_contents($f_log, "Procesando $file \n", FILE_APPEND | LOCK_EX);
    
    $content = file_get_contents($file);

    //estas urls se definen en context, en la descripcion del recurso solo queda el nombre de la propiedad
    $content = str_replace("http://id.nlm.nih.gov/mesh/vocab#", "", $content);
    $content = str_replace("http://www.w3.org/2001/XMLSchema#", "", $content);
    $content = str_replace("http://www.w3.org/2000/01/rdf-schema#", "", $content);
    
    //se convierte el json en array asociativo
    $entidades = json_decode($content,true);
    unset($content);
    
    $ctdad = count($entidades);
    echo "--- total de entidades: ".$ctdad;
    if(!$ctdad){
      echo "Error en json_decode file: $file";  
    }
    else{
      for ($i=0; $i<$ctdad; $i++){
        $entidad = $entidades[$i];
        $entidad['sameAs'] = $entidad['@id']; 
        $identifier = substr($entidad['@id'],$len_pathMesh);

        $type = $entidad['@type'][0];
        $hasType = 1;  //incluir campo type en el sql
        switch($type){
          case 'CheckTag':
          case 'GeographicalDescriptor':
          case 'PublicationType':
          case 'TopicalDescriptor':
          case 'Descriptor':
            $table = 'Descriptor';
            break;
          case 'SCR_Chemical':
          case 'SCR_Disease':
          case 'SCR_Protocol':
          case 'SupplementaryConceptRecord':
            $table = 'SupplementaryConceptRecord'; 
            break;
          case 'Qualifier':
          case 'Concept':
          case 'Term':
            $table = $type; 
            $hasType = 0;
            break;
          case 'AllowedDescriptorQualifierPair':
          case 'DisallowedDescriptorQualifierPair':
          case 'DescriptorQualifierPair':
            $table = 'DescriptorQualifierPair'; 
            break;
          case 'TreeNumber':
            $table = 'TreeNumber'; 
            $hasType = 0;
            
            //ver si es de los que tienen TreeNumber diferente en Mesh y Decs
            $treeNumberDecs = treeNumberMesh2Decs($identifier,$arr_treeN);
            if($treeNumberDecs){
              $identifier = $treeNumberDecs; 
              $entidad['label'][0]['@value'] = $treeNumberDecs;   
            //echo "treeNumberDecs:$treeNumberDecs<BR>";
            }
            //echo "identifier:$identifier<BR>";
            break;
        }
        if($identifier ){                                                      
          //if(!in_array($identifier, $identifiers[$table])){
            //si no existe el identifier en la tabla se inserta el registro 
            //uri de decs rdf 
            $entidad['@id'] = $decs_path[$table."s"]['path'] . $identifier;   //por ej: http://decs.sld.cu/Descriptors/D0000001
            //asigna uris de decs rdf a las propiedades de la entidad y simplifica los valores de otras propiedades
            $entidad = set_correct_values($entidad,$arr_treeN,$len_pathMesh);
            //convierte array en string valido para pasar por la funcion JSON_OBJECT
            $value = array_to_objStr($entidad,$link_decs);
            //echo "value: $value<BR>";
            
            if($hasType)
              $sql= "INSERT INTO $table (identifier, type, data, created, meshUpdated) VALUES ('$identifier', '$type', JSON_OBJECT($value), NOW(), NOW())"; 
            else
              $sql= "INSERT INTO $table (identifier, data, created, meshUpdated) VALUES ('$identifier', JSON_OBJECT($value), NOW(), NOW())"; 
            
            $result = mysqli_query($link_decs, $sql);
            if (!$result) {
                echo 'MySQL Error: ' . mysqli_error($link_decs)." $sql<BR>";
                file_put_contents($f_log, "\nMySQL Error: $sql\n", FILE_APPEND | LOCK_EX);
            }
            else{
              //se agrega identificador insertado en la tabla
              $arr_identifiers[$table][]=$identifier;  
              file_put_contents($f_log, "$identifier-$table\n ", FILE_APPEND | LOCK_EX);
            }  
          //}
        }
        else{
           echo "i=$i sin identifier:". print_r($entidad,true)."<BR>----------------------------<BR>"; 
        } 
      }  
    }
    unset($entidades);
    $total_j=(time()-$ini_j)/60;
    $ini_j = time();
    echo " tiempo entidades.$j: $total_j minutos<BR>";

    $total=(time()-$ini)/60;
    echo "Tiempo total: $total minutos<BR>";
}    

$k_tables = array_keys($arr_identifiers);
foreach($k_tables as $table){
  echo "En $table insertados:".count($arr_identifiers[$table])."<BR>";  
}      
$total=(time()-$ini)/60;
echo "Tiempo de ejecucion: $total minutos<BR>";     

/* Devuelve un arreglo con los identificadores que existen en cada tabla
 *
 * @return
 *   array of array: para cada tabla devuelve un array de los identificadores que contiene
 */
function getIdentifiers(){
  $tables = array('Descriptor','Qualifier','SupplementaryConceptRecord', 'Concept', 'Term', 'DescriptorQualifierPair', 'TreeNumber');
  $identifiers = array();
  foreach($tables as $table){
    $identifiers[$table] = array();  
    
    $sql1 = "SELECT identifier FROM $table";
    $result1 = mysqli_query($link_decs, $sql1);
    if (!$result1) {
      echo 'MySQL Error: ' . mysqli_error($link_decs);
    }
    else{
      while($row = mysqli_fetch_array($result1)){
        $identifiers{$table}[]=$row[0];
      }
    }
  }
  return $identifiers;
}

/* En los datos de la entidad corrige los valores de propiedades del tipo @id (uris) y para otras propiedades con type que se define
 * en context se simplifica su valor.
 * 
 * Cambia uris de mesh por uris de decs. En el caso de los calificadores hace conversion de treeNumber de mesh por treeNumber de decs. 
 * En propiedades que vienen con @type, @value, se asigna directamente @value a la propiedad.
 *
 * @param $entidad
 *   array of array: arreglo asociativo con todas las propiedades de la entidad y sus valores
 * @param $arr_treeN
 *   array: arreglo asociativo con la correspondencia e/ los TreeNumber de calificadores en DeCS y MeSH, Y** de MeSH y Q** de DeCS 
 * @param $len_pathMesh
 *   integer: longitud del path de las entidades en mesh 
 *
 * @return
 *   array of array: arreglo asociativo con todas las propiedades de la entidad con las correcciones a las propiedades 
 *                   con @type definido en context, como uris, fechas, ...  
 */
function set_correct_values($entidad, $arr_treeN, $len_pathMesh){
  global $decs_path;
  
  //se recorren las propiedades y segun su tipo se construye la uri de decs correspondiente
  foreach($entidad as $k => $v){//$k: propiedad, $v:valor de la propiedad
    $type_prop = '';
    switch ($k){
      case 'allowableQualifier':
      case 'broaderQualifier':
      case 'hasQualifier':
        $type_prop = 'Qualifiers';
        break;
      case 'broaderDescriptor':
      case 'hasDescriptor':
      case 'pharmacologicalAction':
      case 'seeAlso':
        $type_prop = 'Descriptors';
        break;
      case 'indexerConsiderAlso': //Descriptor o DescriptorQualifierPair, prop de SCR
      case 'mappedTo': //Descriptor o DescriptorQualifierPair
      case 'preferredMappedTo': //Descriptor o DescriptorQualifierPair
      case 'useInstead': //Descriptor o DescriptorQualifierPair
        $type_prop = 'Descriptor_DescriptorQualifierPair';
        break;
      case 'concept':
      case 'broaderConcept':
      case 'narrowerConcept':
      case 'relatedConcept':
      case 'preferredConcept':
        $type_prop = 'Concepts';
        break;
      case 'term':
      case 'preferredTerm':
        $type_prop = 'Terms';
        break;
      case 'treeNumber':
      case 'parentTreeNumber':
        $type_prop = 'TreeNumbers';
        break;
      //simplifica el valor de las sgtes propiedades ya q su tipo se define en context
      case 'dateCreated':
      case 'dateRevised':
      case 'dateEstablished':
      case 'frequency':
      case 'identifier':
        $entidad[$k] = $v[0]['@value'];
        break;
      case 'active':
        $entidad['active'] = ($v[0]['@value']=="true") ? "1" : "0";
        break;
      case 'registryNumber':
        if($v[0]['@value'] != 0)
          $entidad[$k] = $v[0]['@value'];
        else
          unset($entidad[$k]); 
        break;
      case 'relatedRegistryNumber':
        foreach($v as $ki => $vi){ //el valor de la propiedad $v es array, se recorren sus elementos
          $entidad[$k][$ki] = $vi['@value'];
        }
        break;
    }                            
    if($type_prop){//hay q reemplazar uris de mesh por uris de decs
      $uno = 0;
      if(in_array($k, array('preferredConcept','preferredTerm','hasDescriptor','hasQualifier','parentTreeNumber','useInstead'))){
        if(count($v)>1){
          echo "$k tiene mas de un elemento ".print_r($v,true)."<BR>"  ;
        }
        else
         $uno = 1; //el valor es un solo elemento, no se pone array
      }
      foreach($v as $ki => $vi){ //el valor de la propiedad $v es array, se recorren sus elementos
        if(isset($vi{'@id'}) AND strpos($vi{'@id'},URL_MESH) === 0){ //tiene uri de mesh 
          $identifier = substr($vi{'@id'},$len_pathMesh);

          $type_prop1 = '';
          if($type_prop=='Descriptor_DescriptorQualifierPair'){ 
            if(strlen($vi{'@id'})> $len_pathMesh+10)
              $type_prop1 = 'DescriptorQualifierPairs';
            else
              $type_prop1 = 'Descriptors';
          }elseif($type_prop=='TreeNumbers'){
             //echo "identifier:$identifier<BR>";
             $treeNumberDecs = treeNumberMesh2Decs($identifier,$arr_treeN);
             //echo "treeNumberDecs:$treeNumberDecs<BR>";
             if($treeNumberDecs){
               $identifier = $treeNumberDecs; 
               //echo "cambio identifier:$identifier<BR>";
             } 
          }
          $type_prop1 = $type_prop1 ? $type_prop1 : $type_prop;
          if($uno){
          //if(in_array($k, array('preferredConcept','preferredTerm','hasDescriptor','hasQualifier','useInstead'))){
            //estas propiedades tienen un solo valor, no son array
            $entidad[$k] = $decs_path[$type_prop1]['path'] . $identifier;
          }
          else 
            $entidad[$k][$ki] = $decs_path[$type_prop1]['path'] . $identifier;
        }
        else{
          echo "No tiene @id, $k:".print_r($v,true)."<BR>";   
        }
      }
    }    
  }                                                           
  return $entidad;  
}
?>
