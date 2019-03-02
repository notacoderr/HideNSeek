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
				$game = $arena["game"];
				$arenaworld = $this->main->arenadata->getWorld($game);
				if(($levelArena = $this->main->getServer()->getLevelByName($arenaworld)) instanceof Level)
				{
					$phase = $arena["phase"];
					$playercount = $this->main->playercounts[$game];
					$minplayer = $this->main->arenadata->getMin($game);
					if($playercount == 0)
					{
						if($arena["wait-time"] <> $this->main->waitTime) $this->main->initGame($game);
						if($arena["hide-time"] <> $this->main->hideTime) $this->main->initGame($game);
						if($arena["play-time"] <> $this->main->playTime) $this->main->initGame($game);
					} else {
						if($playercount >= $minplayer) #($playercount >= 1)
						{
							switch($phase)
							{
								case "WAIT":
									if($arena["wait-time"] > 0) //TO DO fix player count and timer
									{
										switch($arena["wait-time"])
										{
											
											case 8:
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
											case 6:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->addTitle(TextFormat::BOLD. TextFormat::GREEN . "READY", "§cO O O O O O O O O");
												}
											break;
											case 5:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->addTitle(TextFormat::BOLD. TextFormat::GREEN . "READY", "§aO §cO O O O O O O §aO");
												}
												
											break;
											case 4:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->addTitle(TextFormat::BOLD. TextFormat::GREEN . "READY", "§aO O §cO O O O O §aO O");
												}
												shuffle($this->main->arenas[$game]["waiting"]); # First Shuffle
											break;
											case 3:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->addTitle(TextFormat::BOLD. TextFormat::GREEN . "READY","§aO O O §cO O O §aO O O ");
												}
											break;
											case 2:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->addTitle(TextFormat::BOLD. TextFormat::GREEN . "READY", "§aO O O O §cO §aO O O O");
												}
												shuffle($this->main->arenas[$game]["waiting"]); # Second Shuffle
											break;
											
											case 1:
												foreach($levelArena->getPlayers() as $p)
												{
													$p->addTitle(TextFormat::BOLD. TextFormat::GREEN . "HIDE", "§aO O O O O O O O O");
												}
												foreach($this->main->arenas[$game]["waiting"] as $n)
												{
													$pObj = Server::getInstance()->getPlayer($n);
													if(count($this->main->arenas[$game]["waiting"]) > 1)
													{
														$pObj->sendMessage($this->main->prefix. TextFormat::GREEN . " > You are a Hider!");
														unset($this->main->arenas[$game]["waiting"][ $pObj->getName() ]);
														$this->main->summon($pObj, $game, "hider");
													} else {
														$pObj->sendMessage($this->main->prefix. TextFormat::RED . " > You are the first Seeker!");
														unset($this->main->arenas[$game]["waiting"][ $pObj->getName() ]);
														$this->main->summon($pObj, $game, "seeker");
													}
													$pObj->setNameTagVisible(false); # Should Hide the name
												}
												
												# -- Change Game Phase -- #
												$this->main->running[$game]["phase"] = "HIDE";
											break;
										}

										foreach($levelArena->getPlayers() as $p)
										{
											$p->sendTip("§l§7[ §f". $arena["wait-time"] ." seconds to start §7]");
										}

										$this->main->running[$game]["wait-time"] -= 1;
									}
								break;
								case "HIDE":
									if($arena["hide-time"] > 0)
									{
										if($arena["hide-time"] == 1)
										{
											# -- Change Game Phase -- #
											$this->main->running[$game]["phase"] = "PLAY";
										} else {
											switch($arena["hide-time"])
											{
												case 3: case 2: case 1:
													foreach($levelArena->getPlayers() as $p) {
														$p->addTitle(TextFormat::BOLD . TextFormat::GREEN . $arena["hide-time"], "Seconds left to hide");
													}
												break;
												default:
													foreach($levelArena->getPlayers() as $p) {
														$p->sendTip(TextFormat::BOLD . TextFormat::YELLOW . $arena["hide-time"] . "s to hide. Quick!");
													}
											}
										}
										$this->main->running[$game]["hide-time"] -= 1;
									}
								break;
								case "PLAY":
										$time = $arena["play-time"];
										$mins = floor($time / 60 % 60);
										$secs = ($s = floor($time % 60)) < 10 ? "0" . $s : $s;
										if($playercount >= $minplayer) #($playercount >= 1) # ($playercount >= $minplayer)
										{
											$hcount = count($this->main->arenas[$game]["hiders"]);
											$scount = count($this->main->arenas[$game]["seekers"]);
											if($hcount == 0 && $scount >= 1)
											{
												$this->main->concludeGame($game, "S", $this->main->arenas[$game]["seekers"]);
												if($this->main->config->get("auto-rejoin"))
												{
													$this->main->initGame($game);
													foreach($levelArena->getPlayers() as $pl)
													{
														$this->main->summon($pl, $game, "waiting");
													}
												} else {
													foreach($levelArena->getPlayers() as $pl)
													{
														$this->main->leaveArena($pl, $game, true);
													}
												}
											}
											if($scount == 0 && $hcount >= 1)
											{
												$this->main->concludeGame($game, "H", $this->main->arenas[$game]["hiders"]);
												if($this->main->config->get("auto-rejoin"))
												{
													$this->main->initGame($game);
													foreach($levelArena->getPlayers() as $pl)
													{
														$this->main->summon($pl, $game, "waiting");
													}
												} else {
													foreach($levelArena->getPlayers() as $pl)
													{
														$this->main->leaveArena($pl, $game, true);
													}
												}
											}
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
													$this->main->concludeGame($arena);
													$spawn = $this->main->getServer()->getDefaultLevel()->getSafeSpawn();
													$this->main->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
													foreach($levelArena->getPlayers() as $pl)
													{
														$pl->addTitle("§lGame Over","§cYou have played on: §a" . $game);
														$this->main->leaveArena($pl, $game, true);
													}
												} else {
													foreach($levelArena->getPlayers() as $pla)
													{
														$pla->sendTip("§l§fSeekers: " . $scount . " : Hiders: " . $hcount);
														$pla->sendPopup("§l§7Remaing time: §b".$mins. "§f : §b" .$secs);
													}
												}
											}
										} else {
											foreach($levelArena->getPlayers() as $pl)
											{
												$pl->addTitle("§lGame Over","§cToo few players to continue" . $game);
												$this->main->leaveArena( $pl , $game, true);
											}
										}
										$this->main->running[$game]["play-time"] -= 1;
								break;
							}
						} else {
							foreach($levelArena->getPlayers() as $pl)
							{
								$pl->sendTip("§f>> §8/quithns - to leave the game §f<<");
								$pl->sendPopup("§l§bRequires {$minplayer} or more players to start!");
							}
						}
					}
				}
			}
		}
	}
}
