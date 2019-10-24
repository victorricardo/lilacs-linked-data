<?php
require($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require(PATH_COMMON_LD."array2json.inc");

$link = mysqli_connect('localhost', 'root', 'root');
if (!$link) {
    die('Could not connect: ' . mysqli_error($link));
}
$dbname = 'lildbi';
if (!mysqli_select_db($link, $dbname)) {
    echo 'Could not select database $dbname';
    exit;
}
  $arr_orgs = array();
  
  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Nacional de Información de Ciencias Médicas - INFOMED', '@language'=>'es');
  $arr_org['branchCode'] = "CU1";
  $arr_org['memberOf'] = array('Viceministerio de Desarrollo','Ministerio de Salud Pública');
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['sameAs'] = "http://www.infomed.sld.cu/";
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Damiana Martín Laurencio","jobTitle"=>"Directora","email" => "damiana@infomed.sld.cu");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Habana","addressCountry"=>"Cuba","streetAddress" => "Calle 27 No 110 e/ M y N. Vedado. Plaza de la Revolución");
  $arr_org['email'] =  array("damiana@infomed.sld.cu", "blazo@infomed.sld.cu", "keylin@infomed.sld.cu");
  $arr_org['telephone'] = "(53 7) 832-2004 / 832-4402";

  $arr_orgs[]=$arr_org;
  
  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Nacional de Información de Ciencias Médicas - Revistas', '@language'=>'es');
  $arr_org['branchCode'] = "CU19";
  $arr_org['memberOf'] = array('Infomed Red Telemática de Salud','Ministerio de Salud Pública');
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Habana","addressCountry"=>"Cuba","streetAddress" => "Calle 27 No 110 e/ M y N. Vedado. Plaza de la Revolución");
  $arr_org['sameAs'] = "http://bvs.sld.cu/revistas/indice.html";
  $arr_org['member'] =  array("@type"=>"Person","name"=> "José Enrique Alfonso Manzanet","jobTitle"=>"Jefe de Redacción","email" => "jenrique@infomed.sld.cu");
  $arr_org['email'] =  array("concuba@infomed.sld.cu", "jenrique@infomed.sld.cu");
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS");
  $arr_org['telephone'] = "(53 7) 832-4519 / 832-4579";
  
  $arr_orgs[]=$arr_org;
  
  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Provincial de Información de Ciencias Médicas de Camaguey', '@language'=>'es');
  $arr_org['branchCode'] = "CU1.3";
  $arr_org['memberOf'] = array('Ministerio de Salud Pública');
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['sameAs'] = "http://www.sld.cu/sitios/cpicm-cmw/";
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Gelsy Rodríguez Lopez","jobTitle"=>"Jefe de Servicios Técnicos","email" => "gelsy@finlay.cmw.sld.cu");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Camaguey","addressCountry"=>"Cuba","streetAddress" => "Carretera Central Oeste");
  $arr_org['email'] =  array("gelsy@finlay.cmw.sld.cu", "cpicm@finlay.cmw.sld.cu");
  $arr_org['telephone'] = "(53 7) 03229-2110";

  $arr_orgs[]=$arr_org;
  
  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Biblioteca Médica Nacional', '@language'=>'es');
  $arr_org['branchCode'] = "CU1.1";
  $arr_org['memberOf'] = array('Centro Nacional de Información de Ciencias Medicas - INFOMED','Ministerio de Salud Pública');
  $arr_org['additionalType'] =  array("Centro Coordenador Nacional BVS","Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['sameAs'] = "http://www.infomed.sld.cu/";
  $arr_org['member'] =  array(
                            array("@type"=>"Person","name"=> "Beatriz Aguirre Rodríguez","jobTitle"=>"Responsable"),
                            array("@type"=>"Person","name"=> "Mirta Prendes Guerrero","jobTitle"=>"Responsable","email" => "mirta@infomed.sld.cu"),
                            array("@type"=>"Person","name"=> "Lisbeth Cruz (SCAD)","jobTitle"=>"Responsable","email" => "lisscruz@infomed.sld.cu"),
                            array("@type"=>"Person","name"=> "Bárbara Lazo Rodríguez","jobTitle"=>"Responsable","email" => "blazo@infomed.sld.cu"),
                        );
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Habana","addressCountry"=>"Cuba","streetAddress" => "Calle 23 No 162 esq N. Vedado. Plaza de la Revolución");
  $arr_org['email'] =  array("prestamo@infomed.sld.cu", "blazo@infomed.sld.cu", "mirta@infomed.sld.cu", "lisscruz@infomed.sld.cu");
  $arr_org['telephone'] = "(53 7) 832-4402";

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Provincial de Información de Ciencias Médicas de Cienfuegos', '@language'=>'es');
  $arr_org['branchCode'] = "CU403.1";
  $arr_org['memberOf'] = array('Facultad de Ciencias Médicas','Universidad Médica de Cienfuegos');
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['sameAs'] = "http://www.cfg.sld.cu/";
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Elinor Dulzaides Iglesias","jobTitle"=>"Responsable");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Cienfuegos","addressCountry"=>"Cuba","streetAddress" => "Calle 51 y Avenida 5 de Septiembre");
  $arr_org['email'] =  array("mikhail@ucm.cfg.sld.cu", "editorial@spicm.cfg.sld.cu");
  $arr_org['telephone'] = "(53 043) 516-602";

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro de Investigación y Desarrollo de Medicamentos', '@language'=>'es');
  $arr_org['branchCode'] = "CU404.1";
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Maria Tereza Dominguez Drake");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Habana","addressCountry"=>"Cuba","streetAddress" => "Calle 26 No.1605 entre Avenida Rancho Boyeros y Calzada de Puentes Grandes");
  $arr_org['email'] =  array("admimefa@infomed.sld.cu", "cidem@infomed.sld.cu");
  $arr_org['telephone'] = "(53 7) 881-1944 / 881-0892 / 881-2453 / 878-8633";

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Internacional de Restauración Neurológica', '@language'=>'es');
  $arr_org['branchCode'] = "CU414.1";
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['sameAs'] = "http://www.ciren.cu/";
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Maria Luisa Rodriguez Cordero");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Habana","addressCountry"=>"Cuba","streetAddress" => "Avenida 25 nº 15805 entre 158 y 160 Playa");
  $arr_org['email'] =  array("cineuro@neuro.ciren.cu");
  $arr_org['telephone'] = "(53 7) 273-6777 / 273-6778 / 273-6356 / 273-6087 / 271-5756 / 271-5687 / 271-5567 / 271-5097 / 271-5044";

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Provincial de Información de Ciencias Médicas de Granma', '@language'=>'es');
  $arr_org['branchCode'] = "CU417.1";
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['sameAs'] = "http://www.cpicm.grm.sld.cu/";
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Heberto Milanés Barrero");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Granma","addressCountry"=>"Cuba","streetAddress" => "Céspedes nº 109 e/ Saco y Figueredo");
  $arr_org['email'] =  array("rguevara@grannet.grm.sld.cu");
  $arr_org['telephone'] = "(53) 424-464/426 -057";

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Provincial de Información de Ciencias Médicas de Santiago de Cuba', '@language'=>'es');
  $arr_org['branchCode'] = "CU418.1";
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Dolores Meléndez Suárez");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Santiago de Cuba","addressCountry"=>"Cuba","streetAddress" => "Calle 5sta nº 51 esq. - Carretera del Caney");
  $arr_org['email'] =  array("directora.cpicm@medired");
  $arr_org['telephone'] = "(53) 642 -4878/642 -751";

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Provincial de Información de Ciencias Médicas de Guantánamo', '@language'=>'es');
  $arr_org['branchCode'] = "CU419.1";
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Mayra López Milián");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Guantánamo","addressCountry"=>"Cuba","streetAddress" => "Calle S Oeste s/n e/ 6 y 9 Norte");
  $arr_org['email'] =  array("cpicm@unimed.gtm.sld.cu", "mayra@infosol.gtm.sld.cu");
  $arr_org['telephone'] = "(53) 2138-6591 / 2138-4092 / 2138-1014 EXT 54";

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Provincial de Información de Ciencias Médicas de Sancti Spiritus', '@language'=>'es');
  $arr_org['branchCode'] = "CU420.1";
  $arr_org['memberOf'] = array('Facultad de Ciencias Médicas');
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Carmen Sánchez");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Sancti Spiritus","addressCountry"=>"Cuba","streetAddress" => "Circunvalante Norte s/n - Olivos 3");
  $arr_org['email'] =  array("director@centromed.ssp.sld.cu");
  $arr_org['telephone'] = "(53 41) 327 -293";

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Biblioteca', '@language'=>'es');
  $arr_org['branchCode'] = "CU421.1";
  $arr_org['memberOf'] =  array('Centro Provincial de Información de Ciencias Médicas de Ciego de Avila');
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['sameAs'] = "http://www.sld.cu/sitios/ciego-avila/";
  $arr_org['member'] =  array(
                            array("@type"=>"Person","name"=> "Yolanda Pérez Jiménez"),
                            array("@type"=>"Person","name"=> "Sara Morgado Ruiz")
                            );
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Ciego de Avila","addressCountry"=>"Cuba","streetAddress" => "Calle Arnaldo Ramírez s/n esq. Chicho Torres");
  $arr_org['email'] =  array("yolanda@cpi.cav.sld.cu", "sara@cpi.cav.sld.cu");

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Provincial de Información de Ciencias Médicas de Holguín', '@language'=>'es');
  $arr_org['branchCode'] = "CU422.1";
  $arr_org['memberOf'] =  array('Facultad de Ciencias Médicas');
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Mirtha Santiesteban");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Holguín","addressCountry"=>"Cuba","streetAddress" => "Ave Lenin No. 4 esquina Aguilera");
  $arr_org['email'] =  array("cpicmho@bariay.hlg.sld.cu");

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Provincial de Información de Ciencias Médicas de Pinar del Río', '@language'=>'es');
  $arr_org['branchCode'] = "CU423.1";
  $arr_org['memberOf'] =  array('Facultad de Ciencias Médicas');
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Heida Hernández");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Pinar del Río","addressCountry"=>"Cuba","streetAddress" => "Carretera Central Km 89");
  $arr_org['email'] =  array("heida@princesa.pri.sld.cu", "cpicm@princesa.pri.sld.cu");

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Provincial de Información de Ciencias Médicas de Matanzas', '@language'=>'es');
  $arr_org['branchCode'] = "CU424.1";
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['sameAs'] = "http://www.cpimtz.sld.cu/";
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Lázaro de León Rosales");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Matanzas","addressCountry"=>"Cuba","streetAddress" => "Navia esquina Isabel Primera - Versalles");
  $arr_org['email'] =  array("ldeleon.mtz@infomed.sld.cu", "cpicmmt.mtz@infomed.sld.cu");
  $arr_org['telephone'] = "(55 3) 4524 -3757";

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Provincial de Información de Ciencias Médicas de Villa Clara', '@language'=>'es');
  $arr_org['branchCode'] = "CU425.1";
  $arr_org['memberOf'] =  array('Instituto Superior de Ciencias Médicas Dr. Serafin Ruiz de Zarate Ruiz');
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Manuel Delgado Perez");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Villa Clara","addressCountry"=>"Cuba","streetAddress" => "Carretera de Acueducto y Circunvalación s/n");
  $arr_org['email'] =  array("pilarfd@infomed.sld.cu", "manueldp@infomed.sld.cu");
  $arr_org['telephone'] = "(53-042) 27 -3765";

  $arr_orgs[]=$arr_org;

  $arr_org = array();
  $arr_org['name'] = array('@value'=>'Centro Provincial de Información de Ciencias Médicas de la Isla de la Juventud', '@language'=>'es');
  $arr_org['branchCode'] = "CU426.1";
  $arr_org['memberOf'] =  array('Centro Municipal de Higiene, Epidemiologia y Microbiologia');
  $arr_org['additionalType'] =  array("Centro Cooperante da BVS", "SCAD", "Rede LILACS");
  $arr_org['member'] =  array("@type"=>"Person","name"=> "Odalys González Santos");
  $arr_org['address'] = array("@type"=>"PostalAddress","addressLocality"=> "Nova Gerona","addressCountry"=>"Cuba","streetAddress" => "Calle A No. 405 entre 39 A y 4 Edificio. Centro de Higiene");
  $arr_org['email'] =  array("cpicmij@infomed.sld.cu");
 
  $arr_orgs[]=$arr_org;

  foreach($arr_orgs as $arr_org){
      
    $sql0 = 'SELECT id FROM lildbi.organization WHERE data->"$.branchCode" = "'.$arr_org['branchCode'].'"';  
    $res = mysqli_query($link, $sql0);

    if (!mysqli_num_rows($res)) {//si no existe la organizacion se inserta
      $arr_org['@type'] = "Organization";
      if(isset($arr_org['memberOf'])){
        foreach($arr_org['memberOf'] as $k=>$name){
          $name = array('@value'=>$name, '@language'=>'es');  
          $arr_org['memberOf'][$k] = array('name'=> $name, '@type'=>'Organization');
        }
      }
      if(is_assoc_array($arr_org['member'])){
        $arr_org['member']['name'] = array('@value'=>$arr_org['member']['name'], '@language'=>'es');
      }
      else{
        foreach($arr_org['member'] as $k=>$member){
          $name = $member['name'];  
          $arr_org['member'][$k]['name'] = array('@value'=>$name, '@language'=>'es');
        }
      }
      //convertir array  $arr_org en str válido para pasar por funcion JSON_OBJECT()
      $json_str = array_to_objStr($arr_org, $link, 1);

      if(!json_argument_error($json_str,$link)){  //chequear cadena $json_str antes de insertar
        $sql= "INSERT INTO Organization (data, created) VALUES (JSON_OBJECT($json_str), NOW())"; 
        $resulti = mysqli_query($link, $sql);
        if (!$resulti) {
          printf("SQL is %s!<BR>", $sql);
          echo 'MySQL Error: ' . mysqli_error($link);
        }
      }
    }
  } 
  echo "Insertadas las organizaciones";

?>

