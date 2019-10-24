<?php
/* Llamado a los metodos del cliente del ws restful de DeCS-LD
 * Las propiedades validas de cada tipo de entidad estan descritas en: 4.1 Clases DeCS (MESH).docx 
 */
require('decsClient.php');

set_time_limit(0); 
$ini=time(); 

$url = "http://localhost/apps-ld/decs-ld/restws/index.php";

//$metodo = "GET";
//$metodo = "POST";
$metodo = "PUT";
//$metodo = "DELETE";

//tipo de entidad
$params["entityType"] = "Descriptors";
//$params["entityType"] = "Qualifiers";
//$params["entityType"] = "DescriptorQualifierPairs";
//$params["entityType"] = "Concepts";
//$params["entityType"] = "Terms";
//$params["entityType"] = "TreeNumbers";
//$params["entityType"] = "SupplementaryConceptRecords";
//$params["entityType"] = "users";//usuarios del sistema, tienen api_key, email,permisos 

//solicitar api_key
//$params["entityType"] = "api_key";
if($params["entityType"] == "api_key"){
  $metodo = "POST";  
  $params["email"] = "yazna3@inf.sld.cu";  
}
else{
 $params["api_key"] = "2e33aed76fa2b0b403a0cfda9ee74283";  
 switch($metodo){
  case "GET":  
    switch($params["entityType"]){
      case "Descriptors":  
        //GET recurso especifico por identifier, acepta ademas parametro fields, el resto lo ignora
        //identifier: identificador unico de entidad (ej D012345 o _D000123)
        //$params["identifier"] = "D000002";
        //$params["identifier"] = "_D000012";
        //$params["identifier"] = "D0012";//no valido
        
        //limitar las propiedades a devolver en los resultados. Nombres de prop separados por coma
        //$params["fields"] = "@context,@id,label.@value,@type,treeNumber,allowableQualifier,modified";
        $params["fields"] = "@id,label.@value";
        //$params["fields"] = "cccc,bbbb";//propiedades no validas, retorna error

        //q: busqueda de texto en cualquier parte de cualquier propiedad 
        //$params["q"] = utf8_encode("Política"); //hay q pasarlo por utf8_encode
        //$params["q"] = "Sanitaria"; 

        //busqueda en (cualquier parte) de un campo especifico: nombre campo, valor  
        $params["@type"] = "PublicationType"; 
        //$params["label"] = "Article"; 
        //$params["label"] = utf8_encode("Clínico"); //hay q pasarlo por utf8_encode 
        $params["label.@language"] = "es"; //dentro de una prop q es array 
        $params["dateEstablished"] = "2016"; 

        //ordenando los resultados por un campo especifico: nombre campo  (uno solo)
        //$params["sort_by"] = "identifier";
        //$params["sort_by"] = "@type";
        $params["sort_by"] = "label.@value";
        //$params["sort_by"] = "label";//es un array, en estos casos no queda claro como ordena??
        break;
      
      case "Qualifiers":
        //GET recurso especifico por identifier: identificador unico de entidad (ej Q000005 o _Q000123)
        //$params["identifier"] = "Q000002";
        //$params["identifier"] = "_Q000012";
        //$params["identifier"] = "Q0012001200";//no valido
        
        //fields: limitar campos a devolver en los resultados. Nombres de campos separados por coma
        //$params["fields"] = "data,@id,label";//devuelve data completo que incluye @id y name
        //$params["fields"] = "@id,label,broaderQualifier";

        //q: busqueda de texto en cualquier parte de cualquier propiedad 
        $params["q"] = utf8_encode("análisis"); //hay q pasarlo por utf8_encode 

        //$params["label"] = utf8_encode("administración"); 
        //$params["label"] = "dosage"; 

        //ordenando los resultados por un campo especifico: nombre campo  (uno solo)
        //$params["sort_by"] = "id";
        $params["sort_by"] = "created";
        break;
      case "DescriptorQualifierPairs":
        //GET recurso especifico por identifier: identificador unico de entidad, union de identificador de descriptor y calificador (ej D000001Q000005, _D000001Q000123, ...)
        //$params["identifier"] = "D000001Q000276";
        //$params["identifier"] = "_D002231Q000502";
        //$params["identifier"] = "Q0012001200";//no valido
        
        //limitar las propiedades o campos a devolver en los resultados. Nombres de campos separados por coma
        $params["fields"] = "@id,label.@value,nuevo";//prop nuevo no existe, la ignora y devuelve el resto 

        //q: busqueda de texto en cualquier parte de cualquier propiedad 
        $params["q"] = utf8_encode("diagnóstico"); 

        $params["label"] = "image"; 
        //$params["hasQualifier"] = "Q000000981"; 
        //$params["hasDescriptor"] = "D000360"; 

        //ordenando los resultados por un campo especifico: nombre campo  (uno solo)
        //$params["sort_by"] = "id";
        $params["sort_by"] = "hasDescriptor";
        break;
      case "Concepts":
        break;
      case "Terms":
        break;
      case "TreeNumbers":
        break;
      case "SupplementaryConceptRecords":
        break;
      case "users":
        //limitar las propiedades o campos a devolver en los resultados. Nombres de campos separados por coma
        $params["fields"] = "api_key";

        //q: busqueda de texto en cualquier parte de cualquier propiedad 
        //$params["q"] = "yay"; 

        //ordenando los resultados por un campo especifico: nombre campo  (uno solo)
        $params["sort_by"] = "created";
        break;
    }
    //orden descendente, si ascendente no hace falta llenarlo q es el valor por defecto  
    //$params["sort_order"] = "desc";

    //paginacion  
    $params["page_size"] = 10; //ctdad de resultados a retornar por pagina, por defecto 10
    //$params["page"] = 2; //numero de pagina, por defecto 1

    break;
  
  case "POST": //insert
    switch($params["entityType"]){
      case "Descriptors":  
        $data_arr=array();
        $params['data'] = utf8_encode(json_encode($data_arr));
        break;
      case "Qualifiers":
        $data_arr=array();
        $params['data'] = utf8_encode(json_encode($data_arr));
        break;
      case "DescriptorQualifierPairs":
        $data_arr=array();
        $params['data'] = utf8_encode(json_encode($data_arr));
        break;
      case "Concepts":
        break;
      case "Terms":
        break;
      case "TreeNumbers":
        break;
      case "SupplementaryConceptRecords":
        break;
      case "users":
        $params["permisos"] = json_encode(array('GET'=>array("users"), 
                                          'PUT'=>array('Descriptors', 'users'),
                                          'POST'=>array('Descriptors', 'users')));  
        break;
    }
    break;  

  case "PUT": //update
    switch($params["entityType"]){
      case "Descriptors":  
        $params['identifier'] = "D000002";
        //agregando traducciones
        /*$params['data'] = utf8_encode('{"historyNote":[{"@value":"96; was ABATE 1972-95 (see under INSECTICIDES, ORGANOTHIOPHOSPHATE 1972-90)", "@language": "en"},{"@value":"96; era ABATE 1972-95 (ver bajo INSECTICIDAS, ORGANOTHIOPHOSPHATO 1972-90)","@language":"es"},{"@value": "96; era ABATE 1972-95 (ver bajo INSECTICIDOS, ORGANOTHIOPHOSPHATO 1972-90)", "@language": "pt"}], "publicMeSHNote": [{"@value": "96; was ABATE 1972-95 (see under INSECTICIDES, ORGANOTHIOPHOSPHATE 1972-90)", "@language": "en"}, {"@value": "96; era ABATE 1972-95 (ver bajo INSECTICIDAS, ORGANOTHIOPHOSPHATO 1972-90)", "@language": "es"},{"@value": "96; era ABATE 1972-95 (ver bajo INSECTICIDOS, ORGANOTHIOPHOSPHATO 1972-90)", "@language": "pt"}], "previousIndexing": [{"@value": "Insecticides (1966-1971)", "@language": "en"}, {"@value": "Insecticidas (1966-1971)", "@language": "es"}, {"@value": "Insecticidos (1966-1971)", "@language": "pt"}]}');*/
        $data_arr = array(
                    "historyNote"=> array(
                                        array("@value"=> "96; was ABATE 1972-95 (see under INSECTICIDES, ORGANOTHIOPHOSPHATE 1972-90)", "@language"=> "en"),
                                        array("@value"=> "96; era ABATE 1972-95 (ver INSECTICIDAS, ORGANOTIOFOSFATO 1972-90)", "@language"=> "es"),
                                        array("@value"=> "96; was ABATE 1972-95 (see under INSECTICIDAAS, ORGANOTHIOPHOSPHATEE 1972-90)", "@language"=> "pt")
                                        ),
                    "publicMeSHNote"=> array(
                                        array("@value"=> "96; was ABATE 1972-95 (see under INSECTICIDES, ORGANOTHIOPHOSPHATE 1972-90)", "@language"=> "en"),
                                        array("@value"=> "96; era ABATE 1972-95 (ver INSECTICIDAS, ORGANOTIOFOSFATO 1972-90)", "@language"=> "es"),
                                        array("@value"=> "96; was ABATE 1972-95 (see under INSECTICIDAAS, ORGANOTHIOPHOSPHATEE 1972-90)", "@language"=> "pt")
                                        ),                                        
                    "previousIndexing"=> array(
                                        array("@value"=> "Insecticides (1966-1971)", "@language"=> "en"),
                                        array("@value"=> "Insecticidas (1966-1971)", "@language"=> "es"),
                                        array("@value"=> "Insecticidees (1966-1971)", "@language"=> "pt")
                                        )                                        
                         );
        $params['data'] = utf8_encode(json_encode($data_arr));
        
         break;
      case "Qualifiers":
        //$params['data'] = '{"trabajo": "INFOMED"}';//propiedad q no existe en ctx, no se actualiza
        $params['data'] = '{"email": "ivankotp@gmail.com","name.@value": "Ivanko, Taras Petrovich"}';
        break;
      case "DescriptorQualifierPairs":
        $params['id'] = 18;
        $params['data'] = '{"email": ["editorial@infomed.sld.cu"], "sameAs": "http://www.editorial.sld.cu/", "address": {"@type": "PostalAddress", "streetAddress": "23 esq J", "addressCountry": "Cuba", "addressLocality": "La Habana"}}';
        break;
      case "Concepts":
        break;
      case "Terms":
        break;
      case "TreeNumbers":
        break;
      case "SupplementaryConceptRecords":
        break;
      case "users":
        $params['id'] = 4;
        $params["data"] = json_encode(array('email' => "yagv2@infomed.sld.cu",
                                            'permisos'=>
                                             array('GET'=>array("users"), //GET solo se chequea para "users" 
                                                  'PUT'=>array('Descriptors','users'),
                                                  'POST'=>array('Descriptors','users'),
                                                  'DELETE'=>array('users'))));  
        //error no pueden modificarse id, ni api_key
        //$params["data"] = json_encode(array('identifier' => 'D0000022', 'api_key'=> "89765"));
        break;
    }
    break;  

  /*case "DELETE"://no se debe usar, en mesh se marcan con [obsolete], su uso no se restringe solo a otras entidades de DeCS, por ej se usa en datos lilacs 
    $params['identifier'] = 'D0000022';
    break; */ 
 }
}
//echo "$metodo, $url, ".print_r($params,true); exit;

echo execRequest( $metodo, $url, $params);
$total=(time()-$ini)/60;
//echo "Tiempo de ejecucion: $total minutos<BR>";

?>
