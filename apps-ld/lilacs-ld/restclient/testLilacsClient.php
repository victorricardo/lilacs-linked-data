<?php
require('lilacsClient.php');

set_time_limit(0); 
$ini=time(); 

$url = "http://localhost/apps-ld/lilacs-ld/restws/index.php";

$metodo = "GET";
//$metodo = "POST";
//$metodo = "PUT";
//$metodo = "DELETE";

//tipo de entidad
$params["entityType"] = "documents";
//$params["entityType"] = "persons";
//$params["entityType"] = "organizations";
//$params["entityType"] = "events";
//$params["entityType"] = "projects";
//$params["entityType"] = "users";//usuarios del sistema, tienen api_key, email,permisos 

//identificador unico de entidad (entero sin signo)
//$params["id"] = 4;

//solicitar api_key
//$params["entityType"] = "api_key";
if($params["entityType"] == "api_key"){
  $metodo = "POST";  
  $params["email"] = "yagv1113@alianza.co.cu";  
}
else{
 $params["api_key"] = "c795bc78390a8a956a1d64d4a6444e4d";  
 switch($metodo){
  case "GET":  
    //busqueda de un recurso especifico por su id, puede conmbinarse con parametro fields, el resto lo ignora
    //identificador unico de entidad (entero sin signo)
    //$params["id"] = 5;

    switch($params["entityType"]){
      case "documents":  
        //limitar las propiedades o campos a devolver en los resultados. Nombres de campos separados por coma
        //$params["fields"] = "@context,id,name,publisher,provider.name.@value";
        $params["fields"] = "data";

        //q: busqueda de texto en cualquier parte de cualquier propiedad 
        $params["q"] = "general";

        //busqueda en (cualquier parte) de un campo especifico: nombre campo, valor  
        //$params["@type"] = "Article"; 
        //$params["name"] = "cirugía"; //problema con acento, no recupera nada
        //$params["name"] = utf8_encode("cirugía"); //hay q pasarlo por utf8_encode 
        //$params["provider.name.@value"] = "INFOMED"; 
        //$params["provider.code"] = "CU1"; 
        //$params["name"] = "perro"; 

        //ordenando los resultados por un campo especifico: nombre campo  (uno solo)
        $params["sort_by"] = "datePublished";
        //$params["sort_by"] = "id";
        //$params["sort_by"] = "@type";
        //$params["sort_by"] = "provider.branchCode";
        //$params["sort_by"] = "name";//es un array, en estos casos no queda claro como ordena??
        break;
      
      case "persons":
        //limitar las propiedades o campos a devolver en los resultados. Nombres de campos separados por coma
        $params["fields"] = "data,@id,name";//devuelve data completo que incluye @id y name

        //q: busqueda de texto en cualquier parte de cualquier propiedad 
        $params["q"] = utf8_encode("Pérez"); //hay q pasarlo por utf8_encode 

        $params["name"] = "Juan"; 

        //ordenando los resultados por un campo especifico: nombre campo  (uno solo)
        //$params["sort_by"] = "id";
        $params["sort_by"] = "created";
        break;
      case "organizations":
        //limitar las propiedades o campos a devolver en los resultados. Nombres de campos separados por coma
        $params["fields"] = "id,name,email,member";

        //q: busqueda de texto en cualquier parte de cualquier propiedad 
        //$params["q"] = "INFOMED"; 

        $params["name"] = "Biblioteca"; 

        //ordenando los resultados por un campo especifico: nombre campo  (uno solo)
        //$params["sort_by"] = "id";
        $params["sort_by"] = "telephone";
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
      case "documents":  
        $params['data'] = utf8_encode('{"_id": 222, "name": [{"@value": "Protesis", "@language": "es"}, {"@value": "Prosthesis", "@language": "en"}], "@type": "Book", "typeOfLiterature":"M", "levelOfTreatment":"m", "provider":{"branchCode":"CU1", "@type": "Organization"}, "inLanguage":"es", "primarySubject":{"id": "D111199", "@id": "http://decs.sld.cu/Descriptors/D111199", "@type": ["Topic","TopicalDescriptor"]}, "about": [{"id": "D111190", "@id": "http://decs.sld.cu/Descriptors/D111190", "@type": ["Topic","TopicalDescriptor"], "label": {"@value": "Cuello", "@language": "es"}}], "author": {"name": "Pérez Perez, Juan", "@type": "Person"}, "pageEnd": 105, "database": "CUMED"}');
        break;
      case "persons":
        $params['data'] = utf8_encode('{"name": {"@value": "García Vega, Yazna", "@language": "es"}, "@type": "Person"}');
        break;
      case "organizations":
        $params['data'] = utf8_encode('{"name": {"@value": "Centro Provincial de Información de Ciencias Médicas de Artemisa", "@language": "es"}, "@type": "Organization", "email": ["cpicm@art.sld.cu"], "member": {"name": {"@value": "Juan Perez", "@language": "es"}, "@type": "Person"}, "sameAs": "http://www.cpicm.art.sld.cu/", "address": {"@type": "PostalAddress", "streetAddress": "1ra nº 109 e/ 2 y 4", "addressCountry": "Cuba", "addressLocality": "Artemisa"}, "branchCode": "CU555.1", "additionalType": ["Centro Cooperante da BVS", "SCAD", "Rede LILACS"]}');
        break;
      case "users":
        $params["permisos"] = json_encode(array('GET'=>array("documents","persons","organizations","events","projects","users"), 
                                          'PUT'=>array('users'),
                                          'POST'=>array('users')));  
        break;
    }
    break;  

  case "PUT": //update
    $params['id'] = 5;
    switch($params["entityType"]){
      case "documents":  
        //$params['data'] = array("id"=> 2, "@type"=> "Chapter", "database"=> "CUMED");
        $params['data'] = '{"_id": 2, "name": [{"@value": "Protesis de cuello.", "@language": "es"}, {"@value": "Neck prosthesis", "@language": "en"}], "@type": "Article", "about": [{"id": "D111190", "@id": "http://decs.sld.cu/Descriptors/D111190", "@type": ["Topic","TopicalDescriptor"], "label": {"@value": "Cuello", "@language": "es"}}], "author": {"name": "Perez, PP", "@type": "Person"}, "database": "CUMED"}';
        break;
      case "persons":
        //$params['data'] = '{"trabajo": "INFOMED"}';//propiedad q no existe en ctx, no se actualiza
        $params['data'] = '{"email": "ivankotp@gmail.com","name.@value": "Ivanko, Taras Petrovich"}';
        break;
      case "organizations":
        $params['id'] = 18;
        $params['data'] = '{"email": ["editorial@infomed.sld.cu"], "sameAs": "http://www.editorial.sld.cu/", "address": {"@type": "PostalAddress", "streetAddress": "23 esq J", "addressCountry": "Cuba", "addressLocality": "La Habana"}}';
        break;
      case "users":
        $params['id'] = 6;
        $params["data"] = json_encode(array('email' => "yagv2@alianza.co.cu",
                                            'permisos'=>
                                             array('GET'=>array("documents","persons","organizations","events","projects","users"), 
                                                  'PUT'=>array('users'),
                                                  'POST'=>array('users'),
                                                  'DELETE'=>array('users'))));  
        //error no pueden modificarse id, ni api_key
        //$params["data"] = json_encode(array('id' => 106, 'api_key'=> "89765"));
        break;
    }
    break;  

  case "DELETE":
    //$params['id'] = 183;//imposible eliminar, es parte de otros doc
    $params['id'] = 1;
    //$params['id'] = 20;
    break;  
 }
}
//echo "$metodo, $url, ".print_r($params); exit;

echo execRequest( $metodo, $url, $params);
$total=(time()-$ini)/60;
//echo "Tiempo de ejecucion: $total minutos<BR>";

?>
