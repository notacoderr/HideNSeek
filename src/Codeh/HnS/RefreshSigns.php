<?php

namespace Codeh\HnS;

use pocketmine\Server;
use pocketmine\Player;

use pocketmine\utils\TextFormat as TF;

use pocketmine\tile\Sign;
use pocketmine\level\Level;

class RefreshSigns extends \pocketmine\scheduler\Task
{
	private $main;
	
	public function __construct(\Codeh\Hns\Hns $core)
	{
		$this->main = $core;
	}
  
	public function onRun($tick)
	{
		
		$level = $this->main->getServer()->getDefaultLevel();
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Sign) {	
				$text = $t->getText();
				if(TF::clean($text[0]) == $this->main->prefix)
				{
					if(array_key_exists(TF::clean($text[1]), $this->main->arenas))
					{
						$game = TF::clean($text[1]);
						$playercount = $this->main->playercounts[$game];
						$arenalevel = $this->main->getServer()->getLevelByName($game);
						$max = $this->main->arenadata->getMax($game);
						$playtime = $this->main->running[$game]["play-time"];
						$t->setText(
							TF::BOLD . TF::RED . $this->main->prefix,
							TF::BOLD . TF::AQUA . $game,
							TF::YELLOW  . ($playercount >= $max ? "F U L L" : $playercount . " / " . $max),
							TF::GREEN . ($playtime < $this->main->playTime ? "Playing" : "Waiting")
						);
					}
				}
			}
		}
	}

}
