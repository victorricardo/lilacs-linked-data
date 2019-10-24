<?php
include("tmp2entity.php");
require_once(PATH_LILACS_LD."db/commonDB.php");

//PENDIENTE: consultas con capa BD

set_time_limit(0); 
$ini=time(); 
//pasar los datos validados a tablas de entidades
update_ValidTmp($link);

//Analiza los registros de las tablas definitivas y en cada campo que sea entidad trata de asignar datos enlazados  
//OJO!!! FALTA si tiene id actualizar los datos dependiendo de fecha de actualizacion
$entidades = array(/*'organization', 'person', 'event', 'project', */'document');
echo "<BR>Actualizando Datos Enlazados<BR><BR>";
foreach($entidades as $entity){
  updateEntityLD($entity, $link);
}
$total=(time()-$ini)/60;
echo "Tiempo de ejecucion: $total minutos<BR>";

function updateEntityLD($table, $link){
  $upd = $err = $ii= 0;  
  $t1=time();

  $count = getCount($link, 'document');
  $ctdad = intval($count/1000);
  $upd=0;
  for($i=0;$i<=$ctdad;$i++){
    $start = $i*1000;

    $sql = "SELECT id, data FROM $table LIMIT $start,1000";  
    //$sql = "SELECT id, data FROM $table where id = 2";  
    $result = mysqli_query($link, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
      $data_arr = json_decode($row['data'],true);

      list($u, $e) = updateEntityLDbyId($table, $data_arr, $row['id'], $link);  
      $upd += $u;
      if($err){
        $err += $e;
        echo "Error asignando datos enlazados en $table id: ".$row['id'];
      }

      $ii++;
      //$t1=(time()-$t1); 
      echo "Registro $ii <BR>";
      //break;     //descomentar para probar solamente en el primer registro
    }
    mysqli_free_result($result);
  }  
  echo "En $table se actualizaron $upd registros<BR>";
  
  if($err)
    echo ", no se pudieron actualizar $err registros por errores ocurridos";
  echo "<BR>";    

}
function updateEntityLDbyId($table, $data_arr, $id, $link){
    $upd=$err=0;

    $objectLD = putObjLD($data_arr, $id, $link); 
    $countLD = count($objectLD);

    if(!isset($data_arr['@id'])){
      //Agregar '@id' de entidad en data
      $entityID = array('@id'=> URL_LILACS_LD.$table."s/".$id, 'id'=>$id);
      if($countLD)
        $objectLD = array_merge($entityID, $objectLD);
      else  
        $objectLD = $entityID;
      $countLD += 1;  
    }

    if($countLD){  
      $r = _updateEntityDB($table, $id, $objectLD, 1);  
      if($r)
        $upd = 1;
      else
        $err = 1;  
    }
    return array($upd,$err);
}
/* Recorre elementos de un array asociativo, si el elemento es una entidad (person, organization, document) intenta asignar datos enlazados
 *
 * @param $object
 *   array: arreglo asociativo
 * @param $link
 *   identificador de la base de datos activa (para realizar la consulta)
 *
 * @return
 *   array: arreglo asociativo con elementos a los que se asignaron datos enlazados
 *   NULL: si a ninguno de los elementos de object se le asignaron datos enlazados
 */
function putObjLD($object, $id_doc, $link){
  $objLD = array();  

  foreach($object as $key => $value){
    $upd = 0;
    if(is_array($value)){
      if(is_assoc_array($value)){ //un objeto
        $valueLD = putValueLD($value, $id_doc, $link);  
      }
      else{ //arreglo indice numerico
        $valueLD = array();  
        foreach($value as $assoc_value){
          if(is_array($assoc_value) AND is_assoc_array($assoc_value) ){
            $vLD = putValueLD($assoc_value, $id_doc, $link);  
            if($vLD){
              $valueLD[] = $vLD;
              $upd = 1;
            }
            else{
              $valueLD[] = $assoc_value;
            }
          } 
        }
        if(!$upd)  
          $valueLD = NULL;
      }
      if($valueLD){
        $objLD[$key] = $valueLD;
      }
    }
  }
  if(count($objLD))
    return $objLD;
  else
    return NULL;  
    
}

