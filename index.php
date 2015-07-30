<?php
ini_set('display_errors', TRUE);
require_once 'pdo.class.php';

$db=new db;
$db->get("sqlite_master");
$db->debug();
$data=$db->showtablefields('sqlite_master');