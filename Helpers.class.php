<?php
/**
 * Project:			WPBB3 (Wordpress & phpbb3 integration)
 * File:			Helpers.class.php
 *
 * Description:		Various functions
 *
 * @author:			Mamontov Andrey <andrvm@andrvm.ru>
 * @copyright		2011 Mamontov Andrey (andrvm)
 *
 */

class Helpers {

    /**
     * Generate an unigue value
     */
    static function unique_id($extra = 'c'){

        $rand_seed = '8a414598ba18a512b8fe97f1497fa22b';

        $val = $rand_seed . microtime();
        $val = md5($val);

        return substr($val, 4, 32);
    }

    /**
     * Set cookie
     */
    static function set_cookie($name, $cookiedata, $cookietime, $path='/', $domain = false){

        $name_data = rawurlencode($name) . '=' . rawurlencode($cookiedata);
        $expire = date('D, d-M-Y H:i:s \\G\\M\\T', $cookietime);
        $domain = !$domain  ? '' : '; domain=' . $domain;

        header('Set-Cookie: ' . $name_data . (($cookietime) ? '; expires=' . $expire : '') . '; path=' . $path . $domain . '; HttpOnly', false);
    }

    /**
     * Transform a multidimensional array
     * in an one-dimensional (for curl)
     * @see http://stackoverflow.com/questions/3874762/problem-posting-an-multidimensional-array-using-curl-php
     *
     * @param array $var
     * @param bool  $prefix
     *
     * @return array one-dimensional
     */
    static function flatten_GP_array(array $var, $prefix = false){

        $return = array();

        foreach($var as $idx => $value){

            if(is_scalar($value)){

                if($prefix){
                    $return[$prefix . '[' . $idx . ']'] = $value;
                } else {
                    $return[$idx] = $value;
                }
            } else {
                $return = array_merge($return, self::flatten_GP_array($value, $prefix ? $prefix . '[' . $idx . ']' : $idx));
            }
        }

        return $return;
    }

} // End Helpers Class