/* Analiza un valor de una propiedad si es una entidad y no se le ha asignado @id (no esta enlazado), se trata de asignar, A la vez 
 * se recorren todos sus elementos y si alguno es entidad se trata d asignar datos enlazados.    
 *
 * @param $value
 *   array: arreglo asociativo
 * @param $link
 *   identificador de la base de datos activa (para realizar la consulta)
 *
 * @return
 *   array: arreglo asociativo, del mismo valor de entrada con datos enlazados
 *   NULL: si no se le asignaron datos enlazados a value, ni a ninguno de sus elementos 
 */
function putValueLD($value, $id_doc, $link){
   $valueLD = array();
   $upd = 0; 
  //si value es una entidad y no tiene asignado id
  //OJO!!! considerar que tenga id y que haya q actualizar por diferencias en modified 
  if(isset($value['@type']) AND !isset($value['@id']) AND (isset($value['name']) OR isset($value['branchCode']))){
    switch($value['@type']){
      case 'Organization':
      case 'Person':
      case 'Event':
      case 'Project':
        $entity=value_asEntity($value, $id_doc, $link);   
        break;
      case 'PostalAddress':
      case 'PublicationEvent':
        break;
      case 'Topic':
      case 'CheckTag':
      case 'TopicalDescriptor':
      case 'PublicationType':
      case 'DescriptorQualifierPair':
        break;
      default: //los difrentes type de documento
        $entity=value_asDocument($value, $id_doc, $link);   
        break; 
    }  
    if(isset($entity)){
      $diff = array_diff_assoc_recursive($entity,$value);
      if($diff){  
        $valueLD = $entity;
        $upd = 1;  
      }    
    }
  }
  //Los elem de value que son entidad se analizan y si se les pudo asignar id se devuelven en valueLD
  //Ej: value es del tipo Person y su campo affiliation es Organization 
  //$elem_valueLD tiene solo aquellos indices a los que se agrego id
  $elem_valueLD = putObjLD($value, $id_doc, $link);
  if($elem_valueLD){
    $upd = 1;  
    foreach($elem_valueLD as $k => $v){
      $valueLD[$k] = $v;
    }
  }
  if($upd){
    return $valueLD;  
  }
  else
    return NULL;
}

/* Calcula las diferencias entre dos arreglos teniendo en cuenta sus llaves y entrando en todos los niveles de los array  
 *
 * @param $array1
 *   array: arreglo asociativo a comparar
 * @param $array2
 *   array: arreglo asociativo a comparar
 *
 * @return
 *   array: arreglo asociativo, con las diferencias, o 0 si son iguales
 */
function array_diff_assoc_recursive($array1, $array2){
  foreach($array1 as $key => $value) {
    if(is_array($value)){
      if(!isset($array2[$key])){
        $difference[$key] = $value;
      }
      elseif(!is_array($array2[$key])){
        $difference[$key] = $value;
      }
      else{
        $new_diff = array_diff_assoc_recursive($value, $array2[$key]);
        if($new_diff != FALSE){
          $difference[$key] = $new_diff;
        }
      }
    }
    elseif(!isset($array2[$key]) || $array2[$key] != $value){
      $difference[$key] = $value;
    }
  }
  return !isset($difference) ? 0 : $difference;
}

/**
 * Agrega datos enlazados de documento si este existe en la tabla Document, sino lo agrega a tabla temporal tmp_document
 *
 * @param $isPartOf
 *   array asociativo: Datos de documento del q es parte el document (niveles superiores)
 *   Ej: array{"name"=>{"@value"=>"Rev. cuba. cir", "@language"=>"es"}, "@type"=>"Periodical", "levelOfTreatment"=>"s", "typeOfLiterature"=>"S"}
 * @param $in_document
 *   integer identificador (id) del documento donde se hace referencia a la institucion (nmbre de campo, valor))
 * @param $link
 *   identificador de la base de datos activa (para realizar la consulta)
 *
 * @return
 *   array asociativo: con datos de document, si se encontro en la tabla se agregan llaves '@id' (url datos enlazados) , 'id', sino se devuelve igual 
 *   Ej: array{"@id"=>"http://lilacs.sld.cu/entity/document/5", "id"=>5, "name"=>{"@value"=>"Rev. cuba. cir", "@language"=>"es"}, "@type"=>"Periodical", "levelOfTreatment"=>"s", "typeOfLiterature"=>"S"}
 */
