<?php

class CharConvUtil {
    
	static function makeMaps() {
        for($i=32; $i<=255; $i++) {
            $latin1_utf8[chr($i)] = utf8_encode(chr($i));
            $utf8_latin1[utf8_encode(chr($i))] = chr($i);
        }
        global $_CharConvUtilMap;
        $_CharConvUtilMap=array(
			'latin1_utf8'=> $latin1_utf8,
			'utf8_latin1'=> $utf8_latin1,
		);
        
        define('_CharConvUtilMap_Registered_',TRUE);
    }
    static function getMap($what) {
     	global $_CharConvUtilMap;
        
     	if (!defined('_CharConvUtilMap_Registered_')) {
     	 	CharConvUtil::makeMaps();
     	}
     	
     	return ArrayUtil::getValue($_CharConvUtilMap,$what,array());
    }
    
    static function mixedToLatin1($string) {
     	$utf2latin = CharConvUtil::getMap('utf8_latin1');
        foreach( $utf2latin as $key => $val ) {
            $string = str_replace($key, $val, $string);
        }
        return $string;
    }

    static function mixedToUTF8($string) {
        return utf8_encode(CharConvUtil::mixedToLatin1($string));
    }
}

?>