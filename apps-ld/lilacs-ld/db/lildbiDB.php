<?php
require("conexionDB.php");

$sql = "SHOW TABLES FROM $dbname";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not list tables\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}
$tables =array();
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}
echo "Tablas:";
print_r($tables);
mysqli_free_result($result);

$sql = "CREATE TABLE IF NOT EXISTS document (
  `id` int(11) NOT NULL auto_increment,
  `data` json DEFAULT NULL,
  `originalRecord` json DEFAULT NULL,
  `context` json DEFAULT NULL,
  `created` DATETIME,
  `transfered` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not create table document\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}
$sql = "CREATE TABLE IF NOT EXISTS organization (
  `id` int(11) NOT NULL auto_increment,
  `data` json DEFAULT NULL,
  `context` json DEFAULT NULL,
  `created` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not create table organization\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}

$sql = "CREATE TABLE IF NOT EXISTS person (
  `id` int(11) NOT NULL auto_increment,
  `data` json DEFAULT NULL,
  `context` json DEFAULT NULL,
  `created` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not create table person\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}
$sql = "CREATE TABLE IF NOT EXISTS tmp_person (
  `id` int(11) NOT NULL auto_increment,
  `data` json DEFAULT NULL,
  `is_valid` int(1) DEFAULT 0,
  `sameAs` int(11) DEFAULT 0,
  `id_person` int(11) DEFAULT NULL,
  `in_document` json DEFAULT NULL,
  `created` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not create table tmp_person\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}
$sql = "CREATE TABLE IF NOT EXISTS tmp_organization (
  `id` int(11) NOT NULL auto_increment,
  `data` json DEFAULT NULL,
  `is_valid` int(1) DEFAULT 0,
  `sameAs` int(11) DEFAULT 0,
  `id_organization` int(11) DEFAULT NULL,
  `in_document` json DEFAULT NULL,
  `created` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not create table tmp_organization\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}
$sql = "CREATE TABLE IF NOT EXISTS tmp_document (
  `id` int(11) NOT NULL auto_increment,
  `data` json DEFAULT NULL,
  `is_valid` int(1) DEFAULT 0,
  `sameAs` int(11) DEFAULT 0,
  `id_document` int(11) DEFAULT NULL,
  `in_document` json DEFAULT NULL,
  `created` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not create table tmp_document\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}
  $sql = "CREATE TABLE IF NOT EXISTS event (
  `id` int(11) NOT NULL auto_increment,
  `data` json DEFAULT NULL,
  `context` json DEFAULT NULL,
  `created` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not create table event\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}
$sql = "CREATE TABLE IF NOT EXISTS tmp_event (
  `id` int(11) NOT NULL auto_increment,
  `data` json DEFAULT NULL,
  `is_valid` int(1) DEFAULT 0,
  `sameAs` int(11) DEFAULT 0,
  `id_event` int(11) DEFAULT NULL,
  `in_document` json DEFAULT NULL,
  `created` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not create table tmp_person\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}
  $sql = "CREATE TABLE IF NOT EXISTS project (
  `id` int(11) NOT NULL auto_increment,
  `data` json DEFAULT NULL,
  `context` json DEFAULT NULL,
  `created` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not create table event\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}
$sql = "CREATE TABLE IF NOT EXISTS tmp_project (
  `id` int(11) NOT NULL auto_increment,
  `data` json DEFAULT NULL,
  `is_valid` int(1) DEFAULT 0,
  `sameAs` int(11) DEFAULT 0,
  `id_project` int(11) DEFAULT NULL,
  `in_document` json DEFAULT NULL,
  `created` DATETIME,  
  `modified` DATETIME,  
   PRIMARY KEY  (`id`)
)";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not create table tmp_person\n";
    echo 'MySQL Error: ' . mysqli_error($link);
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
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "DB Error, could not create table user\n";
    echo 'MySQL Error: ' . mysqli_error($link);
    exit;
}

//mysql_close($link);
?>
