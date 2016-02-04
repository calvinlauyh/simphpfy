<?php

/* 
 * Created by Hei
 */

/*
 * The base class for SimPHPfy internal exceptions during execution.
 * SimPHPfy interal exceptionss are considered a kind of interal server
 * error and have a error code of 500
 */
class SimPHPfyException extends SimPHPfyBaseException{
    /*
     * 
     */
    protected $_previousMessage = "";
    
    /*
     * A message temlate for this particular Exception. The $_message is 
     * a formatted string which allows the user to feed in information
     * when thrown
     * 
     * @var String
     */
    protected $_template = "";
    
    /*
     * Constructor
     * 
     * Prepare the message by examiinging the presence of template and
     * feed in $message provided
     * 
     * @param String|Array $message the error message describing the reasons
     *  for throwing the Exception
     * @param Integer $code the HTTP error code corresponding to the 
     *  Exception
     */
    public function __construct($message, $code=500) {
        if ($this->_template != "") {
            // feed in the $message into the formatted string template
            $this->_previousMessage = $message;
            if (is_array($message)) {
                /*
                 * if there is more than one piece of information to feed in, 
                 * the $message is in array and should use vsprintf to inject
                 */
                $this->message = vsprintf($this->_template, $message);
            } else {
                // sprintf for single-valued feed in
                $this->message = sprintf($this->_template, $message);
            }
        }
        parent::__construct($this->message, $code);
    }
}
