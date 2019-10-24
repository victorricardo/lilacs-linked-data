<?php
/**
 * @file
 * Migrar Descriptores y Calificadores propios del DeCS 
 * - Recorre arboles de categorías propias de DeCS y árbol de calificadores (por si hay alguno propio de decs)
 * - en cada nodo invoca a ins_decs_data.php para convertir datos al modelos rdf e innsetar en la BD  
 */
require_once("ins_decs_data.php");

set_time_limit(0); 
$ini=time(); 

$date=@date('Y-m-d');  
$f_log = PATH_LOGS."log_ins_decs_data$date.txt";
file_put_contents($f_log, "Iniciando log: $date\n");

//recorrer arbol de calificadores(Q) y si existe alguno propio de decs (no tiene unique_identifier_nlm) se agrega
//$tree_id = "Q";
//walk_tree($tree_id, 'Qualifier');
//exit;

//arreglo con la raiz de los arboles propios de decs
foreach($cat_decs as $root_treeId){
  echo "Categoria DeCS: $root_treeId<BR>";  
  walk_tree($root_treeId, 'Descriptor');
} 
  
//para probar
//$tree_id = "VS";
//walk_tree($tree_id, 'Descriptor');
 

$total=(time()-$ini)/60;
echo "Tiempo total: $total minutos<BR>";
file_put_contents($f_log, "Tiempo de ejecucion: $total min \n", FILE_APPEND | LOCK_EX);

/* Funcion recursiva para recorrer el arbol de categorias del DeCS, en cada nodo llama al servicio del decs, e inserta entidad, 
 * recorre sus hijos y llama a walk_tree para c/u  
 *
 * @param $tree_id
 *   string: numero de arbol del descriptor o calificador
 * @param $type
 *   string: Descriptor o Qualifier
 *
 */
function walk_tree($tree_id, $type){
  global $f_log, $link_decs;
  static $lastId=0, $lastId_D=0, $lastId_Q=0;
  
  //chequear si ya se han insertado entidades del decs para inicializar lastId con los ultimos valores
  if($lastId==0){
    $lastId=get_lastId('Term');  
    $lastId_D=get_lastId('Descriptor');  
    $lastId_Q=get_lastId('Qualifier');
    echo "$lastId, $lastId_D, $lastId_Q<BR>";  
  }
  
  $lang = 'en'; //empezar con espanol o ingles? Ingles pq no se han agregado las traducciones a las entidades de mesh

  //busca item por treeNumber con el servicio del decs
  $item = get_item_by_TreeN($tree_id, $lang);

  if($item){
    if(!treeNumber_exist($tree_id)){   //se prgunta aqui para no insertar, hay q llamar al servicio arriba para recorrer hijos
      //si no existe en la tabla de TreeNumbers, insertar descriptores o calificadores q no esten en mesh
      if($type=='Descriptor'){ 
        $lastId_D += 1;
        $lastId += 1;
        echo "$lastId_D: $tree_id<BR>";  
        file_put_contents($f_log, "Procesando $tree_id (id = $lastId_D)\n", FILE_APPEND | LOCK_EX);
        list($lastId_D, $lastId) = ins_decs_DoQ($item, $lastId_D, $lastId, $type, $lang, $f_log);
      }
      elseif($type=='Qualifier' AND !$item['record_list']['record']['unique_identifier_nlm']){ //calificador propio de decs
        $lastId_Q += 1;
        $lastId += 1;
        echo "$lastId_Q, $tree_id <BR>";  
        file_put_contents($f_log, "Procesando $tree_id (id = $lastId_Q)\n", FILE_APPEND | LOCK_EX);
        //resta 1 a los lastId si no se insertaron las entidades
        list($lastId_Q, $lastId) = ins_decs_DoQ($item, $lastId_Q, $lastId, $type, $lang, $f_log);
      }  
    }
    
    //recorre los hijos y llama a walk_tree recursivamente
    if(isset($item['tree']['descendants']['term_list']) AND count($item['tree']['descendants']['term_list'])){  
      $descendants = get_relatives_treeId($item['tree']['descendants']['term_list']);
      foreach($descendants as $descendant){
        walk_tree($descendant, $type);  
      }
    }
  }
}  

/* 
* al recorrer el arbol pueden quedar entidades sin insertar por fallos en la conexion, revisar log
*/
?>
