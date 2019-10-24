<?php
/**
 * @file
 * Convierte fichero MESH.NT a ficheros JSON 
 * 
 * Divide mesh.nt en ficheros .nt mas pequeños, para que rdfconvert pueda convertirlos de .nt a json) 
 */
require("common.inc");

set_time_limit(0); 
$ini=time(); 

//fichero mesh rdf
$mesh_rdf = PATH_RDFCONVERT."mesh.nt";
$handle = fopen( $mesh_rdf, "r");

//directorio donde se ejecuta rdfconvert
chdir(PATH_RDFCONVERT);
$file_path = PATH_RDFCONVERT."entidades";

//pos0: posicion del identificador en la uri del mesh
$pos0 = strlen("<".URL_MESH);
$prev_identifier = "";

$max_lines = 100000;  //maximo de lineas en un fichero (para q funcione rdfconvert)
$i =0;    //ctdad de lineas q se van copiando a un fichero
$j = 0;   //ctdad de ficheros q se van creando

if ($handle) {
    while (!feof($handle) AND $i<=$max_lines ) {
      $buffer = fgets($handle);
      $pos = strpos($buffer,URL_MESH);
      if($pos !== false){
        //si la linea contiene la url del mesh
        $pos1 = strpos($buffer,">");
        //se extrae el identificador de la entidad
        $identifier = substr($buffer,$pos0, $pos1-$pos0);
        $i += 1; //contador de lineas
        if($prev_identifier==""){//primer identificador
          //se escribe la linea en un fichero nt (n-triples)
          $file = $file_path.$j.".nt";
          file_put_contents($file, $buffer);
          $prev_identifier = $identifier; 
           
        }
        else{  
          if($i<$max_lines){  
            //se agrega la linea en el mismo fichero 
            file_put_contents($file, $buffer, FILE_APPEND | LOCK_EX);
            if($identifier!=$prev_identifier ){//nuevo identificador
              //echo "identifier:$identifier<BR>";  
              $prev_identifier = $identifier; 
            }
          }
          elseif($i == $max_lines ){
            if($identifier!=$prev_identifier){//en la ultima linea hay un nuevo identificador, se guarda en nuevo fichero
              $i = 0;    
              $file1 = $file_path.$j.".json";
              echo "$file a $file1<BR>";

              //se convierte el fichero nt a json
              $cmd ="rdfconvert -i N-Triples $file -o JSON-LD $file1"; //linea decomando para ejecutar rdfconvert
              exec($cmd);
          
              //nuevo fichero nt de entidades
              $j += 1;
              $file = $file_path.$j.".nt";
              file_put_contents($file, $buffer);
            }  
            else{
              $i=$max_lines-1;//para no crear nuevo fichero si faltan lineas de la misma entidad
              file_put_contents($file, $buffer, FILE_APPEND | LOCK_EX);
            }
          }
          else{//i>max_ent, crear nuevo fichero
            $i = 0;    
            $file1 = $file_path.$j.".json";
            echo "$file a $file1<BR>";

            //se convierte el fichero nt a json
            //linea decomando para ejecutar rdfconvert
            $cmd ="rdfconvert -i N-Triples $file -o JSON-LD $file1";
            exec($cmd);
            //nuevo fichero nt de entidades
            $j += 1;
            $file = $file_path.$j.".nt";
            file_put_contents($file, $buffer);
          }  
        }
        
      } //la linea contiene la url del mesh
    }
    //convertir el ultimo fichero
    $file1 = $file_path.$j.".json";
    echo "$file a $file1<BR>";

    //se convierte el fichero nt a json
    //linea decomando para ejecutar rdfconvert
    $cmd ="rdfconvert -i N-Triples $file -o JSON-LD $file1";
    exec($cmd);

    fclose($handle);
}
else{
  echo "No se pudo abrir mesh.nt";  
}

$total=(time()-$ini)/60;
echo "Tiempo de ejecucion: $total minutos<BR>";


?>