function value_asDocument($isPartOf, $in_document, $link){
 if(isset($isPartOf['name'])) {
   if($isPartOf['@type']=='Periodical' AND (isset($isPartOf['volumeNumber']) OR isset($isPartOf['issueNumber'])) ){//desglosar en PublicationVolume, PublicationIssue
      $serie = array_diff_key($isPartOf, array('volumeNumber'=>1, 'issueNumber'=>1));
      //asignar uri si existe, sino guardar en temporal
      $serie = setUriIsPartOf($serie, $in_document, $link);
      
      $serie1  = array_diff_key($serie, array('typeOfLiterature'=>1, 'levelOfTreatment'=>1, 'dataBase'=>1));
      $volume = array('name'=>$isPartOf['name'], '@type' => 'PublicationVolume', 'volumeNumber'=>$isPartOf['volumeNumber'], 'database'=>$isPartOf['database'], 'isPartOf'=>$serie1);
      //asignar uri si existe, sino guardar en temporal
      $volume = setUriIsPartOf($volume, $in_document, $link);

      if(isset($isPartOf['issueNumber'])){
        $volume1  = array_diff_key($volume, array('isPartOf'=>1, 'database'=>1));
        $issue = array('name'=>$isPartOf['name'], '@type' => 'PublicationIssue', 'issueNumber'=>$isPartOf['issueNumber'], 'database'=>$isPartOf['database'], 'isPartOf'=>$volume1);
        //asignar uri si existe, sino guardar en temporal
        $isPartOf = setUriIsPartOf($issue, $in_document, $link);//el doc es parte del issue, el issue del vol y el vol de la serie
      }
      else{
        $isPartOf = $volume; //no hay issue, el doc es parte del vol  
      }
   }
   else{    
     $isPartOf = setUriIsPartOf($isPartOf, $in_document, $link);  
   }  
 }
 return $isPartOf;
}

function setUriIsPartOf($isPartOf, $in_document, $link){

 $json_str = array_to_objStr($isPartOf, $link);    
 $name = '';
 $id=0;
 
 //buscar id del document en tabla document
 $sql1 = "SELECT id, data FROM document WHERE JSON_CONTAINS(data, JSON_OBJECT($json_str))";  
 $res1 = mysqli_query($link, $sql1);
 if (!$res1) {
   printf("SQL is %s!<BR>", $sql1);
   echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
 }    
 elseif (mysqli_num_rows($res1)) {
   $ret1 = mysqli_fetch_array($res1);
   $id = $ret1[0]; //id
   $doc = json_decode($ret1[1],true);  
   $name = $doc['name']; //name de la tabla que es el mas completo
 }
 else{
   //el doc no esta en Document se chequea en temporal, se devuelve id y name por si es un same as, 
   //si no esta en temporal se inserta
   $id_name = in_temporal('tmp_document', $isPartOf, $in_document, $link);  
   if($id_name){
     list($id,$name) = $id_name;  
   }  
 }
 if($name){
   $isPartOf['name'] = $name;  //name del same as, que es el correcto
 }
 if($id){
   $isPartOf['id'] = $id;  
   $isPartOf['@id'] = URL_LILACS_LD.'documents/'.$id;  
 }
 
 return $isPartOf;
}

/**
 * Agrega datos enlazados de entidad si los datos de value existen en la tabla definitiva, sino lo busca en la tabla temporal, si tampoco existe 
 * lo inserta, si exist en tmp y tiene id de entidad asociado se agrega como datos enlazados.
 * 
 * @param $value
 *   array asociativo: con llaves (name o branchCode, @type, ... )
 *   Ej: array('name'=>"Biblioteca Médica Nacional", '@type'=>'Organization') o array('branchCode'=>"CU1.1", '@type'=>'Organization')
 *       array("@type"=>"Person", "name"=>"Hernandez Vergel, Lazaro Luis", "affiliation"=>array("Infomed","Hospital Calixto García"))
 * @param $in_document
 *   integer identificador (id) del documento donde se hace referencia a la institucion (nmbre de campo, valor))
 * @param $link
 *   identificador de la base de datos activa (para realizar la consulta)
 *
 * @return
 *   array asociativo: con datos de entidad, si se encontro en la tabla se agregan llaves '@id' (url datos enlazados) , 'id' 
 */
