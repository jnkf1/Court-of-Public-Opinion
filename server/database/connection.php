<?php

if (isset($_SERVER["HTTP_ORIGIN"])) {
    header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
}

$db_host = "localhost";
$db_user = "root";
$db_pass = null;
$db_name = "debate";

$mysql = new mysqli($db_host, $db_user, $db_pass, $db_name);

?>