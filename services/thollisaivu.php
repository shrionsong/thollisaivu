<?php

$debug = false;
$text = '';

if(PHP_SAPI==='cli'){
	$text = $argv[1];
	echo "\n";
}
else
{
	if (! isset($_POST["qtext"]) || $_POST["qtext"] == "") {
		$result->setstatus( "notext" );
	} else {
		$text = $_POST ["qtext"];
	}
}	
	
$result = new Result();
$thol = new Thollisaivu ($result);
dump ("Checking...");
$result = $thol->Check( $text );
$result->vithigal = $thol->vithigal;

echo json_encode ( $result);


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


class Thollisaivu {
	var $sapi;
	var $vithigal = array();
	var $tholkappiyam;
	
	function Thollisaivu($result) {
		//$this->sapi = new MCSearch ( "17ce72ef3d034424b898cad37cadc00d" );
		$this->result = $result;
		$this->tholkappiyam = json_decode(file_get_contents("tholkappiyam.json"),true);
		//print_r($this->tholkappiyam);
		$this->vithigal = $this->VithigalEtru('tholkappiyam_vithigal.tsv');
	}
	
	function VithigalEtru($koppu){
		$vithigal = array();
		$vithigalUrai = file_get_contents($koppu);
		
		$vl = preg_split("/\n|\r\n|\r/",$vithigalUrai,-1,PREG_SPLIT_NO_EMPTY);
		foreach($vl as $en=>$v){
			
			if($en==0) continue;
			
			$va = preg_split('/\t/',$v,-1);
			//print_r($va);
			if(count($va)>6){
				$vithi = new Vithi([
						'athikaraEn'=>$va[0],
						'iyalEn'=>$va[1],
						'paaEn'=>$va[2],
						'vagai'=>$va[3],
						'sari'=> ($va[4]=='சரி'?true:($va[4]=='தவறு'?false:null)),
						'vithi'=>'/'.$va[5].'/',
						'vilakkam'=>$va[6]
				]);
		
				$a = $this->tholkappiyam['athikarangal'][$vithi->athikaraEn];
				$i = $a['iyalgal'][$vithi->iyalEn];
				$p = $i['paakkal'][$vithi->paaEn];
				$vithi->paa = new PaaVivaram([
						'athikaram' => $a['peyar'],
						'iyal' => $i['peyar'],
						'varigal' => $p['varigal']
				]);
				$vithigal[] = $vithi;
			}
		}
		return $vithigal;
	}
	
	
	function Check($urai) {
		$result = new Result();
		dump ( "starting search..." );
		
		try {
			$katturai = new Katturai($urai);
			
			foreach($katturai->patthigal as $patthi){
				foreach($patthi->varigal as $vari){
					foreach($vari->thodargal as $thodar){
						foreach($thodar->sorkal as $sol){
							foreach($this->vithigal as $en=>$vithi){
								if(preg_match($vithi->vithi,$sol->patham)){
									//$result->msg += "match $vithi->vithi,$sol->patham; ";
									$sol->vithigal[]=$en;
									if($vithi->sari===false){ 
										$sol->pizhayEn++;
										$katturai->pizhayEn++;
									}
								}
								else{
									//$result->msg += "no match $vithi->vithi,$sol->patham; ";
								}
							}
							
						}
					}
				}
			}
			
			$result->katturai = $katturai;
			$result->setstatus("success");
			
		} catch (Exception $e) {
			$result->msg = $e->getMessage();
		}
		
		return $result;
	}
	
	function Thedu($urai){
		$count = 0;
		$matches = array();
		$results = json_decode( $this->sapi->Search($urai) );
		//var_dump($results);
		//$this->result->response = json_encode($results);
		
		if ($results->_type == "SearchResponse") {
			
			if(property_exists($results,"webPages")){
				
				if(property_exists($results->webPages,"value"))
					$value = $results->webPages->value;
				
				if(property_exists($results->webPages,"totalEstimatedMatches"))
					$count = $results->webPages->totalEstimatedMatches;
				
				foreach ( $value as $val ) {
					$matches[] = new Match ( $val->id, $val->name, $val->url, $val->displayUrl, $val->snippet, $val->dateLastCrawled );
				}
			}
		}
		
		return array($count,$matches);
	}
}