function value_asEntity($value, $in_document, $link){
  $str_entity = strtolower($value['@type']);

  //buscar id de la entidad en tabla definitiva
  //OJO!!! se asume name como string, cdo sea obj con @value, @languaje no va a funcionar (ver como se hace en value_asDocument)
  if(isset($value['name'])){   
    $json_str = array_to_objStr($value['name'], $link);;
  }
  if($value['@type'] == 'Organization'){
      if(isset($value['branchCode'])){
        //se usa ->> para que el resultado no tenga comillas  
        $sql1 = 'SELECT id, data->>"$.branchCode", data->"$.name" FROM organization WHERE data->"$.branchCode" = "'.$value['branchCode'].'"';  
      }  
      else{
        $sql1 = "SELECT id, data->>'$.branchCode', data->'$.name' FROM organization WHERE JSON_CONTAINS(data->'$.name', JSON_OBJECT($json_str))";  
        //$sql1 = 'SELECT id, data->>"$.branchCode", data->>"$.name" FROM organization WHERE data->"$.name" = "'.mysqli_real_escape_string($link,$value['name']).'"';  
      }
   }
   else{
      $sql1 = "SELECT id FROM $str_entity WHERE JSON_CONTAINS(data->'$.name', JSON_OBJECT($json_str))";  
      //$sql1 = "SELECT id FROM $str_entity WHERE data->'$.name' = '".mysqli_real_escape_string($link,$value['name'])."'";  
  }
  $res1 = mysqli_query($link, $sql1);
  if (mysqli_num_rows($res1)) {
    $ret1 = mysqli_fetch_array($res1);
    $id = $ret1[0]; //id                                
    $entity = array('@id'=>URL_LILACS_LD. $str_entity. 's/'.$id, 'id'=>$id);

    if($value['@type']=='Organization'){
      if(isset($value['branchCode'])){
        if($ret1[2]){//name tiene valor en la tabla
          $name_arr=json_decode($ret1[2],1);
          if( isset($value['name']) AND $value['name']!=$name_arr){//no coincide name en la tabla con el de value
          //if( isset($value['name']) AND $value['name']!=$ret1[2]){//no coincide name en la tabla con el de value
            $entity['same_as'] = $value['name'];
          }  
          if(!isset($value['name'])){
            $entity['name'] = $name_arr;//name esta lleno y branchCode tambien, se devuelven ambos 
          } 
        }
      }    
      elseif($ret1[1]){//name esta lleno y branchCode tambien, se devuelven ambos 
        $entity['branchCode'] = $ret1[1];
      }
    }
  }
  else{
    //nombre de la entidad no esta en tabla definitiva, se chequea en temporal
    $id_name = in_temporal('tmp_'. $str_entity, $value, $in_document, $link);  
    if($id_name){//esta en tmp
      list($id,$name) = $id_name;  
      if($id){
        //se construyen datos enlazados (@id, ...)
        $entity = array('@id'=>URL_LILACS_LD. $str_entity. 's/'.$id, 'id'=>$id);
      }
      $value['name'] = $name;  //name del same as, que es el correcto
    }  
  }
    
  // se agregan los datos de entity
  $entity = isset($entity) ? array_merge($entity, $value) : $value;

  return $entity;
}
/**
 * Chequea la existencia de tmp_obj en tabla temporal. 
 * 
 * Si tmp_obj no existe se inserta. 
 * Si existe como alternateName o tiene id_entity se devuelven id y name de tabla definitiva (LD) (hacer merge con el definitivo?)
 * Si no tiene id_entity se hace un merge del existente con tmp_obj. 
 * Si existe en tmp, y proviene de un doc diferente se actualiza in_document 
 *
 * @param $tmp_table
 *   string: nombre de la tabla temporal donde insertar los datos  (tmp_person, tmp_organization, ...)
 * @param $tmp_obj
 *   array asociativo con datos de la entidad  a chequear (persona, organizacion, documento )
 * @param $in_document
 *   integer identificador (id) del documento donde se hace referencia a la entidad (nmbre de campo, valor))
 * @param $link
 *   identificador de la base de datos activa (para realizar la consulta)
 *
 * @return array: id y name de tabla definitiva, si tmp_obj existe en tabla temporal como alternateName o tiene id_entity 
 *                sino NULL 
 */
