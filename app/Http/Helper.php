<?php

namespace App\Http;

class Helper
{

    //Convert Bangla Date Time
    public static function charMonth($char)
    {
        $search_array = array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");
        $replace_array = array("jan", "feb", "mar", "apr", "may", "june", "july", "aug", "sept", "oct", "nov", "dec");
        // convert all month to char
        return str_replace($search_array, $replace_array, $char);
    }
    
    

}