class MCSearch {
	
	var $url = "https://api.cognitive.microsoft.com/bing/v5.0/search";
	var $key = "";
	
	function MCSearch($key) {
		$this->key = "Ocp-Apim-Subscription-Key:" . $key;
	}
	
	function Search($text) {
		$ci = curl_init ();
		curl_setopt ( $ci, CURLOPT_URL, $this->url . "?q=" . urlencode ( $text ) );
		curl_setopt ( $ci, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt ( $ci, CURLOPT_HTTPHEADER, [ 
				$this->key 
		] );
		curl_setopt ( $ci, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ci, CURLINFO_HEADER_OUT, true );
		
		$resp = curl_exec ( $ci );
		
		$info = curl_getinfo ( $ci );
		curl_close ( $ci );
		
		return $resp;
	}
}

class Match {
	var $id;
	var $name;
	var $url;
	var $dispUrl;
	var $snippet;
	var $date;
	
	function Match($id, $name, $url, $dispUrl, $snippet, $date) {
		$this->id = $id;
		$this->name = $name;
		$this->url = $url;
		$this->dispUrl = $dispUrl;
		$this->snippet = $snippet;
		$this->date = $date;
	}
}

class Result {
	var $status = "";
	var $msg = "";
	var $success = false;
	var $katturai;
	var $vithigal = array();
	
	function setstatus($status) {
		$this->status = $status;
		
		if ($status == "success")
			$this->success = true;
	}
}

class Katturai{
	var $patthigal = array();
	var $kalaven = 0;
	var $thodaren = 0;
	var $ptKalaven = 0;
	var $pizhayEn = 0;
	
	function Katturai($urai){
		$pl = preg_split("/(\r\n|\n|\r)/",$urai,-1,PREG_SPLIT_NO_EMPTY);
		
		foreach($pl as $p){
			$patthi = new Patthi();
			
			$vl = preg_split("/(\.|;|:) +/",$p,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			
			$thodar = null;
			foreach($vl as $v){
				
				if(!strpos($v," ") && $thodar != null){
					$thodar->solthodar.= $v;
					continue;
				}
				
				$vari = new Vari();
				
				$thodar = new Thodar();
				$thodar->solthodar = $v;
				
				$delims = " ,\?\!";
				$sl = preg_split("/[".$delims."]/",$thodar->solthodar,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
				
				$sol = null;
				foreach($sl as $s){
					if(strpos($delims,$s) !== false && $sol != null){
						$sol->patham .= $s;
						continue;
					}
					
					$sol = new Sol();
					$sol->patham = $s;
					$thodar->sorkal[] = $sol;
				}
				
				$vari->thodargal[] = $thodar;
				$this->thodaren++;
				
				$patthi->varigal[] = $vari;
			}
			$this->patthigal[] = $patthi;
		}
		
	}
}

class Patthi{
	var $varigal = array();
}

class Vari{
	var $thodargal = array();
}

class Thodar{
	var $solthodar;
	var $kalaven;
	var $kalavugal = array();
	var $sorkal = array();
}

class Sol{
	var $patham;
	var $pizhayEn;
	var $vithigal = array();
}

class Vithi extends Base {
	var $athikaraEn = 0;
	var $iyalEn = 0;
	var $paaEn = 0;
	var $vagai = "சொல்";
	var $sari = true;
	var $vithi = ""; //regex
	var $vilakkam;
	var $paa;
}

class PaaVivaram extends Base {
	var $varigal = array();
	var $athikaram;
	var $iyal;
}


function dump($obj) {
	global $debug;
	if ($debug)
		var_dump ( $obj );
}

?>