function in_temporal($tmp_table, $tmp_obj, $in_document, $link){
  $json_str = array_to_objStr($tmp_obj, $link);        
  $entity = substr($tmp_table,4);
  $id_entity = "id_".$entity;
  //$ret_arr = array();
   
  //si $json_str es valida para pasar por JSON_OBJECT()
  if(!json_argument_error($json_str,$link)){
    //chequear si existe en tmp_*** por name o branchCode
    if($entity == "organization" AND !isset($tmp_obj['name']) AND isset($tmp_obj['branchCode'])){
      $key = 'branchCode';
    }
    else{
      $key = 'name';
    }
     
    if(is_array($tmp_obj[$key])){
      if(!is_assoc_array($tmp_obj[$key])){
        $json_name = array_to_arrStr($tmp_obj[$key], $link);
        $search = "JSON_ARRAY($json_name)";
      }
      else{
        $json_name = array_to_objStr($tmp_obj[$key], $link);
        $search = "JSON_OBJECT($json_name)";
      }
      $sql = "SELECT id, $id_entity, sameAs, data FROM $tmp_table WHERE (JSON_CONTAINS(data->'$.name', $search) OR JSON_CONTAINS(data->'$.alternateName', $search))";  
    }
    else{
      if(is_numeric($tmp_obj[$key]))
        $sql = "SELECT id, $id_entity, sameAs, data FROM $tmp_table WHERE (data->'$.name' = ". $tmp_obj[$key] ." OR data->'$.alternateName' = ". $tmp_obj[$key] .")";  
      else{
        if($key == 'name'){
          $name = mysqli_real_escape_string($link,$tmp_obj[$key]); 
          //$name = $tmp_obj['name']; 
          $sql = "SELECT id, $id_entity, sameAs, data FROM $tmp_table WHERE (data->'$.name' = '". $name ."' OR data->'$.alternateName' = '". $name ."')";  
        } 
        else{ //solo aqui hay la posibilidad de branchCode que es string
          $sql = "SELECT id, $id_entity, sameAs, data FROM $tmp_table WHERE data->'$.branchCode' = '". $tmp_obj[$key] ."'";  
        } 
      }
    }
    switch($tmp_obj['@type']){//chequear @type too, cdo son periodical se mantiene name en la serie, vol y issue
      case 'Periodical':
      $sql .= " AND data->'$.\"@type\"' ='". $tmp_obj['@type'] ."'";    
        break;  
      case 'PublicationVolume':
        $sql .= " AND data->'$.\"@type\"' ='". $tmp_obj['@type'] ."' AND data->'$.volumeNumber' =". $tmp_obj['volumeNumber'];          break;  
      case 'PublicationIssue':
        $sql .= " AND data->'$.\"@type\"' ='". $tmp_obj['@type'] ."' AND data->'$.issueNumber' =". $tmp_obj['issueNumber'] ."
          AND data->'$.isPartOf.volumeNumber' =". $tmp_obj['isPartOf']['volumeNumber'];          
        break;  
      default:
        break;  
    }
    $res = mysqli_query($link, $sql);
    if (!$res) {
      printf("SQL is %s!<BR>", $sql);
      echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
    }    
    elseif (mysqli_num_rows($res)) {//existe en tmp_***
      $ret1 = mysqli_fetch_array($res);
      if($ret1[$id_entity]){//no esta n tabla definitiva y tine id_entity => es sameAs  o id_entity se puso manualmente en la temporal 
        $sql = "SELECT data FROM $entity WHERE id =". $ret1[$id_entity];  
        $res = mysqli_query($link, $sql);
        if($res){
          $ret2 = mysqli_fetch_array($res);
          $data = json_decode($ret2[0],1);
          //se devuelve $id_entity y name de tabla de entidad
          $ret_arr = array($ret1[$id_entity], $data['name']);//debe existir 'name' en la definitiva
        }    
      }
      else{//si existe en tmp y no tiene id_entity
        $data = json_decode($ret1['data'],1);
        $sql = "SELECT id FROM $tmp_table WHERE id =". $ret1['id']. " AND !JSON_CONTAINS(data, JSON_OBJECT($json_str))";  
        $res = mysqli_query($link, $sql);
        if (!$res) {
          printf("SQL is %s!<BR>", $sql);
          echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
        }    
        elseif(mysqli_num_rows($res))  {
          $id_new_data = $ret1['id']; //si los datos a chequear (tmp_obj) no estan incluidos en data de la tabla, se marcan para hacer merge 
          //se unen datos de la entidad existente con los de tmp_obj
          $new_data = array_merge_distinct($data,$tmp_obj);
          $json_str_new = array_to_objStr($new_data, $link);        
          $sql= "UPDATE $tmp_table SET data = JSON_OBJECT($json_str_new), modified = NOW() WHERE id=". $id_new_data; 
          $res = mysqli_query($link, $sql);
          if (!$res) {
            printf("SQL is %s!<BR>", $sql);
            echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
          }
        }
      }

      if($in_document){      
        //chequear si in_document ya esta incluido sino se agrega
        $sql = "SELECT id FROM $tmp_table WHERE id =". $ret1['id']. " AND !JSON_CONTAINS(in_document, '$in_document')";  
        $res = mysqli_query($link, $sql);
        if (!$res) {
          printf("SQL is %s!<BR>", $sql);
          echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
        }
        elseif (mysqli_num_rows($res)) {
          $sql= "UPDATE $tmp_table SET in_document = JSON_ARRAY_APPEND(in_document, '$', $in_document), modified = NOW() WHERE id=". $ret1['id']; 
          $res = mysqli_query($link, $sql);
          if (!$res) {
            printf("SQL is %s!<BR>", $sql);
            echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
          }
        }   
      }  
    }
    else{//si no existe en tmp_  se inserta
      if($in_document)
        $sql= "INSERT INTO $tmp_table SET data= JSON_OBJECT($json_str), in_document = JSON_ARRAY($in_document), created = NOW()" ; 
      else
        $sql= "INSERT INTO $tmp_table SET data= JSON_OBJECT($json_str), modified = NOW()"; 
      $res = mysqli_query($link, $sql);
      if (!$res) {
        printf("SQL is %s!<BR>", $sql);
        echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
      }
    } 
  }

  if(isset($ret_arr)){
    return $ret_arr;  
  }
  else
    return NULL;
}
/* chequear datos enlazados en document
* al asignar datos enlazados dejar solo @id, @type, name, branchcode
* en is_valid: 0 no se ha validado (nuevo), 
*               1 validado y OK, 
*              -1 validado y error 
* 
* actualizar entidad si existe id y fecha de modificacion del documento mayor que la de la entidad, 
* definir flujo de actualizacion, principalmente si la entidad isValid 
* 
* marcar 
*/


