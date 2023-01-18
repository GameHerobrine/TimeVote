<?php
namespace net\skidcode\gh\timevote;

use Player;
use Plugin;
use ServerAPI;
use Config;

class TimeVote implements Plugin
{
	/**
	 * @var ServerAPI
	 */
	private $api;
	/**
	 * @var MessageBuilder
	 */
	private $builder;
	/**
	 * @var VoteHandler
	 */
	private $voteHandler;
	
	private $config;
	
	private static $INSTANCE;
	
	public function __construct(ServerAPI $api, $server = false)
	{
		$this->api = $api;
		$this->builder = new MessageBuilder("TimeVote");
		$this->voteHandler = new VoteHandler($api, $this->builder);
		self::$INSTANCE = $this;
	}
	
	public function init()
	{
	    $this->config = new Config($this->api->plugin->configPath($this)."TimeVote.cfg", CONFIG_PROPERTIES, [
	        "vote-time-ticks" => 600,
	        "vote-timeout-ticks" => 2000
	    ]);
	    
	    ConfigConstants::$TIMEOUTDELAY = $this->config->get("vote-timeout-ticks");
	    ConfigConstants::$VOTETIME = $this->config->get("vote-time-ticks");
	    
	    $this->api->console->register("vote", "<arg>", function($cmd, $params, $issuer, $alias){
			if(!($issuer instanceof Player)){
				return $this->builder->build("You must be a player to execute this command.");
			}
			$param = isset($params[0]) ? strtolower($params[0]) : "";
			switch($param){
				case "day":
				case "night":
					return $this->voteHandler->startVote($issuer, $param);
				case "yes":
				case "no":
					return $this->voteHandler->vote($issuer, $param);
					
			}
			
			return $this->builder->build("Not enough arguments.");
		});
	    $this->api->console->cmdWhitelist("vote");
	}
	
	public function __destruct(){}
	
	public static function getInstance(){
		return self::$INSTANCE;
	}
}

