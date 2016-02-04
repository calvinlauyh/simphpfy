<?php

/* 
 * Created by Hei
 */

class MissingClassFileException extends SimPHPfyException{
    protected $_template = "Class file `%s` was not found.";
    
    public function __construct($message, $code = 404) {
        parent::__construct($message, $code);
    }
}
