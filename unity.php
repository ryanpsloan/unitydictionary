<?php
include_once("/var/www/html/Query.php");
header('Content-Type: text/html; charset=utf-8');
session_start();

//single object creation handles all aspects of the query internally
try{
    $queryObj = new Query($field = $_GET['field'],$radio = $_GET['radio']);
    //$queryObj->printLog();
}catch(Exception $e){
    echo $e->getMessage();
}

?>