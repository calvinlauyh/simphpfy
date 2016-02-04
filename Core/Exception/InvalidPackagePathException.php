<?php

/* 
 * Created by Hei
 */

class InvalidPackagePathException extends SimPHPfyException{
    protected $_template = "Package `%s` could not be found.";
    
    public function __construct($message, $code = 404) {
        parent::__construct($message, $code);
    }
}
