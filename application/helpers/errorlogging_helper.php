<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**/
if ( ! function_exists('log_rmessage'))
{
    function log_rmessage($level = 'level', array $message, $file = 'userlogin_log')
    {
        $CI =& get_instance();
        $filename = $CI->config->config['logrmessage_path'].$file.'_'.date('m-Y').'.php';
        $date_format = $CI->config->config['logrmessage_date_format'];

        $message = empty($message) ? $_SERVER['REQUEST_URI'] : $message;


        if(empty($message)) {
            $comma_separated = $_SERVER['REQUEST_URI'];
        } else {
            $comma_separated = '';
            $count = 0;

            while(list($key, $val) = each($message)) {
                if($count == 0) { $comma_separated .= $key.'=>'.$val; }
                else { $comma_separated .= '|'.$key.'=>'.$val; }
                $count++;
            }
        }


        $time = time();
        $save_message  = '';

        if ( ! file_exists($filename)) {
            $save_message .= "<"."?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?".">\n\n";
        }

        if ( ! $handle = @fopen($filename, FOPEN_WRITE_CREATE)) {
            return FALSE;
        }

        $save_message .= strtoupper($level).' - '.$time.' or readable '.date($date_format, $time).' --> '.$comma_separated.''.PHP_EOL;

        flock($handle, LOCK_EX);
        fwrite($handle, $save_message);
        flock($handle, LOCK_UN);
        fclose($handle);

        @chmod($filepath, FILE_WRITE_MODE);

    }
}

