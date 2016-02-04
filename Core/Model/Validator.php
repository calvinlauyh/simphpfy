<?php

/* 
 * Created by Hei
 */
class Validator{
    /*
     * Check if a string is non-empty
     * 
     * @param String $value
     * 
     * @return Boolean Result
     */
    public static function nonEmpty($value) {
        /*
         * empty() gives a False-Negative when the value is evulated to "0", 
         * details can be found in PHP documentation
         * http://php.net/manual/en/types.comparisons.php
         */
        if (empty($value) && $value != '0') {
            return FALSE;
        }
        if (is_array($value) || is_object($value)) {
            return TRUE;
        }
        return self::_regEx('@^[^\s]+$@', $value);
    }
    
    /*
     * Check if a required field has been filled, it is NOT non-empty check. It
     * considers single/consecutive whitespace(s) as valid input
     * 
     * If the input is an array, it will check whether the 0th index of the 
     * array contains an index of name 1st index of the array.
     * i.e. array($array, 'test') will check whether $array has an index of 
     * `test`
     * 
     * non-empty field is essetially required while required field may not 
     * conform with non-empty
     * 
     * @param String|Array $value
     * 
     * @return Boolean Result
     */
    public static function required($value, $index='') {
        /*
         * use isset() instead of array_key_exists() in the sense that
         * array_key_exists will only check if the index exists in the 
         * array while isset() will return FALSE if the $array['test'] is 
         * evaluated to null, which make much more sense to a required field
         */
        return ($index == '')? isset($value): isset($value[0][$value[1]]);
    }
    
    /*
     * Type checking
     */
    public static function integer($value) {
        return is_numeric($value) && (intval($value)-$value == 0);
    }
    
    /*
     * @param Mixed $value, The value to check for
     * @param Boolan $followPHP, Flag indicating whether to follow the PHP 
     * standard, i.e. anything that can be cast to String returns TRUE
     */
    public static function string($value, $followPHP=FALSE) {
        if ($followPHP) {
            if((!is_array($item)) &&
                ((!is_object($item) && settype($item, 'string') !== FALSE) ||
                (is_object($item) && method_exists($item, '__toString')))) {
                return TRUE;
            }
        } else {
            return is_scalar($value);
        }
    }
    
    /*
     * Alias of string()
     */
    public static function text($value, $followPHP=FALSE) {
        return self::string($value, $followPHP);
    }
    
    public static function float($value) {
        return is_numeric($value);
    }
    
    public static function arraytype($value) {
        return is_array($value);
    }
    
    public static function object($value) {
        return is_object($value);
    }
    
    /*
     * Check if a string is a valid email accroding to RFC5322 modified
     * 
     * @param String $value, The string to check for
     * 
     * @return Boolean, result
     */
    public static function email($value) {
        return (self::_regEx("@[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*\@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?@", $value));
    }
    
    /*
     * Check if a string is a valid unix timestamp
     * 
     * @param String $value, The string to check for
     * 
     * @return Boolean, result
     */
    public static function date($value) {
        return ((int) $value == $value) 
            && ($value <= PHP_INT_MAX)
            && ($value >= ~PHP_INT_MAX);
    }
    
    /*
     * Check if a string contains only alphabets and numbers
     * 
     * @parsm String $value The string to check for
     * 
     * @return Boolean result
     */
    public static function alphaNumeric($value) {
        return self::_regEx('@^\w+$@', $value);
    }
    
    /*
     * Check if a value is equal to a defined value
     * 
     * @pram Mixed $value The value to check for
     * 
     * @return Boolean result
     */
    public static function equal($value, $equal) {
        return ($value == $equal);
    }
    
    /*
     * Perform a operator comparision to a value and a defined value
     * 
     * @param Mixed $value, The value on the left side
     * @param String $operator, The operator
     * @param Mixed $value1, The value of the right side
     * 
     * @return Boolean, The result
     */
    public static function compare($value, $operator, $value1) {
        if ($operator == '>') {
            return ($value > $value1);
        } elseif ($operator == '<') {
            return ($value < $value1);
        } elseif ($operator == '>=') {
            return ($value >= $value1);
        } elseif ($operator == '<=') {
            return ($value <= $value1);
        } elseif ($operator == '==') {
            return ($value == $value1);
        } elseif ($operator == '!=') {
            return ($value != $value1);
        } elseif ($operator == '===') {
            return ($value === $value1);
        } elseif ($operator == '!==') {
            return ($value !== $value1);
        } else {
            return FALSE;
        }
    }
    
    /*
     * Check if a numeric value is between minimum and maximum number
     * 
     * @param Numeric $value, The value to check for
     * @param Numeric $min, The minimum number
     * @param Numeric $max, The maximum number
     * 
     * @return Boolean, reuslt
     */
    public static function range($value, $minimum, $maximum) {
        if (!is_numeric($value)) {
            return FALSE;
        }
        return ($value >= $minimum && $value <= $maximum);
    }
    
    /*
     * Check if a string is in length at least minimum
     * 
     * @param String $value The string to check for
     * @param Integer $minimum The minimum length
     * 
     * @return Boolean result
     */
    public static function minLength($value, $minimum) {
        return (mb_strlen($value) >= $minimum);
    }
    
    /*
     * Check if a string is in length at least minimum
     * 
     * @param String $value The string to check for
     * @param Integer $maximum The maximum length
     * 
     * @return Boolean result
     */
    public static function maxLength($value, $maximum) {
        return (mb_strlen($value) <= $maximum);
    }
    
    /*
     * Check if a string is in length between min and max
     * 
     * @param String $value The string to check for
     * @param Integer $min The minimum length
     * @param Integer $max The maximum length
     * 
     * @return Boolean result
     */
    public static function lengthBetween($value, $min, $max) {
	$length = mb_strlen($value);
        return (($length >= $min) && ($length <= $max));
    }
    
    /*
     * Perform a regular expression and returns if the input mathces or not
     * 
     * @param String $regex The regular expression
     * @param String $value The value to be checked against the RegEx
     * 
     * @return Boolean Success of the RegEx match
     */
    protected static function _regEx($regEx, $value) {
        if (is_string($regEx) && preg_match($regEx, $value)) {
            return TRUE;
        }
        return FALSE;
    }
}
