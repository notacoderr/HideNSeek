<?php

namespace Codeh\HnS;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;

class TeamHandler {
	
	private $main;
	public $game;
	
	public function __construct(\Codeh\HnS\HnS $main) {
		$this->main = $main;
	}
	
	public function initTeams($game) : void {
		
		$this->game[$game] = [];
	}
	
	public function setTeamAs(string $game, string $playername, string $team = "waiting") : void {
		switch($team) {
			case "waiting":
				$this->game[$game][$playername] = $team;
			break;
			
			case "seeker":
				$this->game[$game][$playername] = $team;
			break;
			
			case "hider":
				$this->game[$game][$playername] = $team;
			break;
		}
	}
	
	public function assignPlayers(string $game) : void {
		$players = $this->game[$game];
		$firstSeeker = true;
		foreach($players as $name => $team) {
			$pObj = $this->main->getServer()->getPlayer($name);
			if($firstSeeker) {
				$this->setTeamAs($game, $name, "seeker"); # i forgot i can overwrite it.
				$this->main->tpAs($pObj, $game, "seeker");
				$pObj->sendMessage($this->main->config->getNested('messages.FirstSeeker'));
				$pObj->setNameTag(""); # use an empty nametag
				$pObj->setGameMode(2);
				
				$hideTime = $this->main->running[$game]["hide-time"] + 3;
				$pObj->addEffect((new EffectInstance(Effect::getEffect(15)))->setDuration($hideTime * 20)->setAmplifier(2));
				
				$firstSeeker = false;
			} else {
				$this->setTeamAs($game, $name, "hider");
				$this->main->tpAs($pObj, $game, "hider");
				$pObj->sendMessage($this->main->config->getNested('messages.AsHider'));
				$pObj->setNameTag("");
				$pObj->setGameMode(2);
			}
		}
	}
	
	public function removeFrom(string $game, string $playername) : void {
		if(array_key_exists($playername, $this->game[$game])) {
			$newList = array_diff_key($this->game[$game], array_flip(array($playername))); # removes the player from the list 
			$this->game[ $game ] = $newList; # fml
		}
	}
	
	public function sameTeam(string $game, string $n1, string $n2) : bool {
		return $this->getTeam($game, $n1) == $this->getTeam($game, $n2);
	}
	
	public function getTeam(string $game, string $playername) : string {
		return $this->game[$game][$playername];
	}
	
	public function countTeam(string $game, string $team) : int {
		return count(array_keys($this->game[$game], $team));
	}
	
	function shuffleTeams($game) { 
		$list = $this->game[$game];
		if (!is_array($list)) return; 
		$keys = array_keys($list); 
		shuffle($keys); 
		$random = array(); 
		foreach ($keys as $key) { 
			$random[$key] = $list[$key]; 
		}
		$this->game[$game] = $random;
		#var_dump($this->game[$game]);
	}
}