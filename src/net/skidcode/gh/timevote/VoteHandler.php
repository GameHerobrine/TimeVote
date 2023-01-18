<?php
namespace net\skidcode\gh\timevote;

use Level;
use Player;
use ServerAPI;

class VoteHandler
{
	/**
	 * Entry Example:
	 * world1 => [
	 * 	total_voted => 0,
	 * 	voting_time => "day",
	 *  level_inst => Level
	 *  players_voted => []
	 * ]
	 * @var array
	 */
	private $voteArray;
	
	/**
	 * Entry Example:
	 * world1 => false
	 * @var array
	 */
	private $voteTimeoutArray;
	/**
	 * @var ServerAPI
	 */
	private $api;
	/**
	 * @var MessageBuilder
	 */
	private $builder;
	
	const VOTE_OK = 0;
	const VOTE_STARTED_ALREADY = 1;
	const VOTE_TIMEOUT = 2;
	
	
	public function __construct(ServerAPI $api, MessageBuilder $builder){
		$this->api = $api;
		$this->builder = $builder;
		$this->voteArray = [];
		$this->voteTimeoutArray = [];
	}
	
	public function vote(Player $issuer, $arg){
		$level = $issuer->level->getName();
		if(!isset($this->voteArray[$level])) return $this->builder->build("No vote is currently started.");
		$voteEntry = &$this->voteArray[$level];
		if(isset($voteEntry["players_voted"][$issuer->iusername])){
			return $this->builder->build("You have already voted.");
		}
		
		$voteEntry["players_voted"][$issuer->iusername] = $arg == "yes" ? true : false;
		if($arg == "yes"){
			++$voteEntry["total_voted"];
		}
		
		
		/*If more than a half of players voted, change time*/
		$pls = count($issuer->level->players);
		if(((ceil($pls / 2) + ($pls % 2 == 0 ? 1 : 0)) <= $voteEntry["total_voted"]) || count($voteEntry["players_voted"]) >= $pls){
			$this->handleVoteQueue($level);
			unset($this->voteArray[$level]);
		}
		return $this->builder->build("You have succesfully voted.");
	}
	
	public function noTimeDelay($levelName){
		unset($this->voteTimeoutArray[$levelName]);
	}
	
	public function handleVoteQueue($levelName){
		if(isset($this->voteArray[$levelName])){
			$entry = $this->voteArray[$levelName];
			$lvlInst = $entry["level_inst"];
			$pls = count($lvlInst->players);
			$this->voteTimeoutArray[$levelName] = true;
			$this->api->schedule(ConfigConstants::$TIMEOUTDELAY, [$this, "noTimeDelay"], $levelName); 
			if((ceil($pls / 2) + ($pls % 2 == 0 ? 1 : 0)) <= $entry["total_voted"]){
				$this->switchTime($lvlInst, $entry["voting_time"]);
				$this->api->chat->broadcast($this->builder->build("Changed time to {$entry["voting_time"]} in {$levelName}. ({$entry["total_voted"]}/{$pls})"));
				unset($this->voteArray[$levelName]);
				return;
			}
			unset($this->voteArray[$levelName]);
			$this->api->chat->broadcast($this->builder->build("Vote Failed. Try again next time. ({$entry["total_voted"]}/{$pls})"));
		}
	}
	
	public function checkCanVote(Level $level){
		if(isset($this->voteArray[$level->getName()])){
			return VoteHandler::VOTE_STARTED_ALREADY;
		}
		if(isset($this->voteTimeoutArray[$level->getName()]) && $this->voteTimeoutArray[$level->getName()] === true){
			return VoteHandler::VOTE_TIMEOUT;
		}
		return VoteHandler::VOTE_OK;
	}
	
	private function switchTime(Level $level, $arg){
		$this->api->time->set($arg, $level);
	}
	
	public function startVote(Player $issuer, $arg){
		$check = $this->checkCanVote($issuer->level);
		if($check == VoteHandler::VOTE_STARTED_ALREADY){ 
			return $this->builder->build("Another vote is started in this world already!");
		}elseif($check == VoteHandler::VOTE_TIMEOUT){
			return $this->builder->build("The vote cannot be created now.");
		}
		if(count($issuer->level->players) === 1){
			$this->switchTime($issuer->level, $arg);
			return $this->builder->build("You have switched time to $arg in {$issuer->level->getName()}");
		}else{
			$levelName = $issuer->level->getName();
			$this->voteArray[$levelName] = [
				"total_voted" => 1,
				"players_voted" => [$issuer->iusername => true],
				"voting_time" => $arg,
				"level_inst" => $issuer->level
			];
			$this->api->schedule(ConfigConstants::$VOTETIME, array($this, "handleVoteQueue"), $levelName);
			$this->api->chat->broadcast($this->builder->build("{$issuer} started vote to set time in {$levelName} to {$arg}.\nType /vote yes or /vote no to vote")); //TODO bc
		}
		
	}
	
}

