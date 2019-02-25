<?php

namespace Codeh\HnS;

use pocketmine\{Server, Player};

use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\tile\Sign;
use pocketmine\level\Level;
use pocketmine\item\Item;

class GameSender extends pocketmine\scheduler\Task
{
	public function __construct(\Codeh\HnS\HnS $plugin) {
		$this->plugin = $plugin;
	}
	public function onRun($tick)
	{
		$config = $this->plugin->getConfig();
		$rbharenas = $config->get("rbharenas");
    
		if(!empty($rbharenas))
		{
			foreach($rbharenas as $arena)
			{
				$time = $config->get($arena . "PlayTime");
				$mins = floor($time / 60 % 60);
				$secs = floor($time % 60);
				if($secs < 10){ $secs = "0".$secs; }
				$timeToStart = $config->get($arena . "StartTime");
				$levelArena = $this->plugin->getServer()->getLevelByName($arena);
				if($levelArena instanceof Level)
				{
					$playersArena = $levelArena->getPlayers();
					if( count($playersArena) == 0)
					{
						$config->set($arena . "PlayTime", $this->plugin->playtime);
						$config->set($arena . "StartTime", 90);
					} else {
						if(count($playersArena) >= 2)
						{
							if($timeToStart > 0) //TO DO fix player count and timer
							{
								$timeToStart--;
								
								switch($timeToStart)
								{
									case 10:
										foreach($playersArena as $pl)
										{
											$pl->sendPopup($this->plugin->prefix . " §7•>§c Attention!§f your inventory will be wiped..");
										}
									break;
									
									case 7: //wipes inventory
										foreach($playersArena as $pl)
										{
											$pl->getInventory()->clearAll();
										}
									break;
									
									case 5: //inserts bow
										foreach($playersArena as $pl)
										{
											$this->plugin->insertBow($pl);
										}
									break;
									
									case 3: //inserts axe
										foreach($playersArena as $pl)
										{
											$this->plugin->insertAxe($pl);
										}
									break;
											//insert arrow is in playGame() function
									default:
										foreach($playersArena as $pl)
										{
											$pl->sendPopup("§l§7[ §f". $timeToStart ." seconds to start §7]");
										}
								}
								
								$config->set($arena . "StartTime", $timeToStart);
							} else {
								$aop = count($levelArena->getPlayers());
								if($aop >= 2)
								{
									foreach($playersArena as $pla)
									{
										$pla->sendTip("§l§fK ".$this->plugin->kills[ $pla->getName() ]." : D ".$this->plugin->deaths[ $pla->getName() ]);
										$pla->sendPopup("§l§7Game ends in: §b".$mins. "§f:§b" .$secs);
									}
								}
								
								$time--;
								switch($time)
								{
									case 299:
										$this->plugin->assignSpawn($arena);
										foreach($playersArena as $pl)
										{
											$pl->addTitle("§l§fRo§7b§fin §aHood","§l§fYou are playing on: §a" . $arena);
										}
									break;
									
									case 239:
										foreach($playersArena as $pl)
										{
											$pl->addTitle("§l§7Countdown", "§b§l".$mins. "§f:§b" .$secs. "§f remaining");
										}
									break;
									
									case 179:
										foreach($playersArena as $pl)
										{
											$pl->addTitle("§l§7Countdown", "§b§l".$mins. "§f:§b" .$secs. "§f remaining");
										}
									break;
									
									default:
									if($time <= 0)
									{
										$this->plugin->announceWinner($arena);
										$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
										$this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
										foreach($playersArena as $pl)
										{
											$pl->addTitle("§lGame Over","§cYou have played on: §a" . $arena);
											$pl->setHealth(20);
											$this->plugin->leaveArena($pl);
										}
										$time = $this->plugin->playtime;
									}
								}
								$config->set($arena . "PlayTime", $time);
							}
						} else {
							if($timeToStart <= 0)
							{
								foreach($playersArena as $pl)
								{
									$this->plugin->announceWinner($arena, $pl->getName());
									$pl->setHealth(20);
									$this->plugin->leaveArena($pl);
									$this->plugin->api->addMoney($pl->getName(), mt_rand(390, 408));//bullshit
									$this->plugin->givePrize($pl);
									//$this->getResetmap()->reload($levelArena);
								}
								$config->set($arena . "PlayTime", $this->plugin->playtime);
								$config->set($arena . "StartTime", 90);
							} else {
								foreach($playersArena as $pl)
								{
									$pl->sendPopup("§e§l< §7need more player(s) to start§e >");
								}
								$config->set($arena . "PlayTime", $this->plugin->playtime);
								$config->set($arena . "StartTime", 90);
							}
						}
					}
				}
			}
		}
		$config->save();
	}
}
