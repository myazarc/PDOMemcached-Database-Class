<?php

namespace myc\Exception;

use myc\Exception\MYCDBException as MYCDBException;

class MYCDBInvalidArgumentException extends MYCDBException {

    public function errorMessage() {
        $errorMsg = 'Error on line ' . $this->getLine() . ' in ' . $this->getFile()
                . ': <br><b>' . $this->getMessage() . '</b>';
        return $errorMsg;
    }

}
