<?php

/* 
 * Created by Hei
 */

/*
 * Exception descripying situation when a controller class file is not found
 */
class MissingControllerException extends SimPHPfyException{
    protected $_template = '';
    
    public function __construct($message, $code = 404) {
        $this->_template = "Controller `%s` was not found in `" . CONTROLLER . '`.';
        parent::__construct($message, $code);
    }
}
