<?php
/**
 *
 * @package		Oreo
 * @author		liuyoubin
 * @copyright	Copyright (c) 2013 - 2013
 * @license		http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link		http://baidu.com
 * @since		Version 1.0
 * @create		2013-11-6
 * @filesource
 */

define("DEFAULT_LOG_BASE_PATH", dirname(__FILE__)."../../log/");

class Logger{
	
	const LEVEL_NONE    = 0x00;
	const LEVEL_FATAL   = 0x01;
	const LEVEL_WARNING = 0x02;
	const LEVEL_NOTICE  = 0x04;
	const LEVEL_TRACE   = 0x08;
	const LEVEL_DEBUG   = 0x10;
	const LEVEL_ALL     = 0xFF;
	
	const MAX_FILE_SIZE = 1024000000;
	const MAX_LINE_SIZE = 1024;
	
	private static $Levels = array(
			self::LEVEL_NONE    => 'NONE',
			self::LEVEL_FATAL   => 'FATAL',
			self::LEVEL_WARNING => 'WARNING',
			self::LEVEL_NOTICE  => 'NOTICE',
			self::LEVEL_TRACE	=> 'TRACE',
			self::LEVEL_DEBUG   => 'DEBUG',
			self::LEVEL_ALL     => 'ALL',
	);
	
	private static $instance = null;
	
	private static $config = null;
	
	private static $isConfigured = false;
	
	private static $basicFields = null;
	
	private $logPath = null;
	
	private $fileExt = ".log";
	
	private $appName = "";
	
	private $maxFileSize = self::MAX_FILE_SIZE;
	
	private $maxLineSize = self::MAX_LINE_SIZE;
	
	private $level = self::LEVEL_ALL;
	
	private $logId = 0;
	
	private function __construct(){
		if(null === self::$config){
			$this->logPath = DEFAULT_LOG_BASE_PATH."log/";
		}else{
			$this->logPath = isset(self::$config['logPath']) ? self::$config['logPath'] : DEFAULT_LOG_BASE_PATH."log/";
			
			if(isset(self::$config['maxFileSize'])){
				$this->maxFileSize = (int)self::$config['maxFileSize'];
			}
			if(isset(self::$config['maxLineSize'])){
				$this->maxLineSize = (int)self::$config['maxLineSize'];
			}
			if(isset(self::$config['level'])){
				$this->level = self::$config['level'];
			}
			if(isset(self::$config['fileExt'])){
				$this->fileExt = trim(self::$config['fileExt']); 
			}
			if(isset(self::$config['appName'])){
				$this->appName = trim(self::$config['appName']);
			}
		}
		$this->generateLogId();
	}
	
	private static function getInstance(){
		if(null === self::$instance){
			self::$instance = new Logger();
		}
		return self::$instance;
	}
	
	public static function configure(array $config){
		if(self::$config === null){
			self::$config = $config;
			self::$isConfigured = true;
		}
	}
	
	public static function isConfigured(){
		return self::$isConfigured;
	}
	
	public static function initBasicFields(array $basicFields){
		if(self::$basicFields === null){
			self::$basicFields = $basicFields;
		}
	}
	
	public function log($level,$msg,array $args = array(),$depth = 0){
		if(!($level & $this->level) || !isset(self::$Levels[$level])){
			return ;
		}
		$logFile = $this->choseLogFile($level);
		
		$trace = debug_backtrace();
		$depth = $depth > count($trace) ? count($trace) - 1 : $depth;
		$file = isset($trace[$depth]['file']) ? basename($trace[$depth]['file']) : '';
		$line = isset($trace[$depth]['line']) ? $trace[$depth]['line'] : '';
		
		$strArgs = self::$Levels[$level];
		
		$strArgs .= empty($this->appName) ? "" : " ".$this->appName;
	
		$strArgs .= sprintf(" logId=[%u] trace=[%s] time=[%s]",
				$this->logId,
				$file.":".$line,
				date("Y-m-d H:i:s"));
		
		if(null !== self::$basicFields){
			$args = array_merge(self::$basicFields,$args);
		}
		foreach( $args as $key => $value )
		{
			$strArgs .= "{$key}=[$value] ";
		}
		
		$strArgs .= "$msg\n";
		
		if (strlen($strArgs) > $this->maxLineSize) {
			$strArgs = substr($strArgs, 0,$this->maxLineSize).PHP_EOL;
		}
		
		if($this->maxFileSize > 0){
			clearstatcache();
			$size = filesize($logFile);
			if($size && $size > $this->maxFileSize){
				unlink($logFile);
			}
		}
		return file_put_contents($logFile, $strArgs,FILE_APPEND);
	}
	
	public static function warning($msg,array $args = array(),$depth = 0){
		$ins = self::getInstance();
		$ins->log(self::LEVEL_WARNING,$msg,$args,$depth+1);
	}
	
	public static function fatal($msg,array $args = array(),$depth = 0){
		$ins = self::getInstance();
		$ins->log(self::LEVEL_FATAL,$msg,$args,$depth+1);
	}
	
	public static function notice($msg,array $args = array(),$depth = 0){
		$ins = self::getInstance();
		$ins->log(self::LEVEL_NOTICE,$msg,$args,$depth+1);
	}
	
	public static function trace($msg,array $args = array(),$depth = 0){
		$ins = self::getInstance();
		$ins->log(self::LEVEL_TRACE, $msg,$args,$depth+1);
	}
	
	public static function debug($msg,array $args = array(),$depth = 0){
		$ins = self::getInstance();
		$ins->log(self::LEVEL_DEBUG, $msg,$args,$depth+1);
	}
	
	public static function getLogId(){
		return self::getInstance()->logId;
	}
	
	private function generateLogId(){
		$requestTime = gettimeofday();
		$this->logId = intval($requestTime['sec'] * 100000 + $requestTime['usec'] / 10) & 0xFFFFFFFF;
	}
	
	private function choseLogFile($level){
		if(!empty($this->appName)){
			$logFile = $this->logPath.$this->appName."_".date("Y-m-d");
		}else{
			$logFile = $this->logPath.date("Y-m-d");
		}
		if(intval($level) & self::LEVEL_WARNING || intval($level) & self::LEVEL_FATAL){
			$logFile = $logFile.".err";
		}else{
			$logFile = $logFile.".com";
		}
		
		$logFilePath = $logFile.$this->fileExt;
		
		if(!file_exists($this->logPath)){
			@mkdir($this->logPath);
		}
		
		if(!file_exists($logFilePath)){
			@touch($logFilePath);
		}
		
		return $logFilePath;
	}
}
