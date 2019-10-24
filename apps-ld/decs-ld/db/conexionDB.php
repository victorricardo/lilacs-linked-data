<?php
/**
 * @file
 * Definición de los datos de la conexión a la BD.
 */

//conexion a la bd
$dbnameD = 'decs';
$link_decs = mysqli_connect('localhost', 'root', 'root', $dbnameD);
if (!$link_decs) {
    die('Could not connect DeCS: ' . mysqli_connect_errno());
} 
/*if (!mysqli_select_db($link_decs, $dbname)) {
    echo "Could not select database $dbname";
    exit;
} */
?>
