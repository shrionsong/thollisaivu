<?php

class Base {
	function __construct($args = array()){
		// build all args into their corresponding class properties
		foreach($args as $key => $val) {
			// only accept keys that have explicitly been defined as class member variables
			if(property_exists($this, $key)) {
				$this->{$key} = $val;
			}
		}
	}
}

class Nool{
	var $athikarangal = array();
}

class Athikaram extends Base {
	var $peyar;
	var $en;
	var $iyalgal = array();
}

class Iyal extends Base {
	var $peyar;
	var $en;
	var $paakkal = array();
}

class Paa extends Base {
	var $varigal = array();
	var $en;
//	var $vithigal = array();
}

class Vithi extends Base {
	var $regex;
	var $vilakkam;
}

class IlakkiyaPaguppi{

	function jsonaakku($moolakoppu,$velikoppu){ 
		$nool = new Nool();
		$noolUrai = file_get_contents($moolakoppu);
		//$paakkal = preg_split("/(\r\n|\n|\r){3}/",$paadal,-1,PREG_SPLIT_NO_EMPTY)
		$athikarangal = preg_split("/[ \t]*பாகம் (\d{1,3}) - (.*)(?:\r\n|\n|\r)+/",$noolUrai,-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		
		$a=0;
		while($a<count($athikarangal)-2)
		{
			$athikaram = new Athikaram(['peyar'=>$athikarangal[$a+1],'en'=>$athikarangal[$a]]);
			$iyalgal = preg_split("/[ \t]*(\d{1,3})\.[ \t]*(.*)(?:\r\n|\n|\r)+/",$athikarangal[$a+2],-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
			
			$i=0;
			while($i<count($iyalgal)-2)
			{
				$iyal = new Iyal(['peyar'=>$iyalgal[$i+1],'en'=>$iyalgal[$i]]);
				$paakkal = preg_split("/[ \t]*(\d{1,3})[ \t]*(?:\r\n|\n|\r)+/",$iyalgal[$i+2],-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
				
				$p=0;
				while($p<count($paakkal)-1)
				{
					$paa = new Paa([
							'en'=>$paakkal[$p+1],
							'varigal'=>preg_split("/\r\n|\n|\r/",$paakkal[$p],-1,PREG_SPLIT_NO_EMPTY)
					]);
					$iyal->paakkal[$paa->en] = $paa;
					$p += 2;
				}
				$athikaram->iyalgal[$iyal->en] = $iyal;
				$i += 3;
			}
			$nool->athikarangal[$athikaram->en] = $athikaram;
			$a += 3;
		}
		file_put_contents($velikoppu,json_encode($nool,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	}

}

$ip = new IlakkiyaPaguppi();
$ip->jsonaakku('tholkappiyam.txt','tholkappiyam.json');


?>
