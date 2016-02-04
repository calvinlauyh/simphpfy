<?php

/* 
 * Created by Hei
 */

/* 
 * The parent class for all the HTTP related exceptions. Design to handle
 * error when doing routing but may extend to other possible usages
 * 
 * @param String $message the error message describing the reasons
 *  for throwing the Exception
 * @param Integer $code the HTTP error code corresponding to the 
 *  Exception
 */

class HttpException extends SimPHPfyBaseException {
    /*
     * Constructor
     */
    public function __construct($message, $code=400) {
        parent::__construct($message, $code);
    }
}
