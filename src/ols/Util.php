<?php 
class Util
{
    static public function neatKey($key, $total = 30)
    {
        $len = strlen($key);
        return $key . str_repeat(' ', $total - $len);
    }
    
    static public function startsWith($haystack, $needle)
    {
         $length = strlen($needle);
         return (substr($haystack, 0, $length) === $needle);
    }    
}
?>
