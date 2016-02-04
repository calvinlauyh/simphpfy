<?php

/* 
 * Created by Hei
 */

/*
 * Exception descripying situation when a controller class file is not found
 */
class MissingTemplateException extends SimPHPfyException{
    protected $_template = '';
    
    public function __construct($message, $code = 404) {
        $this->_template = "Template `%s` was not found in `%s`";
        parent::__construct($message, $code);
    }
}
