<?php

/* 
 * Created by Hei
 */

class Request {
    /*
     * The request method or the current request
     * 
     * @var String
     */
    public $method;
    
    /*
     * A list of request parameters. The $params is a mix of the $_REQUEST and 
     * the parameters obtained from the Routing. The parameters obtained from
     * the Routing alwasys overwiret those in $_REQUEST. Those overwritten 
     * parameters can still be accessed from _GET or _POST but it is highly 
     * unrecommended to create overalpping paramters name
     * 
     * @var Array
     */
    public $params;
    
    /*
     * Copy of $_COOKIE
     * 
     * @var Array
     */
    public $cookie;
    
    /*
     * Copy of $_REQUEST
     * 
     * @var Array
     */
    public $_REQUEST;
    
    /*
     * Copy of $_POST
     * 
     * @var Array
     */
    public $_POST;
    
    /*
     * Copy of $_GET
     * 
     * @var Array
     */
    public $_GET;
    
    /*
     * Constructor
     * 
     * accept the $request from Router and store into instance variables
     * 
     * @param Array $request
     * 
     */
    function __construct($method, $params) {
        $this->_setMethod($method);
        $request = $_REQUEST;
        $this->_setREQUEST($_REQUEST);
        $this->_setCookie($_COOKIE);
        $this->_setPOST($_POST);
        $this->_setGET($_GET);
        foreach ($params as $key => $value) {
            $request[$key] = $value;
        }
        $this->_setParams($request);
    }
    
    /*
     * List of method to check the request method
     * 
     * @return Boolean
     */
    public function isGet(){
        return ($this->method == 'GET');
    }
    public function isPost(){
        return ($this->method == 'POST');
    }
    public function isPut(){
        return ($this->method == 'PUT');
    }
    public function isDelete(){
        return ($this->method == 'DELETE');
    }
    
    // Auto-generated setter
    private function _setMethod($method) {
        $this->method = $method;
    }

    private function _setParams($params) {
        $this->params = $params;
    }
    
    private function _setCookie($cookie) {
        $this->cookie = $cookie;
    }
    
    private function _setREQUEST($request) {
        $this->_REQUEST = $_REQUEST;
    }

    private function _setPOST($post) {
        $this->_POST = $_POST;
    }

    private function _setGET($get) {
        $this->_GET = $_GET;
    }


}
