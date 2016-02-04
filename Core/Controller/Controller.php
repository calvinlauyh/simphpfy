<?php

/* 
 * Created by Hei
 */

class Controller {
    /*
     * The request object from the Router
     * 
     * @var Array
     */
    public $request;
    
    /*
     * Make alias to the Request object instance variables and methods available
     * in the Controller. These act as short-hand ways of getting Request data
     * 
     * @var Array
     */
    public $params;
    public $_REQUEST;
    public $_POST;
    public $_GET;
    
    /*
     * Reference to a instantiated View object
     * 
     * @var View
     */
    public $View;
    
    /*
     * Reference to a instantiated Model object
     * 
     * @var Model
     */
    public $Model;
    
    /*
     * The controller name
     * 
     * @var String
     */
    private $controller;
    
    /*
     * The action name
     * 
     * @var String
     */
    private $action;
    
    /*
     * Constructor of the controller base. The constructor accept the response
     * from the Router and extract the information inside
     * 
     * @var Array $resquest The request array from the Router
     * @var String $controller the name of the controller to be called
     * @var String $action the name of the action to be called
     */
    function __construct($request, $controller, $action) {
        $this->_setRequest($request);
        $this->_setController($controller);
        $this->_setAction($action);
        $this->_setParams($request->params);
        $this->_set_REQUEST($request->_REQUEST);
        $this->_set_POST($request->_POST);
        $this->_set_GET($request->_GET);
        $this->Model = new Model(SimPHPfy::getDatasource(), $this->getController());
        $this->View = new View($this);
        register_shutdown_function('_shutdown', $this);
    }
    
    /*
     * Check and load the Model file
     * 
     * @param String $model The name of the Model
     * 
     * @return Model if 
     */
    private static function _loadController($controller){
        $path = CONTROLLER . $controller . '.php';
        if (!file_exists($path)) {
            echo $path;
            throw new MissingControllerException($controller);
        }
        require $path;
        if (!class_exists($controller)) {
            throw new MissingControllerException($controller);
        }
        return true;
    }
    
    private function _setRequest($request) {
        $this->request = $request;
    }

    private function _setParams($params) {
        $this->params = $params;
    }
    
    public function _set_REQUEST($REQUEST) {
        $this->_REQUEST = $REQUEST;
    }

    private function _set_POST($post) {
        $this->_POST = $post;
    }

    private function _set_GET($get) {
        $this->_GET = $get;
    }

    private function _setController($controller) {
        $this->controller = $controller;
    }

    private function _setAction($action) {
        $this->action = $action;
    }

    public function getController() {
        return $this->controller;
    }

    public function getAction() {
        return $this->action;
    }
}