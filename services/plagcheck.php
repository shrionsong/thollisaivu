<?php

$debug = false;
$result = new Result();

if (! isset($_POST["qtext"]) || $_POST["qtext"] == "") {
	$result->setstatus( "notext" );
} else {
	$text = $_POST ["qtext"];
	$pc = new PlagCheck ($result);
	dump ("Checking...");
	$result = $pc->Check( $text );
}

echo json_encode ( $result, JSON_UNESCAPED_UNICODE);


class PlagCheck {
	var $sapi;
	
	function PlagCheck($result) {
		$this->sapi = new MCSearch ( "17ce72ef3d034424b898cad37cadc00d" );
		$this->result = $result;
	}
	
	function Check($urai) {
		$result = new Result();
		dump ( "starting search..." );
		
		try {
			$katturai = new Katturai($urai);
			
			foreach($katturai->patthigal as $patthi){
				foreach($patthi->varigal as $vari){
					foreach($vari->thodargal as $thodar){
						list($count,$matches) = $this->Thedu('"'.$thodar->solthodar.'"');
						$result->msg = " Text:".$thodar->solthodar." Count:".$count.", ";
						$thodar->kalaven = $count;
						$katturai->kalaven += $count;
						if($katturai->ptKalaven < $count) 
							$katturai->ptKalaven = $count;
						
						foreach($matches as $match){
							$thodar->kalavugal[] = new Kalavu([
									'name'=>$match->name,
									'url'=>$match->url,
									'snippet'=>$match->snippet,
									'dispUrl'=>$match->dispUrl
							]);
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
				$thodar->solthodar = $tmp.$v;
				
				$vari->thodargal[] = $thodar;
				$katturai->thodaren++;
				
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
}

class Kalavu{
	var $name;
	var $url;
	var $snippet;
	var $dispUrl;
	
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

function dump($obj) {
	global $debug;
	if ($debug)
		var_dump ( $obj );
}
?>

