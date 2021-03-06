<?php

require_once 'vendor/Autoloader.php';

use myc\Core\db as db;
use myc\Exception\MYCDBException as MYCDBException;
use myc\Exception\MYCDBPDOException as MYCDBPDOException;
use myc\Exception\MYCDBInvalidArgumentException as MYCDBInvalidArgumentException;

try {
    $db = new db();

    $data = $db->get_where('cashbox', array('ISACTIVE' => 'Evet'));

    $db->debug();
} catch (MYCDBInvalidArgumentException $e) {
    echo $e->errorMessage();
} catch (MYCDBPDOException $e) {
    echo $e->errorMessage();
} catch (MYCDBException $exc) {
    echo $exc->errorMessage();
} catch (Exception $e) {
    echo $e->getTraceAsString();
}

