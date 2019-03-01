<?php

namespace Codeh\HnS;

use pocketmine\{Server, Player};

use pocketmine\level\Level;
use pocketmine\utils\TextFormat;

class GameSender extends \pocketmine\scheduler\Task
{
	private $main;
	
	public function __construct(\Codeh\HnS\HnS $core) {
		$this->main = $core;
	}
	public function onRun($tick)
	{
		$running = $this->main->running;
    
		if(!empty($running))
		{
			foreach($running as $arena)
			{
				#$timeToPlay = $arena["play-time"];
				#$timeToWait = $arena["wait-time"];
				#$timeToHide = $arena["hide-time"];
				$game = $arena["game"];
				$arenaworld = $this->main->arenadata->getWorld($game);
				if(($levelArena = $this->main->getServer()->getLevelByName($arenaworld)) instanceof Level)
				{
					$phase = $arena["phase"];
					$playercount = $this->main->playercounts[$game];
					$minplayer = $this->main->arenadata->getMin($game);
					if($playercount == 0)
					{
						if($arena["wait-time"] <> $this->main->waitTime) $arena["wait-time"] = $this->main->waitTime;
						if($arena["hide-time"] <> $this->main->hideTime) $arena["hide-time"] = $this->main->hideTime;
						if($arena["play-time"] <> $this->main->playTime) $arena["play-time"] = $this->main->playTime;
						if($phase !== "WAIT") $arena["phase"] = "WAIT";
					} else {
						if($playercount >= $minplayer)
						{
							$plist = $this->main->arenas[$game];
							switch($phase)
							{
								case "WAIT":
									if($arena["wait-time"] > 0) //TO DO fix player count and timer
									{
										switch($arena["wait-time"])
										{
											
											case 9:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->sendMessage($this->main->prefix . " > §a Game will start soon!");
												}
											break;
											
											case 7:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->setGameMode(2);
												}
											break;
											case 5:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->addTitle(TextFormat::BOLD. TextFormat::GREEN . "READY", "§cO O O O O O O O O");
												}
											break;
											case 4:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->addTitle(TextFormat::BOLD. TextFormat::GREEN . "READY", "§aO §cO O O O O O O §aO");
												}
												
											break;
											case 3:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->addTitle(TextFormat::BOLD. TextFormat::GREEN . "READY", "§aO O §cO O O O O §aO O");
												}
												
											break;
											case 2:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->addTitle(TextFormat::BOLD. TextFormat::GREEN . "READY","§aO O O §cO O O §aO O O ");
												}
											break;
											case 1:
												foreach($levelArena->getPlayers() as $p)
												{
													$player->addTitle(TextFormat::BOLD. TextFormat::GREEN . "READY", "§aO O O O §cO §aO O O O");
													
												}
											break;
											
											case 0:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->addTitle(TextFormat::BOLD. TextFormat::GREEN . "HIDE", "§aO O O O O O O O O");
												}
												shuffle($plist["waiting"]);
												$arena["phase"] = "HIDE";
											break;

											default:
											foreach($levelArena->getPlayers() as $p)
											{
												$p->sendPopup("§l§7[ §f". $arena["wait-time"] ." seconds to start §7]");
											}
										}
										$arena["wait-time"] -= 1;
									}
								break;
								case "HIDE":
									if($arena["hide-time"] > 0)
									{
										if($arena["hide-time"] == 1)
										{
											$arena["phase"] = "PLAY";
										} else {
											if($arena["hide-time"] == ($this->main->hideTime - 1))
											{
												$seeker = $plist["waiting"][0]; # take the first one
												unset($plist["waiting"][$seeker]);
												$this->main->summon(Server::getInstance()->getPlayer($seeker), $game, "seeker");
											}
											
											if($arena["hide-time"] == ($this->main->hideTime - 5))
											{
												foreach($plist["waiting"] as $n)
												{
													$pObj = Server::getInstance()->getPlayer($n);
													unset($plist["waiting"][ $pObj->getName() ]);
													$this->main->summon($pObj, $arena, "seeker");
												}
											}
											
											foreach($levelArena->getPlayers() as $p)
											{
												$p->addTitle(TextFormat::BOLD . TextFormat::GREEN . "H I D E", $arena["hide-time"] . " seconds to release the seeker");
											}
										}
										$arena["hide-time"] -= 1;
									}
								break;
								case "PLAY":
										#$aop = count($levelArena->getPlayers());
										# TO - DO
										$time = $arena["play-time"];
										$mins = floor($time / 60 % 60);
										$secs = ($s = floor($time % 60)) < 10 ? "0" . $s : $s;
										
										if($playercount >= 2)
										{
											switch($arena["play-time"])
											{
												case 179:
													foreach($levelArena->getPlayers() as $pl)
													{
														$pl->addTitle("§l§7Countdown", "§b§l".$mins. "§f:§b" .$secs. "§f remaining");
													}
												break;
												default:
												if($arena["play-time"] <= 0)
												{
													#game
													$this->main->announceWinner($arena);
													$spawn = $this->main->getServer()->getDefaultLevel()->getSafeSpawn();
													$this->main->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
													foreach($levelArena->getPlayers() as $pl)
													{
														$pl->addTitle("§lGame Over","§cYou have played on: §a" . $game);
														$pl->setHealth(20);
														$this->main->leaveArena($pl);
													}
												} else {
													foreach($levelArena->getPlayers() as $pla)
													{
														$pla->sendTip("§l§fSeekers: " . count($plist["seekers"]) . " : Hiders: " . count($plist["hiders"]));
														$pla->sendPopup("§l§7Remaing time: §b".$mins. "§f : §b" .$secs);
													}
												}
											}
										}
										$arena["play-time"] -= 1;
								break;
							}
							
						} else {
							if($arena["wait-time"] <= 0)
							{
								foreach($levelArena->getPlayers() as $pl)
								{
									$this->main->announceWinner($arena, $pl->getName());
									$pl->setHealth(20);
									$this->main->leaveArena($pl);
									$this->main->api->addMoney($pl->getName(), mt_rand(390, 408));//bullshit
									$this->main->givePrize($pl);
									//$this->getResetmap()->reload($levelArena);
								}
								if($arena["wait-time"] <> $this->main->waitTime) $arena["wait-time"] = $this->main->waitTime;
								if($arena["hide-time"] <> $this->main->hideTime) $arena["hide-time"] = $this->main->hideTime;
								if($arena["play-time"] <> $this->main->playTime) $arena["play-time"] = $this->main->playTime;
								if($phase !== "WAIT") $arena["phase"] = "WAIT";
							} else {
								foreach($levelArena->getPlayers() as $pl)
								{
									$pl->addTitle("","§e§l[ §7Requires ". $minplayer ." or more Player(s)§e ]");
								}
								if($arena["wait-time"] <> $this->main->waitTime) $arena["wait-time"] = $this->main->waitTime;
								if($arena["hide-time"] <> $this->main->hideTime) $arena["hide-time"] = $this->main->hideTime;
								if($arena["play-time"] <> $this->main->playTime) $arena["play-time"] = $this->main->playTime;
								if($phase !== "WAIT") $arena["phase"] = "WAIT";
							}
						}
					} # to do not working
				}
			}
		}
	}
}
