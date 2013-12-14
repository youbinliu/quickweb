<?php
class Renn {
	 
	private $appid = '';
	private $secret = '';
	
	private static $client = null;
	
	public function __construct()
	{
		$ci =& get_instance();
		$ci->config->load('renren');
		$this->appid = $ci->config->item('renren_key');
		$this->secret = $ci->config->item('renren_secret');

		//load the library
		$this->load();
	}

	private function load()
	{
		include_once 'rennclient/RennClient.php';
		$credentials = array(
				'appId' => $this->appid,
				'secret' => $this->secret
		);
		
		if(self::$client == null){
			$this->client = new RennClient($this->appid,$this->secret);
		}
	}

}