<?php
/**
 * @file
 * Definición de variables globales
 */
//path (file system) decs-ld y lilacs-ld
require_once($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require_once(PATH_COMMON_LD."getContext.inc");//funciones de ctx

 //url del decs
define("URL_DECS_LD", "http://decs.sld.cu/");

//url del mesh
define("URL_MESH", "http://id.nlm.nih.gov/mesh/");

//camino de rdfconvert (ficheros nt y json con datos de mesh rdf)
define("PATH_RDFCONVERT", "c:/rdfconvert/bin/");

//camino de logs
define("PATH_LOGS", PATH_DECS_LD."logs/");

//url del servicio decs para obtener catetegorias propias y traducciones
$BASEURL = "http://decs.bvsalud.org/cgi-bin/mx/cgi=@vmx/decs/";

//categorias propias del decs
$cat_decs = array('SH','HP','SP','VS');

//Correspondencia e/ los TreeNumber de calificadores en DeCS y MeSH, Y** se utiliza en MeSH y Q** en DeCS (se diferencian solo en los 3 primeros caracteres)
$arr_treeN = array('Y01'=>'Q05', 'Y02'=>'Q10', 'Y03'=>'Q15', 'Y04'=>'Q20', 'Y05'=>'Q35', 'Y06'=>'Q40', 'Y07'=>'Q45', 'Y08'=>'Q50', 
                   'Y09'=>'Q55', 'Y10'=>'Q60', 'Y11'=>'Q65', 'Y19'=>'Q17', 'Y21'=>'Q22', 'Y23'=>'Q25', 'Y25'=>'Q30', 'Y27'=>'Q36', 
                   'Y29'=>'Q37', 'Y31'=>'Q38', 'Y33'=>'Q39', 'Y35'=>'Q42', 'Y37'=>'Q52', 'Y39'=>'Q53', 'Y41'=>'Q70' );

/* A partir del treeNumber de un calificador de MeSH devuelve el treeNumber de este calificador en DeCS
 *
 * @param $treeNumber
 *   string: numero de arbol del calificador en MeSH
 * @param $arr_treeN
 *   array: arreglo asociativo con la correspondencia e/ los numeros de arbol de mesh y decs
 *
 * @return
 *   strin: treeNumber del calificador en decs o NULL si no existe
 */
function treeNumberMesh2Decs($treeNumber, $arr_treeN){
  $parts = explode('.', $treeNumber);  
  if(array_key_exists($parts[0],$arr_treeN)){
    $parts[0]=$arr_treeN[$parts[0]];  
    return implode('.',$parts);
  }
  return NULL;
}

//decs_path: arreglo asociativo que para cada tipo de entidad contiene un arreglo con (path: el camino en decs y len: la longitud del mismo) 
$entities =  array('Descriptors', 'Qualifiers', 'Concepts', 'Terms', 'TreeNumbers', 'DescriptorQualifierPairs', 'SupplementaryConceptRecords');
foreach($entities as $entity){
  $path = URL_DECS_LD."$entity/";  
  $decs_path[$entity] = array('path'=> $path, 'len'=>strlen($path));
}

$context = file_get_contents(PATH_DECS_LD."context.jsonld") ;
$decs_ctx = json_decode($context, 1);//context como arreglo asociativo
$allowed_decs_prop = getAllowedProperties($decs_ctx);//arreglo de nombres de las propiedades del modelo de datos de DeCS-ld, PENDIENTE: diferenciar por tipo entidad?
//propiedades q son arreglos de objetos (prop de texto libre q tienen traducciones)
$decs_objectsArray_prop = array('label', 'annotation', 'considerAlso', 'historyNote', 'onlineNote', 'previousIndexing', 'publicMeSHNote', 'note', 'source', 'scopeNote', 'altLabel', 'prefLabel')
?>
