<?php

namespace Night\CommonBundle\Utils;

class StringUtil
{
	
	/**
	 * Enleve tous les numeros a la fin d'une chaine de caracteres.
	 * 
	 * @param text $text
	 * @return text 
	 */
	public function removeNumsAtEnd($text)
	{
		while(is_numeric(substr($text, -1))){
			$text = substr($text, 0, -1);
		} 
		return $text;
	}

	
	/**
     * Slugify text into lowercase
     *
     * @param $text
     * @return mixed|string
     */
     public function slugify($text, $isCamelCase = true)
    {
        // replace non letter or digits by -
        $text = preg_replace('#[^\\pL\d]+#u', '-', $text);
		// Remplace les lettre en majuscules		
		if($isCamelCase){
		   $text = preg_replace('#\p{Lu}#u', '-\0', $text);		
		}
        // trim	
        $text = trim($text, '-');
        // transliterate
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }
        // lowercase
	
        $text = strtolower($text);
        // remove unwanted characters
        $text = preg_replace('#[^-\w]+#', '', $text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }
	
	 public function unSlugify($text){
		return lcfirst(str_replace(" ", "", ucwords(str_replace("-", " ", $text))));
	}
	
	public function prettyUrlToImmoUrl($query)
    {
		$tab = explode("/", $query);

		$query = "";
		for($i = 0 ; $i < count($tab) ; $i += 2 ){
			$key = ucfirst(self::unSlugify($tab[$i]));
			$val = ($tab[$i+1] == "n-a" || empty($tab[$i+1])) ? "" :  ucfirst(self::unSlugify($tab[$i+1]));
			
			if(!empty($val)){
				$query .= $key ."=". $val ."&"; 
			}
		}
		return substr($query, 0 , strlen($query) -1);
	}
}