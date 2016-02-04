<?php

/* 
 * Created by Hei
 */

/*
 * The Route object. A Route object maintains a particular rule defined for 
 * routing. It contains all the information needed when a mathcing a routing
 * rule
 */
class Route{
    /*
     * List of HTTP request method
     * 
     * @var Array
     */
    private $_METHOD = array(
        'GET',
        'POST', 
        'PUT', 
        'DELETE'
    );
    
    /* 
     * List of predefined regular expression for quick access
     * 
     * @var Array
     */
    private $_REGEX = array(
        /* 
         * Regular expression for ID
         */
        'ID' => '[0-9]+', 
        /* 
         * Regular expression for alphabet
         */
        'Alphabet' => '[A-Za-z]+', 
        /* 
         * Regular expression for alphanumeric
         */
        'Alphanumeric' => '[0-9A-Za-z]+', 
        /* 
         * Regular expression for controller
         */
        'Controller' => '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*', 
        /* 
         * Regular expression for action
         */
        'Action' => '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*', 
        /* 
         * Regular expression for RESTful action
         */
        'RESTAction' => 'index|new|create|show|edit|update|destroy', 
        /* 
         * Regular expression for year
         */
        'Year' => '[12][0-9]{3}', 
        /* 
         * Regular expression for month
         */
        'Month' => '0[1-9]|1[012]', 
        /* 
         * Regular expression for day
         */
        'DAY' => '0[1-9]|[12][0-9]|3[01]', 
        /*
         * Regular expression for wildcard character
         */
        '*' => '.+', 
        /*
         * Regular expression for matching remaining parts
         */
        '**' => '.+'
    );
    
    /*
     * Keep a list of arguments passed for debug purpose
     * 
     * @var Array
     */
    private $_arguments;
    
    /*
     * The request method
     * 
     * @var Array
     */
    private $_method;
    
    /*
     * List of segments extracted from the rule
     * 
     * @var Array
     */
    private $_segments;
    
    /*
     * Whether the rule is a RegEx
     * 
     * @var Boolean
     */
    private $_isRegEx;
    
    /*
     * Tell if the rule has a wildcard trailing ** at the end. This can 
     * faciliate the matching process when dispatch
     * 
     * @var Boolean
     */
    private $_hasWildcardTrailing;
    
    /*
     * The rule for matching when dispatch, if isRegEx is true, the rule is a 
     * RegEx prepared, and vice versa.
     * 
     * @var String
     */
    private $_rule;
    
    /*
     * The callback function to call when the rule is matched
     * 
     * @var callable
     */
    private $_callback;
    
    /*
     * The Controller to direct to when the rule is matched
     * 
     * @var String
     */
    private $_controller;
    
    /*
     * The Action in the Controller to direct to when the rule is matched
     * 
     * @var String
     */
    private $_action;
    
    /*
     * Name of variables correseponding to each segments in the rule. If the 
     * segment is not specified with any variable, the variable name will be
     * set to false
     * e.g. /:Controller/:Action/$id:ID/* => [false, false, 'id', false]
     * 
     * @var Array
     */
    private $_bindedVar = array();
    
    /*
     * The number of segments defined in the rule. Allows the matching process
     * to first check for number of segments before performing costly regular
     * expression matching
     * 
     * @var Integer
     */
    private $_segmentCount;
    
    /*
     * Constructor
     * 
     * Accept a route rule definition and prepare a RegEx for matching. The 
     * constructor is responsible for storing the arguments and prepare all the
     * necessary information for quick matching when displatch. The extraction
     * process is left behind and execute during dispatch process to minimize
     * the costly overhead.
     * 
     * @param String|Array $method the request method|list of request method 
     *  the rule is applied to
     * @param String $rule
     * @param Array|callable $callback
     */
    public function __construct($method, $rule, $callback) {
        $this->setArguments(array(
            'method' => $method, 
            'rule' => $rule, 
            'callback' => $callback
        ));
        
        // check for $method validity
        if (is_array($method)) {
            foreach ($method as $key => $value) {
                $this->_requestMethodCheck($value);
            }
        } else {
            $this->_requestMethodCheck($method);
            $method = array($method);
        }
        $this->setMethod($method);
        
        // extract segments and calculate count
        $segments = explode('/', trim($rule, '/'));
        $this->setSegments($segments);
        $this->setSegmentCount(count($segments));
        
        // check for wildcard trailing
        if (substr($rule, -2, 2) == '**') {
            $this->setHasWildcardTrailing(true);
        } else {
            $this->setHasWildcardTrailing(false);
        }
    }
    
