<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require_once(PATH_COMMON_LD."getContext.inc");

$context = file_get_contents(PATH_LILACS_LD."context.jsonld") ;
$lilacs_ctx = json_decode($context, 1); //context como arreglo asociativo
$allowed_prop = getAllowedProperties($lilacs_ctx);//arreglo de nombres de las propiedades del modelo de datos LILACS-ld, PENDIENTE: diferenciar por tipo entidad?

/* Une dos arreglos recursivamente, si sus elementos tienen la misma llave (de tipo string) y los valores son iguales deja uno solo
*
* Por ejemplo: Si array_merge_recursive devuelve 
* array([@type] => array([0] => Person, [1] => Person), [name] => array([0] => Mirta Prendes Guerrero, [1] => Mirta Prendes Guerrero), 
*                        [jobTitle] => array([0] => Responsable, [1] => Administrador, [2] => Responsable))
* Entonces array_merge_distinct devuelve 
* array([@type] => Person, [name] => Mirta Prendes Guerrero, [jobTitle] => array([0] => Responsable, [1] => Administrador))
* 
* @param $arr1
*   array 1er arreglo a unir
* @param $arr2
*   array 2do arreglo a unir
*
* @return 
*   arreglo resultante de la union, sin repetición de pares llave-valor iguales
*/
function array_merge_distinct($arr1,$arr2){
    $arr = array_merge_recursive($arr1,$arr2);
    foreach($arr as $k=>$v){
      if(is_array($v)){
        if(isset($arr1[$k]) AND isset($arr2[$k])){  
          $keys_type = elements_type($v);
          if (count($keys_type)==2){
            if(is_assoc_array($arr1[$k])){
              $v = $arr2[$k];
              $k1 = count($arr2[$k]);
              $v[$k1] = $arr1[$k];
            }
            elseif(is_assoc_array($arr2[$k])){
              $v = $arr1[$k];
              $k1 = count($arr1[$k]);
              $v[$k1] = $arr2[$k];
            }
          }
          elseif($keys_type[0]=='str'){
             $v = array($arr1[$k],$arr2[$k]); 
          }
        }
        $arr_v =array();
        foreach($v as $v1){
          if(is_array($v1)){
            $arr_v = array_values($v);
            $arr_u = $arr_v;
            $f = count($arr_v);
            for($i=0;$i<$f;$i++)
              for($j=$i+1;$j<$f;$j++) {
                if(!array_diff_assoc($arr_v[$i], $arr_v[$j]))
                 unset($arr_u[$j]); 
              }
            break; 
          }
        }
        if(!$arr_v){
          $arr_u = array_unique($v);
        }
        if(count($arr_u)>1)
          $arr[$k] = $arr_u;
        else
          $arr[$k] = $arr_u[0];
      }
    }
    return $arr;
}

/* Devuelve un arreglo con los tipos de los elementos del arreglo 
*
* @param $arr
*   array arreglo cuyos elementos se desea conocer el tipo
*
* @return 
*   arreglo con los valores 'int' si hay elementos enteros y 'str' si hay cadenas
*/
function elements_type($arr){
  $keys = array_keys($arr);
  $int = $str = 0;
  foreach($keys as $key){
    if(is_int($key)){
      $int = 1;  
    }  
    else{
      $str = 1;  
    }
  }
  $ret = array();
  if($int)
    $ret[]='int';
  if($str)
    $ret[]='str';
  
  return $ret;  
}


?>
