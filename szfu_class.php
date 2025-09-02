<?php

class szfu
{
    public static function validate($data = [], $hashid = '', $hashkey = ''){
        $str = '';
        if (isset($data)){
            if (isset($data['Validate'])){
                unset($data['Validate']);
            }
            ksort($data);
            $str = "HashID=$hashid";
            foreach($data as $id => $value){
                $str .= "&$id=$value";
            }
            $str .= "&HashKey=$hashkey";
            $str = urlencode($str);
            $str = strtolower($str);
            $str = str_replace(['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'], ['-','_','.','!','*','(',')'], $str);
            $str = md5($str);
            $str = strtoupper($str);
        }
        return $str;
    }
    public static function post($url, $set){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($set));
        if (substr($url, 0, 8) == 'https://') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        $str = curl_exec($ch);
        if ($errno = curl_errno($ch)){
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $str;
    }
}

?>