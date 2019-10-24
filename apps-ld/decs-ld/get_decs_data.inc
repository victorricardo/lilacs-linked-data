<?php
/**
 * @file
 * Contiene las funciones para conectarse al servicio del DeCS y obtener los datos por treeNumber o texto.
 * Funciones para procesar los datos devueltos por el servicio: para convertirlos al nuevo modelo, obtener todas las traducciones de los campos 
 * de texto libre y otras funciones de utilidad tanto para add_decs_texts (agrega traducciones a entidades obtenidas de mesh), 
 * como para ins_decs_data (migracion de datos propios de decs) 
 */
require_once($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require_once(PATH_COMMON_LD."xml2array.php");
require_once(PATH_COMMON_LD."array2json.inc");
require_once(PATH_DECS_LD."db/conexionDB.php"); //pq se llama desde carpeta de lilacs too
require_once(PATH_DECS_LD."common.inc");  //pq se llama desde carpeta de lilacs too

/* Funciones para conectarse al DeCS */

/* Búsqueda de Descriptor o Calificador por palabras con operadores booleanos 
 *
 * @param $bool
 *   string: expresión booleana, donde los términos son conectados por operadores booleanos AND, OR y AND NOT. Ej: “macaca AND mulatta”
 * @param $index
 *   string: índice en el que se hará la búsqueda (101-107 campo entero, ...; 401-407: palabra a palabra, ...)
  * @param $lang
 *   string: lenguaje en el que se desea obtener los textos ('es', 'en' o 'pt')
 *
 * @return
 *   string: XML con datos del descriptor o calificador 
 */
 function boolSearch($bool_="", $index="", $lang="") {
    global $BASEURL;
    $params = array('bool'=>trim($index)." ".trim($bool_), 'lang'=>trim($lang));
    return getContent($BASEURL, $params);
 }

/* Busqueda de Descriptor o Calificador por tree_id y lenguaje 
 *
 * @param $tree_id
 *   string: numero de arbol del descriptor o calificador
 * @param $lang
 *   string: lenguaje en el que se deseaobtener los textos ('es', 'en' o 'pt')
 *
 * @return
 *   string: XML del descriptor o calificador con la estructura del decs 
 */
 function tree_idSearch($tree_id="", $lang="") {
    global $BASEURL;
    $params = array('tree_id'=>trim($tree_id), 'lang'=>trim($lang));
    return getContent($BASEURL, $params);
 }
           

/* Encuesta al servicio del decs mediante su Url y devuelve el contenido del xml en un string
 *
 * @param $url
 *   string: url del servicio del decs
 * @param $params
 *   array: arreglo asociativo con parametros de la busqueda
 *
 * @return
 *   string: XML del descriptor o calificador con la estructura del decs 
 */
 function getContent($url, $params) {
    array_walk($params , create_function('&$v,$k', '$v = $k."=".$v ;'));
    $parameters = str_replace  (" ", "%20" , implode("&",$params));
    
    /*En infomed
    $PROXY_USER = "yazna";    // Username
    $PROXY_PASS = "";   // Password
    // Username and Password are required only if your proxy server needs basic authentication

    $auth = base64_encode("$PROXY_USER:$PROXY_PASS");
    $opts = array(
      'http'=>array(
        'proxy'=>"tcp://pcasa.red.sld.cu:3128",
        'request_fulluri'=>true ,
        'header' => "Proxy-Authorization: Basic $auth"
      )
    );*/

    //en casa
    $opts = array(
      'http'=>array(
        'proxy'=>"tcp://proxy.sld.cu:3128", 
        'request_fulluri'=>true ,
      )
    );

    $ops_ctx = stream_context_create($opts);  

    $content = file_get_contents($url."?".$parameters, FALSE, $ops_ctx);
    return $content;
 }

/* Obtiene datos de Descriptor o Calificador del DeCS por su tree_id, para el idioma especificado 
 *
 * @param $tree_id
 *   string: numero de arbol del descriptor o calificador
 * @param $lang
 *   string: lenguaje en el que se deseaobtener los textos ('es', 'en' o 'pt')
 *
 * @return
 *   array: datos del descriptor o calificador con la estructura del decs 
 */
 function get_item_by_treeN($tree_id, $lang){
  $ret_item= NULL;
  $items = NULL;

  $contenido = tree_idSearch($tree_id, $lang);
  $resultado = xml2array($contenido);
  if(isset($resultado['decsvmx']['decsws_response'])){
    $items = $resultado['decsvmx']['decsws_response'];
    $ret_item = $items;  //busqueda por treeId, devuelve solo uno
  } 
  return($ret_item);  
}

/* Obtiene datos de Descriptor o Calificador del DeCS por su nombre, para el idioma especificado 
 *
 * @param $label
 *   string: nombre del descriptor o calificador
 * @param $index
 *   string: 101 para busqueda exacta (un descriptor)
 *   string: 401 para busqueda por palabras(calificador pq empieza con /)
 * @param $lang
 *   string: lenguaje en el que se desea obtener los textos ('es', 'en' o 'pt')
 *
 * @return
 *   array: datos del descriptor o calificador con la estructura del decs 
 */
function get_item_by_label($label, $index, $lang){
  $ret_item= NULL;
  $items = NULL;
  
  echo "Buscando por label: $label<BR>";
      
  $contenido = boolSearch($label, $index, $lang);
  $resultado = xml2array($contenido);
  if(isset($resultado['decsvmx']['decsws_response'])){
    $items = $resultado['decsvmx']['decsws_response'];
    switch($index){
      case '101':  
        $ret_item = $items;  //busqueda exacta del descriptor, devuelve solo uno
        break;
      case '401':  
        if (is_array($items) && count($items) > 0) { //busqueda del calificador por palabras, puede devolver mas de uno
          foreach($items as $item ){
            if(isset($item['tree']) AND ($item['tree']['self']['term_list']['term'] == "/".$label )){ //si tiene / delante es el calificador
              $ret_item = $item;
            }
          }  
        }
        break;
    }
  }
  return($ret_item);  
}
                      
/* Convierte los datos devueltos por el servicio del DeCS a la estructura de DeCS RDF
 *
 * @param $term
 *   array asociativo: datos del descriptor o calificador devuelto por el servicio del decs
 * @param $type
 *   string: Descriptor o Qualifier 
 * @param $lang
 *   string: lenguaje de los textos ('es', 'en' o 'pt')
 * @param $ins
 *   integer: 1: indica que es una entidad propia del decs; 0: existe en mesh
 *
 * @return
 *   array of array: lista de los propiedades del termino segun la estructura de datos del mesh rdf  
 */
function decs2decsRDF($term, $type, $lang, $ins=0){
   global $qualifiersByAbbr, $cat_decs; 
   $result = array();
   
   $record = $term['record_list']['record'];
      
   $result['identifier']=$record['unique_identifier_nlm']?$record['unique_identifier_nlm']:NULL;   
   
   if(is_array($record['tree_id_list']['tree_id']))
     $result['treeNumber']=$record['tree_id_list']['tree_id'];
   else
     $result['treeNumber']=array($record['tree_id_list']['tree_id']);

   $entidad = $record['descriptor_list']['descriptor'];
   if($type=="Qualifier" AND $entidad[0][0]=='/'){//calificador, se quita el /
     $value0 = substr($entidad[0],1);    
     $value1 = substr($entidad[1],1);    
     $value2 = substr($entidad[2],1);    
   }
   else{
     $value0 = $entidad[0];    
     $value1 = $entidad[1];    
     $value2 = $entidad[2];    
   }    
   $result['label']=array(
                         array("@value"=>$value0, "@language"=>$entidad['0_attr']['lang']),
                         array("@value"=>$value1, "@language"=>$entidad['1_attr']['lang']),
                         array("@value"=>$value2, "@language"=>$entidad['2_attr']['lang'])
                       );   
   $altLabel = get_altLabel($record, $type);
   if($altLabel)                   
     $result['altLabel'][]=array("@value"=>$altLabel, "@language"=>$lang);

   //si ins=1 es una entidad del arbol propio de decs
   if($ins==1){
     if(isset($term['tree']['ancestors']['term_list']) AND count($term['tree']['ancestors']['term_list']))  
       $ancestors = get_relatives_treeId($term['tree']['ancestors']['term_list']);

     foreach($result['treeNumber'] as $treeNumber){
       //si esta en arbol propio del decs o en el de calificadores (calif propio de decs)
       if(in_array(substr($treeNumber,0,2), $cat_decs) OR $treeNumber[0] == 'Q'){
         $decsTN = array('treeNumber'=>$treeNumber);  
         $pos = strpos($treeNumber,'.');  
         if ($pos !== false) { 
           $parentTreeNumber = substr($treeNumber,0,strlen($treeNumber)-4);
           if(in_array($parentTreeNumber, $ancestors)){
             //para datos de tabla treeNumber y buscar broaderDescritor (@id) al finalizar migracion
             $decsTN['parentTreeNumber'] = $parentTreeNumber;  
           }
         } 
         /*else
         es la raiz de la categoria por ej SH01 su broader es SH, en decs existe un descriptor para la raiz (SH) y 
         en mesh no, existe A01 y no existe A
         ?definir q hacer aqui 
         */ 
         $result['decsTreeNumber'][] = $decsTN;  
       }
     }
     
     //si no tiene identificador no es de mesh, hay que insertarlo en decs RDF con todos sus campos 
     if(!$result['identifier']){
       //allowableQualifier 
       if(isset($record['allowable_qualifier_list']['allowable_qualifier']) AND $record['allowable_qualifier_list']['allowable_qualifier']){
         if(!is_array($record['allowable_qualifier_list']['allowable_qualifier'])){
           $record['allowable_qualifier_list']['allowable_qualifier']=array(0=>$record['allowable_qualifier_list']['allowable_qualifier'], 
                                                        '0_attr'=>$record['allowable_qualifier_list']['allowable_qualifier_attr']);
         }    
         foreach($record['allowable_qualifier_list']['allowable_qualifier'] as $key=>$abbr){
           if(is_numeric($key)){ //es la abreviatura
             if(isset($qualifiersByAbbr[$abbr]))
               $result['allowableQualifier'][] = $qualifiersByAbbr[$abbr];  
             else{ //es propio de decs, esta en la tabla pero sin abbr, buscarlo x id original de decs y agregarle abbreviation
               $k = $key.'_attr';
               $orig_decsId = $record['allowable_qualifier_list']['allowable_qualifier'][$k]['id'];
               if(upd_QualifierAbbr($orig_decsId, $abbr)){
                 $qualifiersByAbbr = get_qualifiers_by_abbr(); //volver a llenarlo x la abbr q se agrego de calif propio del decs   
                 $result['allowableQualifier'][] = $qualifiersByAbbr[$abbr];  
               }
               else
                 $result['allowableQualifier'][] = array('abbr'=>$abbr);  
             }
           }
         }
       }
       //disallowedQualifier
       if(isset($record['entry_combination_list']['entry_combination']) AND $record['entry_combination_list']['entry_combination']){
         $result['disallowedQualifier'] = get_disallowedQualifier($record['entry_combination_list'], $lang);
echo "<BR>disallowedQualifier<BR>";
print_r($result['disallowedQualifier']);
       }
       //seeAlso (textual)
       if(isset($record['see_related_list']['see_related'])){
         $result['seeAlso'] = get_seeAlso_txt($record['see_related_list'], $lang);  
       }
       //campos de texto en $lang
       if(isset($record['indexing_annotation']))
         $result['annotation'][]=array("@value"=>$record['indexing_annotation'], "@language"=>$lang);
       if(isset($record['consider_also_terms_at']))
         $result['considerAlso'][]=array("@value"=>$record['consider_also_terms_at'], "@language"=>$lang);
       if(isset($record['definition']['occ_attr']['n']) AND strlen($record['definition']['occ_attr']['n']))
         $result['scopeNote'][] =array("@value"=>$record['definition']['occ_attr']['n'], "@language"=>$lang);  //es scopeNote del preferredConcept 

       //se usa para asignar abreviatura de calificador, que el servicio del decs no lo devuelve 
       $result['orig_decsId']=$term['record_list']['record_attr']['mfn']; 
     }  
   }   
   return($result);
} 

/* Obtiene datos del DeCS en el idioma especificado y agrega traducciones a campos texto en la estructura de DeCS RDF
 *
 * @param $result
 *   array of array: datos del termino (devuelto por decs2decsRDF)
 * @param $type
 *   string: Descriptor o Qualifier 
 * @param $lang
 *   string: lenguaje en el que se desea obtener los textos ('es', 'en' o 'pt')
 * @param $ins
 *   integer: 1: indica que es una entidad propia del decs; 0: existe en mesh
 *
 * @return
 *   array: lista de los tree_id de los ancestors o descendants  
 */
function get_texts_other_lang($result,$type,$lang,$ins=0){
  foreach($result['label'] as $label){
    if($label['@language'] != $lang ){
      //se llama al servicio del decs para otros iidomas  
      $item = get_item_by_TreeN($result['treeNumber'][0], $label['@language']);
      $record = $item['record_list']['record'];
      if(isset($record['definition']['occ_attr']['n']) AND strlen($record['definition']['occ_attr']['n']))
        $result['scopeNote'][] =array("@value"=>$record['definition']['occ_attr']['n'], "@language"=>$label['@language']);  //es scopeNote del preferredConcept 
      if(isset($record['indexing_annotation']))
        $result['annotation'][]=array("@value"=>$record['indexing_annotation'], "@language"=>$label['@language']);
      if(isset($record['consider_also_terms_at']))
        $result['considerAlso'][]=array("@value"=>$record['consider_also_terms_at'], "@language"=>$label['@language']);
      $altLabel = get_altLabel($record, $type);
      if($altLabel)                   
        $result['altLabel'][]=array("@value"=>$altLabel, "@language"=>$label['@language']);//altLabel de preferredTerm
      if(isset($record['consider_also_terms_at']))
        $result['considerAlso'][]=array("@value"=>$record['consider_also_terms_at'], "@language"=>$label['@language']);
      //solo los q se insertan del decs, los del mesh son uris
      if($ins){
        if(isset($record['see_related_list']['see_related'])){
          $result['seeAlso'] = get_seeAlso_txt($record['see_related_list'], $label['@language'], $result['seeAlso']);  
        }  
        if(isset($record['entry_combination_list']['entry_combination']) AND $record['entry_combination_list']['entry_combination']){
          $result['disallowedQualifier'] = get_disallowedQualifier($record['entry_combination_list'], $lang, $result['disallowedQualifier']);  
        }  
      }
      //Aqui igual para disallowed useInstead
    }
  }
  return($result);
}


/* Procesa synonym_list para guardarlo en la BD
 *
 * @param $record
 *   array of array: datos del termino devuelto por el servicio del decs, correspondiente a ['record_list']['record']
 * @param $type
 *   string: Descriptor o Qualifier 
 *
 * @return
 *   array: lista de los valores textuales de los sinonimos del termino, o NULL si synonym_list no tiene valor
 */
function get_altLabel($record, $type){
  $altLabel = NULL;
  if(isset($record['synonym_list']) AND isset($record['synonym_list']['synonym'])){
    $ctdad = count($record['synonym_list']['synonym']);
    if($ctdad){
      $altLabel = $record['synonym_list']['synonym'];
      
      if($type=="Qualifier"){//calificador, se quita el /
        if ($ctdad == 1){
          $altLabel = substr($altLabel,1);     
        }
        else{
          foreach($altLabel as $key=>$synonym){
            $altLabel[$key] = substr($synonym,1);  
          }
        }  
      }    
    }
  }  
  return $altLabel;
}

$qualifiersByAbbr = get_qualifiers_by_abbr();
/* Obtiene abreviaturas de Term y forma arreglo de calificadores, con llave abreviatura 
 *
 * @return
 *   array: lista de calificadores con llave abreviatura y valor array(identifier, @id, label(json))   
 */
function get_qualifiers_by_abbr(){
 global $link_decs, $decs_path;
 
 $qualifiers=array();
 $result = mysqli_query($link_decs, "SELECT identifier, data->'$.abbreviation' FROM Term WHERE data->'$.abbreviation' IS NOT NULL");
 if (!$result) {
   echo 'MySQL Error (result Term abbreviation): ' . mysqli_error($link_decs);
 }
 elseif(mysqli_num_rows($result)){
   while($row = mysqli_fetch_row($result)){
     
     $sql = "SELECT identifier, data->'$.label' FROM Qualifier WHERE data->'$.preferredTerm' ='".$decs_path['Terms']['path'].$row[0]."'";  
     $result1 = mysqli_query($link_decs, $sql);
     if (!$result1) {
       echo 'MySQL Error (qualifier preferredTerm ): ' . mysqli_error($link_decs);
     }
     elseif(mysqli_num_rows($result1)){
       $row1 = mysqli_fetch_row($result1);  
       $arr_abbr = json_decode($row[1],true);
       $abbr =  $arr_abbr[0]['@value'];
       $qualifiers[$abbr] = array("identifier"=>$row1[0], "@id"=>$decs_path['Qualifiers']['path']. $row1[0], "label"=>$row1[1]);
     }  
   }
 }  
  return $qualifiers;  
}  
/* Procesa el arreglo $relatives (ancestors o descendants) y lo devuelve solo con los tree_id
 *
 * @param $relatives
 *   array of array: arreglo de ancestors o descendants del servicio del decs
 *
 * @return
 *   array: lista de los tree_id de los ancestors o descendants  
 */
function get_relatives_treeId($relatives){
  if(is_array($relatives['term'])){  
    foreach($relatives['term'] as $k => $v){
      if(!is_int($k)){
       list($kk, ) = explode('_',$k);  
       $relatives_treeId[$kk] = $v['tree_id'];
      }
    } 
  }
  else{
    $relatives_treeId[] = $relatives['term_attr']['tree_id'];
  }
  return $relatives_treeId;  
}  

/* Procesa lista de seeAlso y devuelve los valores textuales con idioma, 
 * como son descriptores, se puede asignar en el mismo orden para los dif idiomas, CHEQUEAR en la BD los textos!!!!
 *
 * @param $seeRelated
 *   array o string: valores textuales de terminos relacionados devueltos por el servicio del decs
 * @param $lang
 *   string: lenguaje del teexto ('es', 'en' o 'pt')
 * @param $ret_seeAlso
 *   array of array: lista de seeAlso, para agregarle elementos del idioma = $lang
 *
 * @return
 *   array of array: lista de los valores textuales con idioma  
 */
function get_seeAlso_txt($seeRelated, $lang, $ret_seeAlso=array()){
  if(is_array($seeRelated['see_related'])){  
    foreach($seeRelated['see_related'] as $k => $v){
      if(is_int($k)){
          $ret_seeAlso[$k][]= array('@value' => $v, '@language'=>$lang);
      }
      /*else{ //no hace falta pq se pidio con $lang   
       list($kk, ) = explode('_',$k);  
       $seeAlso[$kk]['@language'] = $v['lang'];
      } */
    } 
  }
  else{
    $ret_seeAlso[0][] = array('@value'=> $seeRelated['see_related'], '@language'=>$lang);
  }
  return $ret_seeAlso;  
}  


/* Procesa lista de calificadores no permitidos, y la devuelve con indices: identifier, hasQualifier, label y useInsted(textual)
 *
 * @param $entry_combination
 *   array of array: calificadores no permitidos con estructura de datos del servicio del decs
 * @param $lang
 *   string: lenguaje del valor de useInstead  ('es', 'en' o 'pt')
 *
 * @return
 *   array of array: lista de calificadores no permitidos, 
 *   - si el calificador se encuentra en la tabla cada elemento tiene (identifier, @id, label y useInsted(textual) )
 *   -sino cada elemento tiene (abbr y useInsted(textual) )  
 */
function get_disallowedQualifier($entry_combination, $lang, $ret_disallowedQ=array() ){
  global $qualifiersByAbbr;  
  
  $disallowedQualifier = $ret_disallowedQ;
  if(!is_array($entry_combination['entry_combination'])){
    $entry_combination['entry_combination']=array(0=>$entry_combination['entry_combination'], 
                                                  '0_attr'=>$entry_combination['entry_combination_attr']);
  }    
  foreach($entry_combination['entry_combination'] as $k=>$v){
    if(is_numeric($k)) 
      $disallowedQualifier[$k]['useInstead'][] = array("@value"=>$v, "@language"=>$lang); 
    else{
      list($kk, ) = explode('_',$k);  
      $abbr = $v['sh_abbr1'];
      $qualifier = isset($qualifiersByAbbr[$abbr]) ? $qualifiersByAbbr[$abbr] : NULL;
      if($qualifier){
        $disallowedQualifier[$kk]['identifier'] = $qualifier['identifier'];
        $disallowedQualifier[$kk]['@id'] = $qualifier['@id'];
        $disallowedQualifier[$kk]['label'] = $qualifier['label'];
      }
      else{
        //no esta en mesh, es propio del decs?  
        $disallowedQualifier[$kk]['abbr'] = $abbr;
      }  
    }
  }
  echo "Disallowed<BR>";
  print_r($disallowedQualifier);
  return $disallowedQualifier;
}
/* Agrega la abreviatura del calificador en el termino (Term.abbreviation) descrito en Qualifier.preferredTerm 
 *
 * @param $orig_decsId
 *   integer: id del calificador en la BD original del DeCS
 * @param $abbr
 *   string: abreviatura del calificador (2 caracteres)
 *
 * @return
 *   1: si se actualizo la abreviatura en el termino correspondiente al calificador, 
 *   0: no se actualizo abreviatura, pq no existe calificador o por error al concultar la tabla
 */
function upd_QualifierAbbr($orig_decsId, $abbr){
  global $link_decs, $decs_path;
  
  $sql = "SELECT JSON_UNQUOTE(data->'$.preferredTerm') FROM Qualifier WHERE data->'$.orig_decsId'=$orig_decsId";   
  $result = mysqli_query($link_decs, $sql);
  if (!$result) {
    echo 'MySQL Error (result): ' . mysqli_error($link_decs);
    $ret=0;
  }  
  elseif(mysqli_num_rows($result)){
    $row = mysqli_fetch_row($result);
    $term_identifier = substr($row[0],$decs_path['Terms']['len']); 
    $abbr_str = array_to_arrStr(array(array("@value"=> $abbr, "@language"=> "en")), $link_decs);

    $sql1= "UPDATE Term SET data = JSON_SET(data, '$.abbreviation', JSON_ARRAY($abbr_str)), decsUpdated=NOW() WHERE identifier='".$term_identifier."'" ;
    $result1 = mysqli_query($link_decs, $sql1);
    if (!$result1) {
      echo 'MySQL Error (result1): ' . mysqli_error($link_decs);
      $ret=0;
    }  
    else{
      $ret = 1;
    }
  }
  else{
    echo "No se encontro en Qualifiers calificador con orig_decsId=$orig_decsId  (asociado a abbr=$abbr) <BR>" ;
    $ret=0;
  }  
  return $ret;
}  
/* Chequea si existe un numero de arbol en la tabla TreeNumber
 * @param $decsTN
 *   string: trenumber a buscar
 * @return
 *   1: si existe,
 *   0: si no existe, 
 *  -1: si error al sonsultar la BD   
 */
function treeNumber_exist($decsTN){
  global $link_decs;
    
  $ret = 0;
  $result = mysqli_query($link_decs, "SELECT id FROM TreeNumber WHERE identifier='".$decsTN."'");
  if (!$result) {
    echo 'MySQL Error (treeNumber_exist): ' . mysqli_error($link_decs)."<BR>";
    $ret = -1;
  }
  elseif(mysqli_num_rows($result)){//existe en la tabla
    $ret = 1;
  }  
  return $ret;
}

/* Obtiene el numero de la ultima entidad de decs introducida en la tabla
 *
 * @param $table
 *   string: nombre de la tabla (Descriptor, Qualifier, Term)
 *
 * @return
 *   integer: numero de la ultima entidad de decs, si el ultimo identificador es D000123, devuelve 123 
 *   -1 si ocurrio error al consultar la tabla
 */
function get_lastId($table){
  global $link_decs;
    
  $prefix = $table[0];
  $result = mysqli_query($link_decs, "SELECT identifier FROM $table WHERE identifier LIKE '_$prefix%' ORDER BY id DESC LIMIT 1");
  if (!$result) {
    echo 'MySQL Error (treeNumber_exist): ' . mysqli_error($link_decs)."<BR>";
    $ret = -1;
  }
  elseif(mysqli_num_rows($result)){//existe en la tabla
    $row = mysqli_fetch_row($result);
    $ret = (int)substr($row[0],2);
  }
  else
    $ret=0;//no hay ninguo  
    
  return $ret;
}
    
/*
-La raiz de un arbol esta en decs como descriptor, pero en mesh no,por ej: ORGANISMOS raiz del arbol con treeid: B, luego B01: Eucariota, B02: Archaea,...
*/

?>

