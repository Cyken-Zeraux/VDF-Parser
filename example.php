<?php

require_once('vdf_parse.php');

// for TF2 or other source game language files, here so you don't fall into the trap of character encoding 'auto-conversions'.
function from_ucs2_to_utf8_preserve($filestring)
{
	$text = NULL;
	if(preg_match('!!u', $filestring))
	{

		//from jasonhao
		//http://stackoverflow.com/questions/10290849/how-to-remove-multiple-utf-8-bom-sequences-before-doctype
		$bom = pack('H*','EFBBBF');
		$text = preg_replace("/^$bom/", '', $filestring);

	} else {
		//treat as UCS-2, because character encodings are difficult to actually determine.
		//and TF2 defaults to UCS-2 on language encodings.
		$language_utf8 = iconv('UCS-2LE', 'UTF-8', $filestring);

		//from jasonhao
		//http://stackoverflow.com/questions/10290849/how-to-remove-multiple-utf-8-bom-sequences-before-doctype
		$bom = pack('H*','EFBBBF');
		$text = preg_replace("/^$bom/", '', $language_utf8);

	}
	return $text;
}

$basestring = dirname(__FILE__) . DIRECTORY_SEPARATOR;

//Will automatically keep UTF-8, or convert to UTF-8 from UCS-2.
$text = from_ucs2_to_utf8_preserve(file_get_contents($basestring . 'vdftest.txt'));

$text2 = from_ucs2_to_utf8_preserve(file_get_contents($basestring . 'vdftest_ucs2.txt'));

$object = vdf_parse($text, true, "\n");

$object2 = vdf_parse($text2, true, "\n");

var_dump($object);
var_dump($object2);


?>