/*function in_temporal($tmp_table, $tmp_obj, $in_document, $link){
  $json_str = array_to_objStr($tmp_obj, $link);        
  $entity = substr($tmp_table,4);
  $id_entity = "id_".$entity;
  //$ret_arr = array();
   
  //si $json_str es valida para pasar por JSON_OBJECT()
  if(!json_argument_error($json_str,$link)){
    //chequear si existe en tmp_*** por name o branchCode
      
    if(is_array($tmp_obj['name'])){
      if(!is_assoc_array($tmp_obj['name'])){
//print_r($tmp_obj['name']);
        $json_name = array_to_arrStr($tmp_obj['name'], $link);
        $sql = "SELECT id, $id_entity, sameAs, data FROM $tmp_table WHERE JSON_CONTAINS(data->'$.name', JSON_ARRAY($json_name)) OR JSON_CONTAINS(data->'$.alternateName', JSON_ARRAY($json_name))";  
      }
      else{
        $json_name = array_to_objStr($tmp_obj['name'], $link);
        $sql = "SELECT id, $id_entity, sameAs, data FROM $tmp_table WHERE JSON_CONTAINS(data->'$.name', JSON_OBJECT($json_name)) OR JSON_CONTAINS(data->'$.alternateName', JSON_OBJECT($json_name))";  
      }
    }
    else{
      if(is_numeric($tmp_obj['name']))
        $sql = "SELECT id, $id_entity, sameAs, data FROM $tmp_table WHERE data->'$.name' = ". $tmp_obj['name'] ." OR data->'$.alternateName' = ". $tmp_obj['name'];  
      else{
        $name = mysqli_real_escape_string($link,$tmp_obj['name']); 
        //$name = $tmp_obj['name']; 
        $sql = "SELECT id, $id_entity, sameAs, data FROM $tmp_table WHERE data->'$.name' = '". $name ."' OR data->'$.alternateName' = '". $name ."'";  
      }
    }
    $res = mysqli_query($link, $sql);
    if (!$res) {
      printf("SQL is %s!<BR>", $sql);
      echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
    }    
    elseif (mysqli_num_rows($res)) {//existe en tmp_***
      $ret1 = mysqli_fetch_array($res);
      if($ret1[$id_entity]){//no esta n tabla definitiva y tine id_entity => es sameAs  o id_entity se puso manualmente en la temporal 
        //se devuelve $id_entity y name de tabla de entidad
        //$field = $entity=='document' ? 'document' : 'data';
        $sql = "SELECT data FROM $entity WHERE id =". $ret1[$id_entity];  
        $res = mysqli_query($link, $sql);
        if($res){
          $ret2 = mysqli_fetch_array($res);
          $data = json_decode($ret2[0],1);
          $ret_arr = array($ret1[$id_entity], $data['name']);
        }    
      }
      else{//si existe en tmp y no tiene id_entity
        $data = json_decode($ret1['data'],1);
        $sql = "SELECT id FROM $tmp_table WHERE id =". $ret1['id']. " AND !JSON_CONTAINS(data, JSON_OBJECT($json_str))";  
        $res = mysqli_query($link, $sql);
        if (!$res) {
          printf("SQL is %s!<BR>", $sql);
          echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
        }    
        elseif(mysqli_num_rows($res))  {
          $id_new_data = $ret1['id']; //si los datos a chequear (tmp_obj) no estan incluidos en data de la tabla, se marcan para hacer merge 
          //se unen datos de la entidad existente con los de tmp_obj
          $new_data = array_merge_distinct($data,$tmp_obj);
          $json_str_new = array_to_objStr($new_data, $link);        
          $sql= "UPDATE $tmp_table SET data = JSON_OBJECT($json_str_new), modified = NOW() WHERE id=". $id_new_data; 
          $res = mysqli_query($link, $sql);
          if (!$res) {
            printf("SQL is %s!<BR>", $sql);
            echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
          }
        }
      }
            
      if($in_document){      
        //chequear si in_document ya esta incluido sino se agrega
        $sql = "SELECT id FROM $tmp_table WHERE id =". $ret1['id']. " AND !JSON_CONTAINS(in_document, '$in_document')";  
        $res = mysqli_query($link, $sql);
        if (!$res) {
          printf("SQL is %s!<BR>", $sql);
          echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
        }
        elseif (mysqli_num_rows($res)) {
          $sql= "UPDATE $tmp_table SET in_document = JSON_ARRAY_APPEND(in_document, '$', $in_document), modified = NOW() WHERE id=". $ret1['id']; 
          $res = mysqli_query($link, $sql);
          if (!$res) {
            printf("SQL is %s!<BR>", $sql);
            echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
          }
        }   
      }  
    }
    else{//si no existe en tmp_  se inserta
      if($in_document)
        $sql= "INSERT INTO $tmp_table SET data= JSON_OBJECT($json_str), in_document = JSON_ARRAY($in_document), modified = NOW()" ; 
      else
        $sql= "INSERT INTO $tmp_table SET data= JSON_OBJECT($json_str), modified = NOW()"; 
      $res = mysqli_query($link, $sql);
      if (!$res) {
        printf("SQL is %s!<BR>", $sql);
        echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
      }
    } 
  }

  if(isset($ret_arr)){
    return $ret_arr;  
  }
  else
    return NULL;
}*/

?>

