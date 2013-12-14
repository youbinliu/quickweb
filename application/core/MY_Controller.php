<?php

class MY_Controller extends CI_Controller{
	

	public function __construct() {
		parent::__construct();
		
	}
	
	public function asyncQuit($data){
		ob_end_clean();
		ob_start();
		header('Content-Type: application/json; charset=UTF-8');
		$finalRet = json_encode ( $data );
		if(isset($_REQUEST['callback'])){
			$fun = $_REQUEST['callback'];
			echo "$fun($finalRet)";
		}else{
			echo $finalRet;
		}
		
		header('Connection: close');
		header("Content-Length: ".ob_get_length());
		ob_end_flush();
		flush();
	}
	
	public function syncQuit($data){
		header('Content-Type: application/json; charset=UTF-8');
		$finalRet = json_encode ( $data );
		if(isset($_REQUEST['callback'])){
			$fun = $_REQUEST['callback'];
			echo "$fun($finalRet)";
		}else{
			echo $finalRet;
		}
		exit();
	}
	
	public function checklogin(){
	
		
	}
}