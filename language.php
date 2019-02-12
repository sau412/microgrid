<?php

function lang_load($lang_code) {
        global $json_language;
        global $default_language;
        if($lang_code=='') $lang_code=$default_language;
        $json_language_parsed=json_decode($json_language);
        $current_language=array();
        foreach($json_language_parsed as $key=>$lang_variable) {
                if(property_exists($lang_variable,$lang_code)) $current_language[$key]=$lang_variable->$lang_code;
        }
        return $current_language;
}

function lang_message($code) {
        global $current_language;
        if(isset($current_language[$code])) {
                return $current_language[$code];
        }
        return $code;
}

function lang_select_form($token) {
        return <<<_END
<form name=change_language method=post class='lang_selector'>
<input type=hidden name=action value='change_lang'>
<input type=hidden name=token value='$token'>
<select name=lang onChange='form.submit();'>
<option>language</option>
<option value='en'>English</option>
<option value='ru'>Русский</option>
<option value='fr'>En français</option>
</select>
</form>
_END;
}

function lang_parser($text) {
        while(preg_match('/%([A-Za-z0-9_]+)%/',$text,$matches)) {
                $replace_from=$matches[0];
                $replace_to=lang_message($matches[1]);
//echo "Replacing from '$replace_from' to '$replace_to'<br>";
                $text=str_replace($replace_from,$replace_to,$text);
        }
        return $text;
}

?>
