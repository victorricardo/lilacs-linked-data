<?php
/**
 * @file
 * Migración de los datos del campo originalRecord al campo data con nuevo modelo de datos RDF, 
 * basado en espacios de nombres: schema y lilacs. 
 */
require($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require_once(PATH_LILACS_LD."db/commonDB.php");

set_time_limit(0); 
$ini=time(); 
$clasificadores = array(
  'typeOfLiterature' => array('S' => 'Serie periódica', 
                              'SC' => 'Conferencia en una serie periódica', 
                              'SP' => 'Proyecto en una serie periódica', 
                              'SCP' => 'Proyecto y conferencia en una serie periódica', 
                              'M' => 'Monografía', 
                              'MC' => 'Conferencia en una Monografía', 
                              'MP' => 'Proyecto en una Monografía', 
                              'MCP' => 'Proyecto y conferencia en una Monografía', 
                              'MS' => 'Serie Monográfíca', 
                              'MSC' => 'Conferencia en una Serie Monográfíca', 
                              'MSP' => 'Proyecto en una Serie Monográfíca', 
                              'T' => 'Tesis', 
                              'TS' => 'Tesis en Serie Monográfica', 
                              'N' => 'No Convencional', 
                              'NC' => 'Conferencia en forma no convencional',
                              'NP' => 'Proyecto en forma no convencional', 
                              ),
  'levelOfTreatment' => array('m' => 'Nivel monográfico', 
                              'mc' => 'Nivel monográfico de colección', 
                              'ms' => 'Nivel monográfico de serie', 
                              'am' => 'Nivel analítico monográfico', 
                              'amc' => 'Nivel analítico monográfico de colección', 
                              'ams' => 'Nivel analítico monográfico de serie', 
                              'as' => 'Nivel analítico de serie', 
                              'c' => 'Nivel colección',
                              's' => 'Nivel serie',
                              ),
  'documentType' =>     array('a' => 'Material textual', 
                              'c' => 'Música impresa', 
                              'd' => 'Manuscritos de música', 
                              'e' => 'Material cartográfico', 
                              'f' => 'Manuscritos de material cartográfico', 
                              'g' => 'Material proyectable', 
                              'i' => 'Registros sonoros no musicales', 
                              'j' => 'Registros musicales',
                              'k' => 'Gráficos bidimensionales no proyectables',
                              'm' => 'Archivo de computador', 
                              'o' => 'Kit', 
                              'p' => 'Material mixto',
                              'r' => 'Material tridimensional',
                              't' => 'Manuscritos',
                              ),
  'itemForm' =>         array('a' => 'Microfilm', 
                              'c' => 'Microficha', 
                              'd' => 'Microficha opaca', 
                              'e' => 'Impreso grande', 
                              'f' => 'Braille', 
                              'r' => 'Reproducción impresa regular – impresión legible', 
                              's' => 'Formato Electrónico',
                              ),
  'typeComputerFile' => array('a' => 'Datos numéricos', 
                              'b' => 'Programas de computador', 
                              'c' => 'Representacionales – información gráfica o pictórica que junto a otro tipo de archivo puede producir patrones gráficos, para interpretar y dar significado a la información.', 
                              'd' => 'Documentos', 
                              'e' => 'Datos bibliográficos', 
                              'f' => 'Tipos de letra (fuentes)', 
                              'g' => 'Juegos', 
                              'h' => 'Sonidos', 
                              'i' => 'Multimedios interactivos', 
                              'j' => 'Servicios o sistemas online', 
                              'm' => 'Combinación' 
                              ),
  'typeCartographic' => array('a' => 'Mapa único', 
                              'b' => 'Serie de mapas', 
                              'c' => 'Mapa seriado', 
                              'd' => 'Globo', 
                              'e' => 'Atlas', 
                              'f' => 'Mapa como suplemento de otra obra', 
                              'g' => 'Mapa encuadernado como parte de otra obra'
                              ),
  'typeSerial' =>       array('l' => 'Hojas sueltas con actualizaciones diarias', 
                              'n' => 'Jornales', 
                              'p' => 'Revistas', 
                              'u' => 'Separatas' 
                              ),
  'typeVisual' =>       array('a' => 'Arte (original)', 
                              'b' => 'Kit', 
                              'c' => 'Arte (reproducción)', 
                              'd' => 'Diorama', 
                              'f' => 'Tira de Film', 
                              'g' => 'Juego', 
                              'i' => 'Cuadro',
                              'k' => 'Gráfico', 
                              'l' => 'Dibujo técnico', 
                              'm' => 'Film', 
                              'n' => 'Mapa', 
                              'o' => 'Flash card (cartón relámpago)', 
                              'p' => 'Slide de microscopio', 
                              'q' => 'Modelo', 
                              'r' => 'Realia', 
                              's' => 'Slide', 
                              't' => 'Transparencia', 
                              'v' => 'Grabación en video', 
                              'w' => 'Juguete' 
                              ),
  'typeNoProjectable' =>array('c' => 'Colage', 
                              'd' => 'Dibujo', 
                              'e' => 'Pintura', 
                              'f' => 'Impresión fotomecánica', 
                              'g' => 'Fotonegativo', 
                              'h' => 'Fotoimpresión', 
                              'i' => 'Foto',
                              'j' => 'Impresión', 
                              'l' => 'Dibujo técnico', 
                              'n' => 'Gráfico', 
                              'o' => 'Flash card (cartón relámpago)'
                              ),
  //se asocian propiedades de CreativeWork 
  'responsibility' =>   array('aut' => 'author',
                              'cre' => 'creator',
                              'edt' => 'editor', 
                              'trl' => 'translator', 
                              'ctb' => 'contributor', 
                              'cpc' => 'copyrightHolder',
                              'ill' => 'illustrator',
                              ),

);

$keys_sinUsar = '';

/*se genera campo 'data' para :
 *-los registros que no s han migrado (data is null) o 
 *-los que originalRecord cambio (newOriginalRecord=1)
 */
//PENDIENTE: si se va a ejecutar importacion periodicamente agregar condicion de newOriginalReord
//$sql = 'SELECT id, originalRecord FROM `document` WHERE originalRecord IS NOT NULL AND (data IS NULL OR newOriginalRecord=1)';  

$count = getCount($link, 'document', "originalRecord IS NOT NULL AND data IS NULL");
$ctdad = intval($count/1000);
$upd=0;
for($i=0;$i<=$ctdad;$i++){
  $start = $i*1000;
  
  $sql = "SELECT id, originalRecord FROM document WHERE originalRecord IS NOT NULL AND data IS NULL LIMIT $start,1000";  
  $result = mysqli_query($link, $sql);
  $migrados = 0;
  while ($row = mysqli_fetch_assoc($result)) {
    $migrados += migrar_document($row['id'], $row['originalRecord'], $clasificadores, $link);  
    //break;        //descomentar para migrar 1 solo registro
  }   
  mysqli_free_result($result);
}  
echo "Se migraron satisfactoriamente $migrados documentos de ". $count[0] ."<BR>";
echo "Llaves sin usar $keys_sinUsar <BR>";
$total=(time()-$ini)/60;
echo "Tiempo de ejecucion: $total minutos<BR>";


function migrar_document($id, $originalRecord, $clasificadores, $link){
    
  $orig_arr = json_decode($originalRecord, 1);
  
  //no procesar si no tiene estos datos
  if(!isset($orig_arr['v5']) OR !isset($orig_arr['v6']))
    return 0;
  
  $document= array('@id'=> URL_LILACS_LD."documents/".$id, 'id'=>$id); 
  $v9 = isset($orig_arr['v9']) ? $orig_arr['v9'] : NULL;
  $someKeys2one = array();
  foreach($orig_arr as $key => $value){
    $property = migrar_docProperty($key, $orig_arr, $someKeys2one, $clasificadores);
    //se asigna la migracion de element a las llaves correspondientes de document, 
    //si se asigna a isPartOf, su valor se devuelve para pasarlo a migrar_docElemnt en las sgtes llamadas, pq isPartOf se forma con varios campos
    asignar_docProperty($property, $document, $someKeys2one);
  }
  //$upd = db_updateDocument($document, $id, $link);
//PENDIENTE: si se va a ejecutar importacion periodicamente actualizar newOriginalReord a 0
  $upd = _updateEntityDB("document", $id, $document);
  return $upd;
}

function migrar_docProperty($key, $orig_arr, &$someKeys2one, $clasificadores){

  $v9 = isset($orig_arr['v9']) ? $orig_arr['v9'] : NULL;
  $v6 = in_array($orig_arr['v6'], array('am','ams','amc', 'as'))?'a': (in_array($orig_arr['v6'], array('m','ms','mc'))?'m':$orig_arr['v6']);
  $def_idioma = '';
  if(isset($orig_arr['v40']) AND !is_array($orig_arr['v40'])){
    $def_idioma = strtolower($orig_arr['v40']);
  }  
  
  //propiedades que se construyen a partir de varios campos d originalRecord
  $isPartOf = isset($someKeys2one['isPartOf']) ? $someKeys2one['isPartOf'] : NULL;
  $recordedAt = isset($someKeys2one['recordedAt']) ? $someKeys2one['recordedAt'] : NULL;
  $project = isset($someKeys2one['project']) ? $someKeys2one['project'] : NULL;
  $publication = isset($someKeys2one['publication']) ? $someKeys2one['publication'] : NULL;
  $temporalSubject = isset($someKeys2one['temporalSubject']) ? $someKeys2one['temporalSubject'] : NULL;

  $value = $orig_arr[$key];
  $property = array();
  switch($key){
   case 'v1': //código del centro
     $property['provider'] = array('branchCode'=>$value, '@type'=>'Organization');  
     break;  
   case 'v2': //número de identificación
     $property['_id'] = $value;
     break;  
   case 'v3': //localización del documento
     $tags = array('<a>' => 'clasificationNumber', '<b>' => 'authorNumber', '<c>' => 'parts', '<t>' => 'inventory', '0' => 'institution'); 
     $located_in = migrar_valueTag($value, $tags);

     if(is_assoc_array($located_in)){
       if(isset($located_in['institution']))  
         if($def_idioma)
           $located_in['institution'] = array('name'=>array('@value'=>$located_in['institution'], '@language'=>$def_idioma), '@type'=>'Organization');
         else
           $located_in['institution'] = array('name'=>array('@value'=>$located_in['institution']),  '@type'=>'Organization');
     }
     else{
       $new_institutions = array();  
       foreach($located_in as $located_in1){
         if(isset($located_in1['institution'])){
           if($def_idioma)
             $located_in1['institution'] = array('name'=>array('@value'=>$located_in1['institution'], '@language'=>$def_idioma), '@type'=>'Organization');
           else
             $located_in1['institution'] = array('name'=>array('@value'=>$located_in1['institution']),  '@type'=>'Organization');
           $new_institutions[] = $located_in1;
         }  
       }  
       $located_in = $new_institutions;
     }
     $property['locatedIn'] = $located_in;
     break;  
   case 'v4': //base de datos
     $property['database'] = $value;
     $database = $value;
     break;  
   case 'v5': //tipo de literatura   
     if(isset($clasificadores['typeOfLiterature'][$value])){
       $val = utf8_encode($clasificadores['typeOfLiterature'][$value]);
       $property['typeOfLiterature']= array('propertyID'=>$value, 'name'=>array('@value'=>$val,'@language' => 'es'));
     }
     break;  
   case 'v6': //nivel de tratamiento
     if(isset($clasificadores['levelOfTreatment'][$value])){
       $val = utf8_encode($clasificadores['levelOfTreatment'][$value]);
       $property['levelOfTreatment'] = array('propertyID'=>$value, 'name'=>array('@value'=>$val,'@language' => 'es'));

       $field_type = documentType($orig_arr['v5'], $value, $v9);//tipo en schema.org
       $property['@type'] = $field_type;
     }
     break;  
   case 'v7': //número del registro
     $property['registerNumber'] = $value;
     break;  
   case 'v8': //dirección electrónica
     switch($orig_arr['v4']){
       //aqui quitar dependencia d la BD 
       case "VIMED":
       case "TRAMED":
         $tags = array("<u>"=>"url", "<i>" =>"inLanguage", "<g>" =>"fullText", "<k>" =>"password", "<l>" =>"logon", "<q>" =>"fileExtension", 
                    "<s>" =>"contentSize", "<x>" =>"notPublicNote", "<y>" =>"fileFormat", "<z>" =>"publicNote");
         break;  
       case "CUMED":
       case "BIBMED":
         $tags = array("<i>"=>"url");
         break;  
     }
     $migr_value = migrar_valueTag($value, $tags);
     $property['electronicAddress'] = $migr_value;
     $property['url'] = $migr_value['url'];
     if(isset($migr_value['fileFormat']))
       $property['fileFormat'] = $migr_value['fileFormat'];
     break;  
   case 'v9': // Tipo de Registro
     if(!is_array($value) AND isset($clasificadores['documentType'][$value])) {//si no es un error
       $val = utf8_encode($clasificadores['documentType'][$value]);
       if ($val){
         $property['documentType'] = array('propertyID'=>$value, 'value'=>array('@value'=>$val,'@language' => 'es'));
       }
     }
     break;  
   case 'v10': // Autor Personal (nivel analítico)
   case 'v16': // Autor personal (nivel monográfico)
   case 'v23': // Autor personal (nivel colección)
     $author=migrar_entity($value, "Person", $def_idioma);
     $arr_auth = authors_byResponsibility($author, $clasificadores['responsibility']);

      if( ($key=='v10'  AND $v6=='a') OR   
        ($key=='v16' AND $v6=='m') OR  
        ($key=='v23' AND $v6=='c')) {       //es el nivel de mas detalle de doc y por eso se asigna a document
       if(is_assoc_array($author)){
         foreach($arr_auth as $author_type => $un_author){
           $property[$author_type] = $un_author;
         }    
       }
       else{
         foreach($arr_auth as $author_type => $un_author){
           $ctdad = count($un_author);
           if($ctdad == 1){
              $property[$author_type] = $un_author[0];
           }
           elseif($ctdad > 1){
             $property[$author_type] = $un_author;
           }
         }
       }    
     }
     else{
       //$k_v[$key]=$value;    
       $property['isPartOf'] = migrar_level($key, $orig_arr['v5'], $orig_arr['v6'], $orig_arr['v4'], $arr_auth, $v9, $isPartOf);
     } 
     break;  
   case 'v11': // Autor Institucional (nivel analítico)
   case 'v17': // Autor institucional  (nivel monográfico)
   case 'v24': // Autor institucional  (nivel coleccion)
     $author=migrar_entity($value, "Organization", $def_idioma);
     $arr_auth = authors_byResponsibility($author, $clasificadores['responsibility']);
     if(($key=='v11' AND !isset($orig_arr['v10']) AND $v6=='a')  OR 
        ($key=='v17' AND !isset($orig_arr['v16']) AND $v6=='m') OR  
        ($key=='v24' AND !isset($orig_arr['v23']) AND $v6=='c')
        ) {
       //es el nivel de mas detalle de doc y por eso se asigna como autor del doc
       if(is_assoc_array($author)){
         foreach($arr_auth as $author_type => $un_author){
           $property[$author_type] = $un_author;
         }    
       }
       else{
         foreach($arr_auth as $author_type => $un_author){
           $ctdad = count($un_author);
           if($ctdad == 1){
              $property[$author_type] = $un_author[0];
           }
           elseif($ctdad > 1){
             $property[$author_type] = $un_author;
           }
         }
       }    
     }
     else{
       if(($key=='v17' AND !isset($orig_arr['v16'])) OR ($key=='v24' AND !isset($orig_arr['v23'])) ){
         //$k_v[$key]=$value;
         $property['isPartOf'] = migrar_level($key, $orig_arr['v5'], $orig_arr['v6'], $orig_arr['v4'], $arr_auth, $v9, $isPartOf);
       }
     } 
     break;
   case 'v12': // Título (nivel analítico)
   case 'v18': // Título (nivel monográfico)
   case 'v25': // Título (nivel colección)
   case 'v30': // Título (nivel serie)
     if(($key=='v12' AND $v6=='a') OR 
        ($key=='v18' AND $v6=='m') OR  
        ($key=='v25' AND $v6=='c') OR
        ($key=='v30' AND $v6=='s')
        ) {
       $name = (isset($property['name'])) ? $property['name'] : NULL;
       $title = migrar_titulo($value, $def_idioma, $name);
       $property['name'] = $title;
     }
     else{  //el titulo es de un doc (de nivel superior) a que pertenece el doc que se esta migrando
       //la informacion de los otros niveles del doc se traduce como isPartOf  
       //$k_v[$key]=$value;
       $title = migrar_titulo($value, $def_idioma);
       $property['isPartOf'] = migrar_level($key, $orig_arr['v5'], $orig_arr['v6'], $orig_arr['v4'], array('name'=>$title), $v9, $isPartOf);
     }                    
     break;  
   case 'v13': // Título traducido al Inglés  (nivel analítico)            
   case 'v19': // Título traducido al Inglés  (nivel monográfico)
   case 'v26': // Título traducido al Inglés  (nivel colección)
     if(($key=='v13' AND $v6=='a') OR 
        ($key=='v19' AND $v6=='m') OR
        ($key=='v26' AND $v6=='c') 
        ) {
       $name = (isset($property['name'])) ? $property['name'] : NULL;
       $title = migrar_titulo($value, 'en', $name);
       $property['name'] = $title;
     }
     else{  //el titulo es de un doc (de nivel superior) al que pertenece el doc que se esta migrando
       //la informacion de los otros niveles del doc se traduce como isPartOf  
       //$k_v[$key]=$value;
       $title_eng = array("@value"=>$value, "@language"=>'en');
       $property['isPartOf'] = migrar_level($key, $orig_arr['v5'], $orig_arr['v6'], $orig_arr['v4'], array('name'=>$title_eng), $v9, $isPartOf);
     }                    
     break;  
   case 'v14': // Páginas (nivel analítico)
   case 'v20': // Páginas (nivel monográfico)
     //devuelve pagination o pageStart, pageEnd
     $paginas = migrar_paginas($value);
     if(($key=='v14' AND $v6=='a') OR
        ($key=='v20' AND $v6=='m') )  {
       foreach($paginas as $k => $v){
         $property[$k] = $v;  
       }
     }
     else{  //paginas de un doc (de nivel superior) al que pertenece el doc que se esta migrando
       //la informacion de los otros niveles del doc se traduce como isPartOf  
       $property['isPartOf'] = migrar_level($key, $orig_arr['v5'], $orig_arr['v6'], $orig_arr['v4'], $paginas, $v9, $isPartOf);
     }                    
     break;  
   case 'v21': // Volumen (nivel monográfico)
     $volume = migrar_volume($value); 
     if($v6=='m'){
       foreach($volume as $k=>$v) //puede tener varias llaves (volume, tomo, part) 
         $property[$k] = $v;
     }
     else{
       $property['isPartOf'] = migrar_level($key, $orig_arr['v5'], $orig_arr['v6'], $orig_arr['v4'], $volume, $v9, $isPartOf);
     }
     break;
   case 'v27': // total de volumenes (nivel colección)       
     if($v6=='c')
       $property['totalVolumes'] = $value;
     else{
       $property['isPartOf'] = migrar_level($key, $orig_arr['v5'], $orig_arr['v6'], $orig_arr['v4'], array('totalVolume'=>$value), $v9, $isPartOf);
     }
     break;
   case 'v31': // Volumen (nivel serie)
     if($v6=='s')
       $property['volumeNumber'] = $value;
     else{
       $property['isPartOf'] = migrar_level($key, $orig_arr['v5'], $orig_arr['v6'], $orig_arr['v4'], array('volumeNumber'=>$value), $v9, $isPartOf);
     }
     break;
   case 'v32': 
     if($v6=='s')
       $property['issueNumber'] = $value;
     else{
       $property['isPartOf'] = migrar_level($key, $orig_arr['v5'], $orig_arr['v6'], $orig_arr['v4'], array('issueNumber'=>$value), $v9, $isPartOf);
     }
     break;
   case 'v35': 
     $property['issn'] = $value;
     //if($v6=='s')
     //  $property['issn'] = $value;
     //else{
     //  $property['isPartOf'] = migrar_level($key, $orig_arr['v5'], $orig_arr['v6'], $orig_arr['v4'], array('issn'=>$value), $v9, $isPartOf);
     //}
     break;
   case 'v38': // Inf descriptiva
      if(is_array($value))
        $value1 = $value[0]; //para buscar tags en el 1er elem del arreglo
      else
        $value1 = $value; 
      
      if( strstr($value1,"<b>") OR strstr($value1,"<a>") OR strstr($value1,"<c>") OR strstr($value1,"<e>")){
         $tags = array("<b>"=>"graficMaterial", "<a>" =>"specifInfo", "<c>" =>"dimension", "<e>" =>"enclosedMaterial", "0"=>"graficMaterial");
         $migr_value = migrar_valueTag($value, $tags);
      }
      else{
         $migr_value = array('graficMaterial'=>$value);
      }
     /*switch($orig_arr['v4']){
       case "VIMED":
       case "TRAMED":
         $tags = array("<b>"=>"graficMaterial", "<a>" =>"specifInfo", "<c>" =>"dimension", "<e>" =>"enclosedMaterial", "0"=>"graficMaterial");
         $migr_value = migrar_valueTag($value, $tags);
         break;  
       case "CUMED":
       case "BIBMED":
         $migr_value = array('graficMaterial'=>$value);
         break;  
     }*/ 
     $property['descriptiveInfo'] = $migr_value;
     break;
   case 'v40': // Idioma dl texto
     $property['inLanguage'] = $value;
     break;
   case 'v49': // Orientador de la Tesis
     $property['tutor'] = migrar_entity($value, "Person", $def_idioma);
     break;
   case 'v50': // Institucion a la qu se presenta la Tesis
     $property['presentedTo'] = migrar_entity($value, "Organization", $def_idioma);
     break;
   case 'v51': // título académico
     $property['inSupportOf'] = $value;
     break;
   case 'v52': // evento - Institucion patrocinadora
     $recordedAt['attendee'] = migrar_entity($value, "Organization", $def_idioma);
     $property['recordedAt'] = $recordedAt;
     break;
   case 'v53': // evento - nombre
     $recordedAt['name'] = array('@value'=>$value);
     if($def_idioma)
       $recordedAt['name']['@language'] = $def_idioma;
     $recordedAt['@type'] = 'Event';
     $property['recordedAt'] = $recordedAt;
     break;
   case 'v55': // evento - fecha
     $recordedAt['startDate'] = $value;
     $property['recordedAt'] = $recordedAt;
     break;
   case 'v56': // evento - ciudad
     if($value != 's.l'){
       $recordedAt['location']['addressLocality'] = $value;
       $property['recordedAt'] = $recordedAt;
     }
     break;
   case 'v57': // evento - pais
     $recordedAt['location']['addressCountry'] = $value;
     $recordedAt['location']['@type'] = 'PostalAddress';
     $property['recordedAt'] = $recordedAt;
     break;
   case 'v58': // proyecto - Institucion patrocinadora
     $project['attendee'] = migrar_entity($value, "Organization", $def_idioma);
     $property['project'] = $project;
     break;
   case 'v59': // proyecto - nombre
     $project['name'] = array('@value'=>$value);
     if($def_idioma)
       $project['name']['@language'] = $def_idioma;
     $project['@type'] = 'Project';
     $property['project'] = $project;
     break;
   case 'v60': // proyecto - numero
     $project['projectNumber'] = $value;
     $property['project'] = $project;
     break;
   case 'v61': // nota interna
     $property['note'] = array('motivation'=>'interna', 'body'=>$value);
     break;
   case 'v62': // editora   
     if($value != 's.n')
       $property['publisher'] = migrar_entity($value, "Organization", $def_idioma);
     /*else       
       $property['publisher'] = $value; */
     break;
   case 'v63': // nro de edición     
     $property['bookEdition'] = $value;
     //workExample??
     break;
   case 'v65': // fecha de publicacion normalizada
     $property['datePublished'] = $value;
     $publication['startDate'] = $value;
     $publication['@type'] = 'PublicationEvent';
     $property['publication'] = $publication;
     break;
   case 'v66': // ciudad de publicacion 
     if($value != 's.l'){
       $publication['location']['addressLocality'] = $value;
       $property['publication'] = $publication;
     }
     break;
   case 'v67': // pais de publicacion 
     $publication['location']['addressCountry'] = $value;
     $publication['location']['@type'] = 'PostalAddress';
     $property['publication'] = $publication;
     break;
   case 'v68': // simbolo
     $property['symbol'] = $value;
     break;
   case 'v69': // ISBN
     $property['isbn'] = $value;
     break;
   case 'v71': // Tipo de publicacion   (DeCS)
     $new_val = migrar_indice($value, $def_idioma, "PublicationType");
     $property['genre'] = $new_val;
     //$property['genre'] = $value;
     break;                          
   case 'v72': // total de referencias
     $property['totalReferences'] = $value;
     break;                          
   case 'v74': // alcance temporal desde
     $temporalSubject['startDate'] = $value;
     $property['temporalSubject'] = $temporalSubject;
     if(isset($orig_arr['v75']))
       $property['about'] = $value .'-'.$orig_arr['v75'];
     else   
       $property['about'] = $value;
     break;                          
   case 'v75': // alcance temporal hasta
     $temporalSubject['endDate'] = $value;
     $property['temporalSubject'] = $temporalSubject;
     break;  
   case 'v76': // descriptor precodificado (DeCS)
     $new_val = migrar_indice($value, $def_idioma, array("Topic", "CheckTag"));
     $property['subjectLimit'] = $new_val;
     $property['about'] = $new_val;
     break;  
   case 'v78': // individuo como tema 
     $aboutPerson = migrar_entity($value, "Person", $def_idioma);
     $property['about'] = $aboutPerson;
     break;  
   case 'v82': // region
     $property['region'] = $value;
     break;  
   case 'v83': // resumen
     //es igual q titulo, se traduce a @value, @language
     $property['description'] = migrar_titulo($value, $def_idioma);
     break;  
   case 'v84': // Fecha del envio del registro para la base de datos original
     $property['transferedOriginalRecord'] = $value;
     break;  
   case 'v85': // palabras llave
     $property['keywords'] = migrar_indice($value, $def_idioma);
     break;  
   case 'v87': // descriptor primario (DeCS)
     $new_val = migrar_indice($value, $def_idioma, array("Topic", "TopicalDescriptor"));
     $property['primarySubject'] = $new_val;
     $property['about'] = $new_val;
     break;  
   case 'v88': // descriptor secundario (DeCS)
     $new_val = migrar_indice($value, $def_idioma, array("Topic", "TopicalDescriptor"));
     $property['secondarySubject'] = $new_val;
     $property['about'] = $new_val;
     break;  
   case 'v91': // Fecha del creacion del registro en la base de datos original
     $new_val = migrar_fecha($value);
     $property['createdOriginalRecord'] = $new_val;
     break;  
   case 'v92': // documentalista (iniciales)
     $property['documentalist'] = $value;
     break;  
   case 'v93': //fecha de la última modificación del registro en la base de datos original
     $new_val = migrar_fecha($value);
     $property['modifiedOriginalRecord'] = $new_val;
     break;  
   case 'v98': //Registro Complementario (documento)
   case 'v101': //Registro Complementario (evento)
   case 'v102': //Registro Complementario (proyecto)
     $arr = explode('-',$value);
     if(count($arr)>1){
       $property['isPartOf'] = migrar_level($key, $orig_arr['v5'], $orig_arr['v6'], $orig_arr['v4'], array('branchCode'=>$arr[0], '_id'=>$arr[1]), $v9, $isPartOf);
     }
     break;  
   case 'v110': //Forma del Item
     if(isset($clasificadores['itemForm'][$value])){
       $property['itemForm'] = array('propertyID'=>$value, 'value'=>array('@value'=>$clasificadores['itemForm'][$value], '@language'=>'es'));
     }
     break;
   case 'v111': //Tipo de Archivo de Computador
     if(isset($clasificadores['typeComputerFile'][$value])){
       $property['typeComputerFile'] = array('propertyID'=>$value, 'value'=>array('@value'=>$clasificadores['typeComputerFile'][$value], '@language'=>'es'));
     }
     break;
   case 'v112': //Tipo de Material Cartográfico
     if(isset($clasificadores['typeCartographic'][$value])){
       $property['typeCartographic'] = array('propertyID'=>$value, 'value'=>array('@value'=>$clasificadores['typeCartographic'][$value], '@language'=>'es'));
     }
     break;
   case 'v113': //Tipo de Periódico
     if(isset($clasificadores['typeSerial'][$value])){
       $property['typeSerial'] = array('propertyID'=>$value, 'value'=>array('@value'=>$clasificadores['typeSerial'][$value], '@language'=>'es'));
     }
     break;
   case 'v114': //Tipo de Material Visual
     if(isset($clasificadores['typeVisual'][$value])){
       $property['typeVisual'] = array('propertyID'=>$value, 'value'=>array('@value'=>$clasificadores['typeVisual'][$value], '@language'=>'es'));
     }
     break;
   case 'v115': //Designación Específica del Material (no proyectable)
     if(isset($clasificadores['typeNoProjectable'][$value])){
       $property['typeNoProjectable'] = array('propertyID'=>$value, 'value'=>array('@value'=>$clasificadores['typeNoProjectable'][$value], '@language'=>'es'));
     }
     break;
   case 'v500': //Nota General
     $property['note'] = array('motivation'=>'general', 'body'=>$value);
     break;  
   case 'v505': //Nota Formateada de Contenido
     $property['note'] = array('motivation'=>'formateada de contenido', 'body'=>$value);
     break;  
   case 'v530': //Nota Disponibilidad física adicional
     $property['note'] = array('motivation'=>'formato físico adicional', 'body'=>$value);
     break;  
   case 'v533': //Nota de Reproducción
     $property['note'] = array('motivation'=>'reproducción', 'body'=>$value);
     break;  
   case 'v534': //Nota de Versión Original
     $property['note'] = array('motivation'=>'versión original', 'body'=>$value);
     break;  
   case 'v610': //Institución como Tema
     $aboutOrganization = migrar_entity($value, "Organization", $def_idioma);
     $property['about'] = $aboutOrganization;
     break;  
   case 'v653': //Desciptores locales
     $property['localSubject'] = $value;
     $property['about'] = $value;
     break;  
   case 'v700': //Nro del registro de ensayo clínico
     $property['clinicEssayNumber'] = $value;
     break;  
                           
 } 
 global $keys_sinUsar;
 if($property == array() AND !strstr($keys_sinUsar, $key.',')){
      $keys_sinUsar .= $key.',';  
 }
 
 return $property;
    
}

function asignar_docProperty($property, &$document, &$someKeys2one){
  foreach($property as $k=>$v){
    if(in_array($k, array('isPartOf', 'recordedAt', 'project', 'publication', 'temporalSubject'))){
      $someKeys2one[$k] = $v; 
      $document[$k]=$v;
    }
    elseif(isset($document[$k])){//si ya tiene valor (y no es isPartOf) no se sobreescribe, se agrega a array no assoc (caso de name en es y en o note) 
      if(!is_array($document[$k]) OR is_assoc_array($document[$k])){
        if(!is_array($v) OR is_assoc_array($v)){
          $document[$k] = array($document[$k], $v);  
        }  
        else{
          $document[$k] = array_merge(array($document[$k]), $v);
        }  
      }
      else{
        if(!is_array($v) OR is_assoc_array($v)){
          $document[$k][] = $v;  
        }  
        else{
          $document[$k] = array_merge($document[$k], $v);
        }  
      }  
    }
    else{
      $document[$k]=$v;
    }
  }
}

/* se utiliza la de capa BD
function db_updateDocument($document, $id, $link){
    $json_str = array_to_objStr($document, $link);
  
    $ok = 0;
    //chequear si cadena $json_str es correcta para pasar por JSON_OBJECT()
    if(!json_argument_error($json_str,$link)){
      //$sql= "UPDATE Document SET data = JSON_OBJECT($json_str), modified=NOW(), newOriginalRecord = 0 WHERE id=". $id; 

      $sql= "UPDATE Document SET data = JSON_OBJECT($json_str), modified=NOW() WHERE id=". $id; 
      $resulti = mysqli_query($link, $sql);
      if (!$resulti) {
        printf("SQL is %s!<BR>", $sql);
        echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
      }
      $ok = 1;
    }
    return $ok;
}*/
/**
 * Traduce un campo de volumen a 'volumeNumber' o 'tomo', 'part'
 *
 * @param $value
 *   string: Texto de volumen (v21)
 *
 * @return
 *   array: asociativo con llave pagination o llaves pageStart, pageEnd, o totalVolumen
 */
function migrar_volume($value){
  $migr_value = explode(',', $value);
  foreach ($migr_value as $v ){
    $m_v = explode('.', $v);
    switch($m_v[0]){
      case 'v':
        $property['volumeNumber'] = $m_v[1];
        break;
      case 't':
        $property['tomo'] = $m_v[1];                                          
        break;
      case 'pt':
        $property['part'] = $m_v[1];                                          
        break;
    }  
  }
  return $property;
}  
/**
 * Traduce un campo de paginas a 'pagination' o 'pageStart, pageEnd', además totalVolumen sis espcifica para monog multivolumen
 *
 * @param $value
 *   string: Texto de paginas
 *
 * @return
 *   array: asociativo con llave pagination o llaves pageStart, pageEnd, o totalVolumen
 */
function migrar_paginas($value){
  $paginas = array();
  if(strstr($value, '<f>')){
     $tags = array("<f>"=>"pageStart", "<l>" =>"pageEnd");
     $migr_value = migrar_valueTag($value, $tags);
     $paginas['pageStart'] = $migr_value['pageStart'];
     $paginas['pageEnd'] = $migr_value['pageEnd'];
  }
  elseif(strstr($value, '-')){
     $migr_value = explode('-',$value);
     $paginas['pageStart'] = $migr_value[0];
     if($migr_value[0]<$migr_value[1]){
       $paginas['pageEnd']  = $migr_value[1];
     }
     else{
       $paginas['pageEnd']  = $migr_value[0]+$migr_value[1];
     }
  }
  elseif(is_numeric($value) OR $value[0]=='[')
    $paginas['pagination'] = $value;
  else{
    if(strstr($value,'v')){
      $migr_value = explode(' ',$value);  
      if($migr_value[1]=='v'){//caso 3 v, 3 v. (1397 p.)  
        $paginas['totalVolumes'] = $migr_value[0];
        if($migr_value[3][0]=='p'){
          if($migr_value[2][0]=='(')
            $paginas['pagination'] = substr($migr_value[2], 1);
          else
            $paginas['pagination'] = $migr_value[2];
            
        }
      }
    }  
  }
  
  return $paginas;
}     
/**
 * Traduce un campo de textos con su idioma q pueden ser del decs como keyword, primarySubject, genre, ...
 *
 * @param $value
 *   Valor del campo en el registro original, puede ser un string o un array
 * @param $def_idioma
 *   string: siglas del idioma por defcto del titulo ('es', 'en', ...)
 *
 * @return
 *   array: arreglo de los textos de decs, donde cada elemento tiene la estructura ('label', '@type')
 */
function migrar_indice($value, $def_idioma, $type=NULL){
  $tags = array('<i>' => '@language', '<s>' => 'qualifier', '<d>' => '@value', '0' => '@value');
  $indices = migrar_valueTag($value, $tags);

  if(!is_array($value)){
    $indices = array($indices);  
  }    
  foreach($indices as $k=>$indice){
    if(!isset($indice['@language']) AND $def_idioma){
      $indice['@language'] = $def_idioma;  
    }
    if(isset($indice['qualifier']) ){
      $indice['@value'] .= '/'. $indice['qualifier'];
      unset($indice['qualifier']);
      if(is_array($type)){
        foreach($type as $i => $untype){
          if($untype != "Topic")  
            $type[$i] = "DescriptorQualifierPair";  
        }  
      }
      else
        $type = "DescriptorQualifierPair";    
    }
    elseif(!$type)
      $type="TopicalDescriptor";
       
    $new_indices[$k]= array("label"=>$indice, "@type"=>$type);
  } 

  return $new_indices;
}

/**
 * Traduce un campo de titulo o titulo en ingles a la estructura ('@value', '@language')
 *
 * @param $value
 *   string: Texto del título
 * @param $language
 *   string: siglas del idioma por defcto del titulo ('es', 'en', ...)
 *
 * @return
 *   array: arreglo de los titulos del documento, donde cada elemento tiene la estructura ('@value', '@language')
 */
function migrar_titulo($value, $language, $name=NULL){
  $tags = array('<i>' => '@language', '0' => '@value');
  $titles = migrar_valueTag($value, $tags);

  if($name){
   if(is_assoc_array($name))
     $new_titles = array($name);  
   else   
     $new_titles = $name;
  }
  if(is_array($value)){
    if(!isset($new_titles))  
      $new_titles = array();  
    foreach($titles as $title){
      if(!isset($title['@language']) AND $language){
        $title['@language'] = $language;  
      }
      $new_titles[] = $title;
    } 
  }
  else{
    if(!isset($titles['@language']) AND $language){
      $titles['@language'] = $language;  
    }
    if(!isset($new_titles))  
      $new_titles = $titles;
    else
      $new_titles[] = $titles;
  }

  return $new_titles;
}

function migrar_affiliation($author, $language){
    $affiliation = setPropertyType($author['affiliation'], 'Organization', $language); 
    if(isset($author['subOrganization'])){
      $subOrganization = setPropertyType($author['subOrganization'], 'Organization', $language);
      $affiliation['subOrganization'] = $subOrganization; 
      unset($author['subOrganization']); 
    }
    if(isset($author['subOrganization1'])){
      $subOrganization = setPropertyType($author['subOrganization1'], 'Organization', $language);
      $affiliation['subOrganization']['subOrganization'] = $subOrganization; 
      unset($author['subOrganization1']); 
    }
    if(isset($author['address'])){
      if(isset($author['address']['addressCountry']) AND $author['address']['addressCountry'] == 's.p'){
        unset($author['address']['addressCountry']); 
      }  
      if(count($author['address'])){
        $affiliation['address'] = $author['address']; 
        $affiliation['address']['@type'] = 'PostalAddress'; 
      }
      unset($author['address']); 
    }
    $author['affiliation'] = $affiliation;
    return $author;
}

/**
 * Traduce un campo de entidad (person o organization) como son: autor personal, autor institucional, tutor, ... a su estructura en el nuevo modelo de datos
 *
 * @param $value
 *   Valor del campo en el registro original, puede ser un string o un array
 *   Ej: "Hernandez Vergel, Lazaro Luis\n      <s1>Hospital Calixto Garcia</s1>\n      <s2>Infomed</s2>\n      <c>La Habana</c>\n      <p>Cuba</p>\n      <r>coord</r>\n   "
 * @param $type
 *   "Person" para autor personal (campos 10 o 16 o 23) , tutor
 *   "Organization" para autor institucional (campos 11 o 17 o 24) o cualquir otra institucion
 *
 * @return
 *   array asociativo: si $value era un string 
 *   Ej: array("@type"=>"Person", "name"=>"Hernandez Vergel, Lazaro Luis", "affiliation"=>array("Infomed","Hospital Calixto García"), "address" => array("addresLocality" => "La Habana", "addressCountry" => "Cuba"), "responsibility" => "coord"}
 *   Ej: array("@type"=>"Organization", "name"=>"Hospital Calixto García")
 *   array de arrays asociativos: si value era un array 
 *   Ej: array(array("@type"=>"Person", "name"=>"Hernandez Vergel, Lazaro Luis", "responsibility" => "coord"}, 
 *             array("@type"=>"Person", "name"=>"Perez, Juan"})
 */
function migrar_entity($value, $type, $language){
  if($type=='Person'){
     $tags = array('<s1>' => 'affiliation', '<s2>' => 'subOrganization', '<s3>' => 'subOrganization1', '<p>' => 'address.addressCountry', 
                   '<c>' => 'address.addressLocality', '<r>' => 'responsibility', '0' => 'name'); 
  }  
  elseif($type=='Organization'){
     $tags = array('<r>' => 'responsibility', '0' => 'name'); 
  }
  $authors = migrar_valueTag($value, $tags);
  if(is_array($value)){
    $new_authors = array();  
    foreach($authors as $author){
      $author['@type'] = $type; 
      $author['name'] = array('@value'=>$author['name'], '@language'=>$language);
      
      if(isset($author['affiliation']) AND $author['affiliation'] != 's.af'){
        $author=migrar_affiliation($author, $language);
      }
      $new_authors[] = $author; 
    }   
  }
  else{
    $authors['@type']= $type; 
    $authors['name'] = array('@value'=>$authors['name'], '@language'=>$language);
    if(isset($authors['affiliation']) AND $authors['affiliation'] != 's.af'){
        $authors=migrar_affiliation($authors, $language);
    }
    $new_authors =$authors;
  }
  return $new_authors;
}
function setPropertyType($property, $type, $language){
  if(is_array($property)){
    $arr_prop = array();  
    foreach($property as $property1){
      if($language)  
        $arr_prop[]= array('name'=>array('@value'=>$property1, '@language'=>$language), '@type'=> $type); 
      else
        $arr_prop[]= array('name'=>array('@value'=>$property1), '@type'=> $type); 
    }   
  }
  else{
    if($language)  
      $arr_prop= array('name'=>array('@value'=>$property, '@language'=>$language), '@type'=> $type); 
    else
      $arr_prop= array('name'=>array('@value'=>$property), '@type'=> $type); 
  }
  return $arr_prop;
}
function author_responsibility($author_resp, $clasificador){
  if($author_resp){
    if(array_key_exists($author_resp,$clasificador))  
        $author_type = $clasificador[$author_resp];
    else
        $author_type = 'contributor';    
  }
  else{
    $author_type = 'author';    
  }
  return $author_type;
}

function authors_byResponsibility($author, $clasificador){
 if(is_assoc_array($author)){
   $arr_auth = array();
   $author_resp = NULL;
   if(isset($author['responsibility'])){
     $author_resp = $author['responsibility'];
     unset($author['responsibility']);
   }
   $author_type = author_responsibility($author_resp, $clasificador);
   $arr_auth[$author_type] = $author;
 }
 else{
   foreach($clasificador as $aut_type)
     $arr_auth[$aut_type] = array();
   //$arr_auth = array('author'=>array(),'editor'=>array(),'translator'=>array(),'contributor'=>array(), 'copyrightHolder'=>array());
   foreach($author as $author1){
     $author_resp = NULL;
     if(isset($author1['responsibility'])){
       $author_resp = $author1['responsibility'];
       unset($author1['responsibility']);
     }
     $author_type = author_responsibility($author_resp, $clasificador);
     $arr_auth[$author_type][] = $author1;
   }
   $arr=$arr_auth;
   foreach($arr as $key=>$value){
     if(!count($value)){
       unset($arr_auth[$key]);  
     }  
   }
 }
 return $arr_auth;
}

/**
 * A partir de los datos de los niveles descritos en el documento se generan los documentos de los cuales es parte (isPartOf de schema). 
 *
 * Por ejemplo una analitica de monografia es parte de una monografia (datos de nivel monografico), 
 * una analítica de serie monografica es parte de una monografia (datos de nivel monografico) y de una serie (datos de nivel serie).
 * 
 * @param $key
 *   string nombre de la llave de originalRecord que se esta analizando para agregar sus datos a isPartOf 
 *   por ej: "v16"
 * @param $v5
 *   string valor del tipo del literarura del documento del que se analizan las partes
 * @param $v6
 *   string valor del nivel de tratamiento del documento del que se analizan las partes
 * @param $v4
 *   string nombr d la bas d datos a la que prtnece el documento del que se analizan las partes
 * @param $prop
 *   arreglo con los datos de la llave ($key) preparados (segun schema) para agregar al documento isParOf
 *   Ej: array('author'=>array('name'=>"juan Perez", "@type": "Person"))
 *       array('name'=>array('@value'=>"Rev. cuba. cir.", "@language": "es"))
 * @param $v9
 *   string valor del tipo de registro del documento del que se analizan las partes, se utiliza para generar el tipo de doc (segun schema) del isPartOf
 * @param $doc_ispart
 *   array asociativo o array de arrays asociativos: Datos de documentos de los q es parte el document (niveles superiores)
 *   Ej: array{"name"=>{"@value"=>"Rev. cuba. cir", "@language"=>"es"}, "@type"=>"Periodical", "levelOfTreatment"=>"s", "typeOfLiterature"=>"S"}
 *
 * @return
 *   array asociativo: con datos del la prop isPartOf del documento, resultado de agregar $prop a $doc_is_part, ademas de las prop @type, typeOfLiterature, levelOfTreatment
 *   Ej: array{"author"=>array("name"=>"juan Perez", "@type": "Person"), 
 *             "name"=>{"@value"=>"Rev. cuba. cir", "@language"=>"es"}, 
 *              "@type"=>"Periodical", "levelOfTreatment"=>"s", "typeOfLiterature"=>"S"}
 */
function migrar_level($key, $v5, $v6, $v4, $prop, $v9, $doc_ispart){
   //se calcula nivel de tratam del documento del cual es parte 
   switch($key){
     case 'v16': 
     case 'v17': 
     case 'v18': 
     case 'v19': 
     case 'v20': 
     case 'v21': 
     case 'v98': 
     case 'v101': 
     case 'v102': 
       $level_p = substr($v6, 1); //se quita la 1a letra, si nivel de tratam del doc es amc, en su nivel superior que es monogr es mc 
       break;               
     case 'v23': 
     case 'v24': 
     case 'v25':
     case 'v26':
     case 'v27':
       $level_p = 'c';//nivel coleccion
       break;  
     case 'v30':
     case 'v31':
     case 'v32':
       $level_p = 's'; //nivel serie        
       break;  
   }  
   //se quita C(onferencia) y P(royecto) del tipo de literatura  
   $type_p = str_replace("C", "", $v5);
   $type_p = str_replace("P", "", $type_p);

   //se calcula @type de documento (de schema) 
   $doc_type = documentType($type_p, $level_p, $v9);

   $arr1 = array('@type'=>$doc_type, 'typeOfLiterature'=>$type_p, 'levelOfTreatment'=>$level_p, 'database'=>$v4);
   //se agrega la prop (titulo o autor)
   if(!$doc_ispart){
     $isPartOf = array_merge($arr1, $prop);
   }
   else{
     if(is_assoc_array($doc_ispart)){
       if($doc_ispart['levelOfTreatment']==$level_p){
         $isPartOf = array_merge_distinct($doc_ispart, $prop);  
       }  
       else{
         $new_isPart = array_merge($arr1, $prop);;
         $isPartOf = array($doc_ispart, $new_isPart);  
       }
     }
     else{
       $isPartOf = $doc_ispart;  
       $exist_p = 0;  
       foreach($doc_ispart as $k_p => $v_p){
         if($v_p['levelOfTreatment']==$level_p){
           $v_p1 = array_merge_distinct($v_p, $prop);
           $isPartOf[$k_p] = $v_p1;
           $exist_p = 1;
           break;  
         }  
       }  
       if(!$exist_p){
         $i = count($doc_ispart);
         $isPartOf[$i] = array_merge($arr1, $prop);  
       }
     }
   }
   return $isPartOf;
}

/**
 * Traduce campo fecha actualizacion (93) a formato DATETIME de mysql
 *
 * @param $value
 *   string: fecha actualizacion
 *
 * @return
 *   array: fecha en a formato DATETIME de mysql (2012/12/25 30:15:09)
 */
function migrar_fecha($value){
  $tags = array("<f>" =>"time", "<i>" =>"",  "<t>" =>"", "0"=>"date");
  $migr_value = migrar_valueTag($value, $tags);
  if(isset($migr_value['date'])){
    $fecha = substr($migr_value['date'],0,4) .'-'. substr($migr_value['date'],4,2) .'-'. substr($migr_value['date'],6,2); 
    if(isset($migr_value['time'])){
      $fecha .= ' '. $migr_value['time']; 
    }
  }
  return $fecha;
}
/**
 * Traduce un campo con subcampos definidos por tags a su estructura en el nuevo modelo de datos
 *
 * A partir de value y los tags conforma el valor del campo en el nuevo modelo
 *
 * @param $value
 *   Valor del campo en el registro original, puede ser un string o un array
 *   Ej: "WA900\n      <a>CUB</a>\n      <b>1987</b>\n      <c>F</c>\n   "
 * @param $tags
 *   array asociativo de los tags que definen los subcampos, con los valores de su traduccion en el nuevo modelo.
 *   si un subcampo no tiene tag, se le asocia el tag '0' y se coloca como último indice
 *   Ej: array('<a>' => 'clasificationNumber', '<b>' => 'autorNumber', '<c>' => 'parts', '<t>' => 'inventory', '0' => 'institution') 
 *   si un subcampo se asocia con una propiedad de una clase en el nuevo modelo, la traduccion del subcampo se asigna como nombre_clase.nombre propiedad
 *   Ej: array('<p>' => 'address.addressCountry') 
 *
 * @return
 *   string: si $value era un string sin tags,   
 *   Ej: "CU1"
 *   array asociativo: si $value era un string con tags  
 *   Ej: array("institution"=>"WA900", "clasificationNumber"=>"CUB", "autorNumber"=>"1997", "parts"=>"F")
 *   array de arrays asociativos: si value era un array de strings con tags 
 *   Ej: array("CU1", array("institution"=>"WA900", "clasificationNumber"=>"CUB", "autorNumber"=>"1997", "parts"=>"F"))
 */
function migrar_valueTag($value, $tags){
  $tag0 = isset($tags[0]) ? 1 : 0;
  if(is_array($value)){
    $res_arr = array();
    foreach($value as $val){
      $arr = explode("\n", $val);
      if(count($arr)==1){    
        $res_arr[] = $tag0 ? array($tags[0] => $val) : $val;
      }
      else{
        $res_arr[] = migrar_tags2obj($arr, $tags);  
      }     
    }
    $res = (count($res_arr)==1)?$res_arr[0]:$res_arr;
  }
  else{
    $arr = explode("\n", $value);
    if(count($arr)==1)
      $res = $tag0 ? array($tags[0] => $value) : $value;
    else{
      $res = migrar_tags2obj($arr, $tags);  
    }
  }    
  return $res;
}                
/**
 * Traduce un arreglo cuyos valores son string con texto de subcampos LILACS a array con índices del nuevo modelo de datos
 *
 * @param $arr
 *   array con texto de subcampos LILACS 
 *   Ej: array("WA900","<a>CUB</a>","<b>1987</b>","<c>F</c>")
 * @param $tags
 *   array asociativo de los tags que definen los subcampos, con los valores de su traduccion en el nuevo modelo.
 *   si un subcampo no tiene tag, se le asocia el tag '0' y se coloca como último indice
 *   Ej: array('<a>' => 'clasificationNumber', '<b>' => 'autorNumber', '<c>' => 'parts', '<t>' => 'inventory', '0' => 'institution') 
 *   si un subcampo se asocia con una propiedad de una clase en el nuevo modelo la traduccion del subcampo se asigna como nombre_clase.nombre propiedad
 *   Ej: array('<p>' => 'address.addressCountry') 
 *   si un subcampo no se asocia a ninguna propiedad de una clase en el nuevo modelo se le asigna ''
 *   Ej: array('<r>' => '') 
 *   
 * @return
 *   array asociativo: con índices del nuevo modelo de datos y valores de los subcampos correspondintes
 *   Ej: array("institution"=>"WA900", "clasificationNumber"=>"CUB", "autorNumber"=>"1997", "parts"=>"F")
 */
function migrar_tags2obj($arr, $tags){
 $obj = array();  
 foreach($arr as $elem ){
   $elem = trim($elem); 
   if(strlen($elem)){
     foreach($tags as $k => $v){
       $arr_v = explode('.',$v);   
       $v_is_array = 0;
       if(strpos($elem,$k)!==false){//si $k es el tag de elem
         if(array_key_exists($v,$obj)){//si ya existe el indice $v en $obj
           $v_is_array = 1;
           if(!is_array($obj[$v])){
             //si existe la llave $v y el valor del objeto en esa prop no es array, se convierte en un array     
             //para casos como '<s1>'  => 'affiliation', '<s2>'  => 'affiliation'; para que $obj['affiliation'] = array('Clínica Cira García', 'Hospital Calizto García')
             $obj[$v] = array($obj[$v]); 
           }  
         }  
         $len_k = strlen($k);  
         if(substr($elem,0,$len_k)==$k){
           if(strlen($v)){   //si $v='', $k no se traduce a ninguna propiedad del objeto
             $val = substr($elem,$len_k,strlen($elem)-(2*$len_k+1));//(2*$len_k+1) len de apertura y cierre de tag     
             if(count($arr_v)==1){
               if(!$v_is_array){
                 $obj[$v]=$val;
               }
               else{
                 $obj[$v][]=$val;
               }
             }  
             else{
               $obj[$arr_v[0]][$arr_v[1]] = $val;
             } 
           }  
           break;
         }
       }
       else{
         if($k=='0'){
           if(count($arr_v)==1){
             if(!$v_is_array){
               $obj[$v]=$elem;
             }
             else{
               $obj[$v][]=$elem;
             }
           }  
           else{
             $obj[$arr_v[0]][$arr_v[1]]=$elem;
           } 
           break;
         }
       }  
  
     }      
   }  
 }
 return $obj;
}

/**
 * Devuelve tipo de documento segun schema.org, a partir de los campos tipo literatura y nivel de tratamiento de lilacs
 *
 * @param $typeOfLiterature
 *   string: tipo de literatura según LILACS 
 * @param $levelOfTreatment
 *   string: nivel de tratamiento según LILACS 
 *   
 * @return
 *   string: tipo de documento segun schema (Book, Article, Collection, Serie, ...)
 */
function documentType($typeOfLiterature, $levelOfTreatment, $v9){
     $type = '';
     switch($levelOfTreatment){
       case 'm':
         switch($typeOfLiterature){
           case 'M':
             $type = 'Book';
             break;  
           case 'T':
             $type = 'Thesis';
             break;  
           case 'N':
           case 'NC':
           case 'NP':
             if($v9){
               switch($v9){
                 case 'e':
                 case 'f':
                   $type = 'Map';
                   break;  
                 case 'i':
                 case 'j':
                   $type = 'AudioObject';
                   break;  
                 case 'g':
                   $type = 'VideoObject';
                   break;  
                 default:
                   $type = 'CreativeWork';
                   break;  
               }
             }
             else{
               $type = 'CreativeWork';
             }
             break;  
         }
         break;
       case 'mc':
         $type = 'PublicationVolume';
         break;  
       case 'ms':
         switch($typeOfLiterature){
           case 'MS':
           case 'MSC':   //??
           case 'MSP':   //??
           case 'MSCP':  //??
             $type = 'PublicationVolume';
             break;
           case 'TS':
             $type = 'Thesis';
             break;
         }    
         break;  
       case 'am':
         switch($typeOfLiterature){
           case 'M':
           case 'T':
             $type = 'Chapter';
             break;
           case 'N':
           case 'NC':
           case 'NP':
             if($v9){
               switch($v9){
                 case 'e':
                 case 'f':
                   $type = 'Map';
                   break;  
                 case 'i':
                 case 'j':
                   $type = 'AudioObject';
                   break;  
                 case 'g':
                   $type = 'VideoObject';
                   break;  
                 default:
                   $type = 'CreativeWork';
                   break;  
               }
             }
             else{
               $type = 'CreativeWork';
             }
             break;
         }    
         break;  
       case 'amc':
         $type = 'Chapter';
         break;  
       case 'ams':
         $type = 'Chapter';
         break;  
       case 'as':
         $type = 'Article';
         break;  
       case 'c':
         $type = 'Collection';
         break;  
       case 's':
         switch($typeOfLiterature){
           case 'MS':
           case 'TS':
             $type = 'BookSeries';
             break;
           case 'S':
             $type = 'Periodical';
             break;
         }    
         break;  
     }
  return $type;
}         
/* Chequea si un arreglo esta incluido dentro de otro (si arr1 esta en arr2)
*
* Recorre arr1 y detrmina si alguna llave no esta incluida en arr2, sino analiza si el valor en la llave esta incluido en el valor en la misma llave de arr2, 
* teniendo en cuenta el tipo de valor (string, array asociativo, array no asociativo)
* 
* @param $arr1
*   array el arreglo que se busca 
* @param $arr2
*   array arreglo donde se busca
*
* @return 
*   0: si arr1 NO esta incluido en arr2
*   1: si arr1 no esta incluido en arr2
*/
function array_in_array($arr1, $arr2){
  foreach($arr1 as $k =>$v){
    if(!isset($arr2[$k]))  //no existe la llave k en arr2
      return 0;
    if(!is_array($arr2[$k])){
      if(is_array($arr1[$k])){   //arr2[k] no es array y arr1[k] si
        return 0;
      }  
      if($arr1[$k] != $arr2[$k]){ //arr1[k] y arr2[k] no son array y son diferents
        return 0;
      }  
    }
    else{
      if(!is_assoc_array($arr2[$k])){
        if(is_assoc_array($arr1[$k])){
          if(!in_array($arr1[$k], $arr2[$k])){
            return 0;
          }  
        }
        else{//arr1[k] y arr2[k] con indice numerico, ninguno asociativo
          foreach($arr1[$k] as $v1){
            if(!in_array($v1, $arr2[$k])){ //alguno de los elem de arr1[k] no esta en arr2[k] 
              return 0;
            }  
          }  
        }
      }
      else{//arr2[k] es asociativo
         if(!is_assoc_array($arr1[$k])){ //arr1[k] no es asociativo
           return 0;
         }  
         if(array_diff_assoc($arr1[$k], $arr2[$k])){ //los dos son asociativos y diferentes   
          return 0;
         } 
      }
    }   
  }  
  return 1;
}
/*Falta: 
*
* Revisar @type a partir de campos 111-115, en el caso de Map chequear MapCategoryType para llenar mapType 
* 
*/
?>
