<?php

/* 
 * Created by Hei
 */

class Router{    
    /*
     * An array that holds all the route object defined
     * 
     * @var Array
     */
    private static $_routes = array();
    
    /*
     * Listen to a custom defined routing rule
     * 
     * @param String|Array $method the request method|list of request method 
     *  the rule is applied to
     * @param String $rule
     * @param Array|callable $callback
     * 
     * @return void
     */
    public static function listen($method, $rule, $callback='') {
        // Create a new Route object
        $routes = self::getRoutes();
        $routes[] = new Route($method, $rule, $callback);
        self::setRoutes($routes);
    }
    
    /*
     * Try to match the current URL with the defined rules
     * 
     * @return void
     */
    public static function dispatch(){
        /*
         * include a list of system pre-defined routing rules
         * 
         * include the last moment when dispatch to ensure that those rules
         * have the lowest priority in the list
         */
        require ROUTER . 'routes.php';
        
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        if ($requestMethod == 'POST') {
            if (isset($_POST['_method'])) {
                $_method = $_POST['_method'];
                if ($_method == 'PUT' || $_method == 'DELETE') {
                    $requestMethod = $_method;
                }
            }
        }
        $requestUri = trim($_SERVER['REQUEST_URI'], '/');
        $questionMarkPos = strpos($requestUri, '?');
        if ($questionMarkPos) {
            $requestUri = substr($requestUri, 0, $questionMarkPos-1);
        }
        $uriSegments = explode('/', $requestUri);
        $uriSegmentCount = count($uriSegments);
        
        $matched = false;
        foreach (self::getRoutes() as $key => $route) {
            // perform quick match
            
            // check if the method matches
            $methodMatched = false;
            if (!in_array($requestMethod, $route->getMethod())) {
                continue;
            }  
            /* 
             * check if the segment count of the Request URI and the rule are 
             * the same or if the rule has wildcard trailing `**`
             */
            if ($route->getSegmentCount() == $uriSegmentCount || $route->hasWildcardTrailing()) {
                /*
                 * Match the request URI with the potential route rule. First 
                 * check if the isRegEx flag is set and compare using 
                 * corresponding method
                 */
                // late prepare the route for matching
                $route->latePrepare();
                $response = array();
                $params = array();
                $controller = $route->getController();
                $action = $route->getAction();
                if ($route->isRegEx()) {
                    // perform regulare expression matching
                    if (preg_match('@^'.$route->getRule().'$@', $requestUri, $matches)) {
                        // get the variables from the url
                        foreach ($route->getBindedVar() as $key => $value) {
                            // Handle magic value
                            if ($value == ':Controller') {
                                $controller = $matches[$key+1];
                            } elseif ($value == ':Action') {
                                $action = $matches[$key+1];
                            } elseif ($value == '*' | $value == '**') {
                                // nothing to do
                            } else {
                                $params[$value] = $matches[$key+1];
                            }
                        }
                    } else {
                        continue;
                    }
                } else if($route->getRule() == $requestUri) {
                    // Pure string has no binded variables
                } else {
                    continue;
                }
                
                // prepare the request and fire
                $request = new Request($requestMethod, $params);
                
                if ($route->getController() == ':Callable') {
                    call_user_func($route->getCallback(), $request);
                } elseif ($route->getController() == ':Closure') {
                    call_user_func($route->getCallback(), $request);
                } elseif ($route->getController() == ':Redirect') {
                    header("Location: {$route->getAction()}");
                } else {
                    self::_loadController($controller);
                    $controllerObj = new $controller($request, $controller, $action);
                    if (!($controllerObj instanceof Controller)) {
                        throw new InvalidControllerException(array(
                            $controller, 
                            'Controller must extend from class `Controller`'
                        ));
                    }
                    if (!method_exists($controllerObj, $action)) {
                        throw new InvalidControllerException(array(
                            $controller, 
                            "Action `{$action}` was not declared"
                        ));
                    }
                    call_user_func_array(array($controllerObj, $action), $params);
                }
                $matched = true;
                break;
            } else {
                continue;
            }
        }
        if (!$matched) {
            throw new FileNotFoundException('File not found' , 404);
        }
    }
    
    /*
     * Check and load the controller file
     * 
     * @param String $controller The name of the controller
     * 
     * @return Boolean If the controller has loaded into the system
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
    
    
    public static function getRoutes() {
        return self::$_routes;
    }

    private static function setRoutes($routes) {
        self::$_routes = $routes;
    }


}
