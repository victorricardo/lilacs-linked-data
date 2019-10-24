<?php
require($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require_once(PATH_LILACS_LD."db/commonDB.php");
require_once(PATH_DECS_LD."get_decs_uri.php"); //decs rdf
require_once(PATH_DECS_LD."get_decs_data.inc"); //decs ws

set_time_limit(0); 
$ini=time(); 

//PENDIENTE: agregar label en los 3 idiomas para facilitar las busquedas de texto, pq no se puede hacer join con los id
//PENDIENTE: consultas con capa BD

setDecsUris($link);

$total=(time()-$ini)/60;
echo "Tiempo de ejecucion: $total minutos<BR>";

function setDecsUris($link){
  $upd = $err = $ii= 0;  
  $t1=time();

  $table = "Document";
  $count = getCount($link, $table);
  $ctdad = intval($count/1000);
  $upd=0;

  for($i=0;$i<=$ctdad;$i++){
    $start = $i*1000;

    $sql = "SELECT id, data FROM $table LIMIT $start,1000";  
    //$sql = "SELECT id, data FROM $table LIMIT 0,10";  
    $result = mysqli_query($link, $sql);
    //echo mysqli_num_rows($result)."<BR>";
    while ($row = mysqli_fetch_assoc($result)) {
      $data_arr = json_decode($row['data'],true);
    
      list($u, $e) = setDecsUrisById($table, $data_arr, $row['id'], $link);
      $upd += $u;
      if($err){
        $err += $e;
        echo "Error asignando datos enlazados del DeCS en document id: ".$row['id'];
      }

      $ii++;
      //$t1=(time()-$t1); 
      echo "Registro $ii <BR>";
      if($ii==3)
        break;     //descomentar para probar solamente en el primer registro
    }
    mysqli_free_result($result);
  }
  echo "En $table se actualizaron $upd registros con uris de decs<BR>";
  
  if($err)
    echo ", no se pudieron actualizar $err registros con uris de decs por errores ocurridos";
  echo "<BR>";    
}

function setDecsUrisById($table, $data_arr, $id, $link){
    $upd=$err=0;
    
    $objectLD= putObjDecsLD($data_arr, $id, $link); 
    if(count($objectLD)){
      $json_str = array_to_objStr($objectLD, $link,0,1);
      //echo $json_str;

      //chequear si cadena $json_str es correcta para pasar por JSON_OBJECT()
      if(!json_argument_error($json_str,$link)){
        //si json_str correcto ejecutar consulta
        $sql= "UPDATE $table SET data = JSON_REPLACE(data, $json_str) WHERE id=". $id; 
        $resulti = mysqli_query($link, $sql);
        if (!$resulti) {
          printf("setDecsUrisById SQL is %s!<BR>", $sql);
          echo 'MySQL Error: ' . mysqli_error($link)."<BR>";
          $err = 1;
        } 
        else
          $upd = 1;
      }
      else
        $err = 1; 
    }  
    return array($upd, $err);
}
//asignar uris del decs
function putObjDecsLD($object){
  $objLD = array();  
  $searched = array();  //se guardan los datos q se han buscado en decs por si se repiten en dif propiedades del doc
  
  foreach($object as $key => $value){
    if(in_array($key,array('about','genre','subjectLimit','primarySubject','secondarySubject','keywords'))){
      $upd = 0;
      if(is_array($value)){
        if(is_assoc_array($value)){ //un objeto
          $label = $value['label']['@value'];

          if(!isset($searched[$label])){//si no ha sido buscado
            echo "<BR>SEARCHED<BR>";
            print_r($searched);
            echo "LABEL $label<BR><BR>";
            $valueLD = putValueDecsLD($value);  

            if($valueLD)
              $searched[$label] = array("@id"=>$valueLD["@id"], "id"=>$valueLD["id"]);
            else  
              $searched[$label] = NULL;
          }  
          else{
            if($searched[$label])  
              $valueLD = array_merge($value,$searched[$label]);  
          }
        }
        else{ //arreglo indice numerico
          $valueLD = array();  
          foreach($value as $assoc_value){
            if(is_array($assoc_value) AND is_assoc_array($assoc_value) ){
              $label = $assoc_value['label']['@value'];

              if(!isset($searched[$label])){//si no ha sido buscado
                $vLD = putValueDecsLD($assoc_value);  
                if($vLD){
//print_r($vLD); exit;
                  $valueLD[] = $vLD;
                  $searched[$label] = array("@id"=>$vLD["@id"], "id"=>$vLD["id"]);
                  $upd = 1;
                }
                else{
                  $valueLD[] = $assoc_value;
                  $searched[$label] = NULL;
                }
              }
              else{
                $valueLD[] = array_merge($assoc_value,$searched[$label]);
                $upd = 1;
              }  
            } 
            else{
              $valueLD[] = $assoc_value;
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
  }
  if(count($objLD))
    return $objLD;
  else
    return NULL;  
    
}
function putValueDecsLD($value){
   global $decs_path;
    
   $valueLD = array();
   $upd = 0; 
  //si no tiene asignado id
  //OJO!!! considerar que tenga id y que haya q actualizar por diferencias en modified 
  if(isset($value['@type']) AND !isset($value['@id']) AND isset($value['label'])){
    if(is_array($value['@type'])){
      foreach($value['@type'] as $untype){
        if($untype!="Topic"){ //no analizar Topic sino los tipos mas especificos
          $type = $untype;
          break;  
        }
      }  
    }
    else{
      $type = $value['@type'];
    }
    
    if(in_array($type, array('CheckTag', 'TopicalDescriptor', 'PublicationType','DescriptorQualifierPair'))){
      $item = $uri = NULL;
      //se busca en decs migrado
      $lang = $value['label']['@language'];
      $json_label[$lang] = '{"@value":"'.$value['label']['@value'].'", "@language":"'. $lang.'"}';
        
      if($type != 'DescriptorQualifierPair' AND strpos($value['label']['@value'],"/")===FALSE){
        $table = 'Descriptor';  
      }
      else{
        $table = 'DescriptorQualifierPair';  
      }  
      echo "Buscando $json_label[$lang] en DeCS RDF<BR>";
      $uri = get_OneUriByLabel($json_label, $table);
      if(!$uri ){
        echo "No encontrado en DeCS RDF, se busca con ws <BR>";
        //si no lo encuentra se busca en decs con web serv
        if($table == 'Descriptor'){  
          $item = get_item_by_label($value['label']['@value'], '101', $lang);//101 busq exacta de descriptor 
          if($item AND $item['record_list']['record']['unique_identifier_nlm']){
            $uri = $decs_path['Descriptors']['path'] . $item['record_list']['record']['unique_identifier_nlm'];
            $id = $item['record_list']['record']['unique_identifier_nlm'];
          }  
        }
        else{
          list($D,$Q)=explode("/",$value['label']['@value']);
          echo "DQP: $D/$Q<BR>";
          $item = get_item_by_label($D, '101', $lang);//101 busq exacta de descriptor 
          if($item AND $item['record_list']['record']['unique_identifier_nlm'])
            $Didentifier = $item['record_list']['record']['unique_identifier_nlm'];
          $item1 = get_item_by_label($Q, '401', $lang);//401 busq de qualifier por palabras
          if($item1 AND $item1['record_list']['record']['unique_identifier_nlm'])
            $Qidentifier = $item1['record_list']['record']['unique_identifier_nlm'];
          else{ //puede ser calificador propio de decs
            $json_label[$lang] = '{"@value":"'.$Q.'", "@language":"'. $lang.'"}';
            $uri = get_OneUriByLabel($json_label, 'Qualifier');
            $Qidentifier = substr($uri,-8);//los calificadores propios tienen 8 caracteres ej: _Q000001
          }
          if(isset($Didentifier) AND isset($Qidentifier)){
            $uri = $decs_path['DescriptorQualifierPairs']['path'] . $Didentifier . $Qidentifier;
            $id = $Didentifier . $Qidentifier;
          }  
        }
      }  
      if($uri){
        echo "Encontrado: $uri <BR>";
        $valueLD = $value;
        $valueLD['@id'] = $uri;
        if(isset($id))
          $valueLD['id'] = $id;
        else{
          $parts = explode("/",$uri);
          $last = count($parts) -1;
          $valueLD['id'] = $parts[$last];
        }
      }
    }  
  }
  return $valueLD;  
}

?>

