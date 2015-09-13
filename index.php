<?php
ini_set('display_errors', TRUE);
require_once 'pdo.class.php';

$db=new db;
$db->where('CONFIGNAME','TITLE')->update('CONFIG',array('CONFIGVALUE'=>'test'));
$db->get('CONFIG');
$db->debug();