    /*
     * extract the segments, validate the segment and prepare all the 
     * information for matching.
     */
    public function latePrepare(){
        $method = $this->getArguments()['method'];
        $rule = $this->getArguments()['rule'];
        $callback = $this->getArguments()['callback'];
        $segments = $this->getSegments();
        
        // prepare the rule for matching
        /*
         * Flag to verify if controller and action are set after preparing the
         * rule
         */
        $_controllerSet = false;
        $_actionSet = false;
        
        $bindedVar = array();
        $ruleResult = '';
        
        foreach ($segments as $key => $value) {
            // match with magic constants
            if ($value == ':Controller') {
                $bindedVar[] = ':Controller';
                $this->setController(':Controller');
                $_controllerSet = true;
                $ruleResult .= '(' . $this->_REGEX['Controller'] . ')' . DS;
                continue;
            }
            if ($value == ':Action') {
                $bindedVar[] = ':Action';
                $this->setAction(':Action');
                $_actionSet = true;
                $ruleResult .= '(' . $this->_REGEX['Action'] . ')' . DS;
                continue;
            }
            
            // match with special * and **
            if ($value == '*') {
                $bindedVar[] = '*';
                $ruleResult .= '(' . $this->_REGEX['*'] . ')' . DS;
                continue;
            }
            if ($value == '**') {
                $bindedVar[] = '**';
                $ruleResult .= $this->_REGEX['**'];
                if ($key < count($segments)-1) {
                    throw new InvalidRouteException(array(
                        'Malformed rule, no expression should be presented after `**`', $rule
                    ));
                }
                break;
            }
            
            /*
             * Match with the $var:[RegEx] pattern, the $var may be absent but 
             * the semicolon : should always present
             */
            $colonPos = strpos($value, ':');
            if ($colonPos !== false) {
                if ($colonPos == 0) {
                    // Pure regular expression
                    $bindedVar[] = '*';
                    $regEx = ltrim($value, ':');
                } else {
                    $ruleSegments = explode(':', $value);
                    $dollarSignPos = strpos($ruleSegments[0], '$');
                    if ($dollarSignPos === false) {
                        throw new InvalidRouteException(array(
                            "Unrecongized variable name `{$ruleSegments[0]}`", $rule
                        ));
                    }
                    $bindedVar[] = ltrim($ruleSegments[0], '$');
                    $regEx = $ruleSegments[1];
                }
                // validate regular expression
                if ($regEx[0] == '{' && $regEx[strlen($regEx)-1] == '}') {
                    $regEx = substr($regEx, 1, strlen($regEx)-2);
                    $ruleResult .= '(' . $regEx . ')' . DS;
                    continue;
                } else {
                    if (isset($this->_REGEX[$regEx])) {
                        $ruleResult .='(' . $this->_REGEX[$regEx] . ')' .DS;
                        continue;
                    } else {
                        throw new InvalidRouteException(array(
                            "Invalid regular expression `{$regEx}`", $rule
                        ));
                    }
                }
            } else {
                // Pure string
                $ruleResult .= $value . DS;
                continue;
            }
        }
        
        /* 
         * controller and action should only be set in either rule or callback
         */        
        if ($_controllerSet && $_actionSet) {
            if ($callback != '') {
                throw new InvalidRouteException(array(
                    'Duplicate callback in $rule and $callback', 
                    $rule
                ));
            }
        } elseif ($callback instanceof Closure) {
            $this->setController(':Closure');
            $this->setAction(':Closure');
            $this->setCallback($callback);
        } elseif (is_callable($callback)) {
            $this->setController(':Callable');
            $this->setAction(':Callable');
            $this->setCallback($callback);
        } else {
            if (isset($callback['controller'])) {
                $this->setController($callback['controller']);
                $_controllerSet = true;
            }
            if (isset($callback['action'])) {
                $this->setAction($callback['action']);
                $_actionSet = true;
            }
            if (!$_controllerSet || !$_actionSet) {
                if ($callback == '') {
                    throw new InvalidRouteException(array(
                        "No recognized callback was provided", $rule
                    ));
                } else {
                    // a redirection
                    $this->setController(':Redirect');
                    $this->setAction($callback);
                }
            }
        }
        
        $this->setBindedVar($bindedVar);
        $this->setRule(rtrim($ruleResult, '/'));
        $this->_autoGenerateIsRegEx();
    }
    
    /*
     * Check if the request method provided is valid
     * 
     * @param $method The request method
     * 
     * @return Boolean
     */
    private function _requestMethodCheck($method) {
        if (in_array($method, $this->_METHOD)) {
            return true;
        }
        throw new ArgumentTypeMismatchException(array(
            '$method', 'Route::_construct', 'GET|POST|PUT|DELETE', $method
        ));
    }
    

    /*
     * Auto generate class variable based on values obtained from other class
     * variables. i.e. segment count and whether the rule contains regular
     * expression
     */
    private function _autoGenerateIsRegEx(){
        $this->setIsRegEx((count($this->getBindedVar()) != 0));
    }
    
    // Auto-generated Get and Set method
    public function getArguments() {
        return $this->_arguments;
    }

    public function getMethod() {
        return $this->_method;
    }

    public function getSegments() {
        return $this->_segments;
    }

    public function isRegEx() {
        return $this->_isRegEx;
    }

    public function hasWildcardTrailing() {
        return $this->_hasWildcardTrailing;
    }

    public function getRule() {
        return $this->_rule;
    }

    public function getCallback() {
        return $this->_callback;
    }

    public function getController() {
        return $this->_controller;
    }

    public function getAction() {
        return $this->_action;
    }

    public function getBindedVar() {
        return $this->_bindedVar;
    }

    public function getSegmentCount() {
        return $this->_segmentCount;
    }

    public function setArguments($arguments) {
        $this->_arguments = $arguments;
    }

    public function setMethod($method) {
        $this->_method = $method;
    }

    public function setSegments($segments) {
        $this->_segments = $segments;
    }

    public function setIsRegEx($isRegEx) {
        $this->_isRegEx = $isRegEx;
    }

    public function setHasWildcardTrailing($hasWildcardTrailing) {
        $this->_hasWildcardTrailing = $hasWildcardTrailing;
    }

    public function setRule($rule) {
        $this->_rule = $rule;
    }

    public function setCallback($callback) {
        $this->_callback = $callback;
    }

    public function setController($controller) {
        $this->_controller = $controller;
    }

    public function setAction($action) {
        $this->_action = $action;
    }

    public function setBindedVar($bindedVar) {
        $this->_bindedVar = $bindedVar;
    }

    public function setSegmentCount($segmentCount) {
        $this->_segmentCount = $segmentCount;
    }
}

