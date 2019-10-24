<?php
/**
 * @file
 * Creación de las tablas de la aplicación
 */
include_once("conexionDB.php");

$sql = "SHOW TABLES FROM $dbnameD";
$result = mysqli_query($link_decs, $sql);
if (!$result) {
    echo "DB Error, could not list tables\n";
    echo 'MySQL Error: ' . mysqli_error($link_decs);
    exit;
}
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}
if(isset($tables)){
  echo "Tablas:";
  print_r($tables);
  mysqli_free_result($result);
}
$sql = "CREATE TABLE IF NOT EXISTS Descriptor (
  `id` int(11) NOT NULL auto_increment,
  `identifier` VARCHAR(10),
  `type` VARCHAR(50),
  `data` json DEFAULT NULL,
  `created` DATETIME,
  `meshUpdated` DATETIME,  
  `decsUpdated` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link_decs, $sql);
if (!$result) {
    echo "DB Error, could not create table Descriptor\n";
    echo 'MySQL Error: ' . mysqli_error($link_decs);
    exit;
}
$sql = "CREATE TABLE IF NOT EXISTS Qualifier (
  `id` int(11) NOT NULL auto_increment,
  `identifier` VARCHAR(10),
  `data` json DEFAULT NULL,
  `created` DATETIME,
  `meshUpdated` DATETIME,  
  `decsUpdated` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link_decs, $sql);
if (!$result) {
    echo "DB Error, could not create table Qualifier\n";
    echo 'MySQL Error: ' . mysqli_error($link_decs);
    exit;
}

$sql = "CREATE TABLE IF NOT EXISTS SupplementaryConceptRecord (
  `id` int(11) NOT NULL auto_increment,
  `identifier` VARCHAR(10),
  `type` VARCHAR(50),
  `data` json DEFAULT NULL,
  `created` DATETIME,
  `meshUpdated` DATETIME,  
  `decsUpdated` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";

$result = mysqli_query($link_decs, $sql);
if (!$result) {
    echo "DB Error, could not create table SupplementaryConceptRecord\n";
    echo 'MySQL Error: ' . mysqli_error($link_decs);
    exit;
}
$sql = "CREATE TABLE IF NOT EXISTS DescriptorQualifierPair (
  `id` int(11) NOT NULL auto_increment,
  `identifier` varchar(20),
  `type` VARCHAR(50),
  `data` json DEFAULT NULL,
  `created` DATETIME,
  `meshUpdated` DATETIME,  
  `decsUpdated` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link_decs, $sql);
if (!$result) {
    echo "DB Error, could not create table DescriptorQualifierPair\n";
    echo 'MySQL Error: ' . mysqli_error($link_decs);
    exit;
}
$sql = "CREATE TABLE IF NOT EXISTS TreeNumber (
  `id` int(11) NOT NULL auto_increment,
  `identifier` VARCHAR(80) NOT NULL,
  `data` json DEFAULT NULL,
  `created` DATETIME,
  `meshUpdated` DATETIME,  
  `decsUpdated` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link_decs, $sql);
if (!$result) {
    echo "DB Error, could not create table TreeNumber\n";
    echo 'MySQL Error: ' . mysqli_error($link_decs);
    exit;
}
$sql = "CREATE TABLE IF NOT EXISTS Concept (
  `id` int(11) NOT NULL auto_increment,
  `identifier` VARCHAR(10),
  `data` json DEFAULT NULL,
  `created` DATETIME,
  `meshUpdated` DATETIME,  
  `decsUpdated` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link_decs, $sql);
if (!$result) {
    echo "DB Error, could not create table Concept\n";
    echo 'MySQL Error: ' . mysqli_error($link_decs);
    exit;
}
  $sql = "CREATE TABLE IF NOT EXISTS Term (
  `id` int(11) NOT NULL auto_increment,
  `identifier` VARCHAR(10),
  `data` json DEFAULT NULL,
  `created` DATETIME,
  `meshUpdated` DATETIME,  
  `decsUpdated` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link_decs, $sql);
if (!$result) {
    echo "DB Error, could not create table Term\n";
    echo 'MySQL Error: ' . mysqli_error($link_decs);
    exit;
}
$sql = "CREATE TABLE IF NOT EXISTS user (
  `id` int(11) NOT NULL auto_increment,
  `email` varchar(150) NOT NULL,
  `api_key` varchar(50) NOT NULL,
  `permisos` json DEFAULT NULL,
  `created` DATETIME,
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link_decs, $sql);
if (!$result) {
    echo "DB Error, could not create table user\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}
?>
