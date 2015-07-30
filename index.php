<?php
ini_set('display_errors', TRUE);
require_once 'pdo.class.php';

$db=new db;
$db->exec("show create tables");
$db->debug();