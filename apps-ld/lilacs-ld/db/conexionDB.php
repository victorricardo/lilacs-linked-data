<?php
  //conexion a la bd
$dbname = 'lildbi';
$link = mysqli_connect('localhost', 'root', 'root', $dbname);
if (!$link) {
    die('Could not connect: ' . mysqli_error($link));
}
  
?>
