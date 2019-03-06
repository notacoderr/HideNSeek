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
						if($arena["reset-time"] <> 10) $this->main->initGame($game);
					} else {
						if($playercount >= $minplayer)
						{
							switch($phase)
							{
								case "WAIT":
									$time = $arena["wait-time"];
									if($time >= 1)
									{
										switch($time)
										{
											case 10:
												$this->main->getServer()->broadcastMessage($this->main->prefix . " > " . $this->main->config->getNested("messages.game-start-soon"), $levelArena->getPlayers());
											break;
											
											case 9: case 8: break;
											
											case 7:
												$this->main->getServer()->broadcastTitle("§eREADY", "§cO O O O O O O O O", -1, 15, -1, $levelArena->getPlayers());
												$this->main->playSound($levelArena->getPlayers());
											break;
											
											case 6:
												$this->main->getServer()->broadcastTitle("§eREADY", "§aO §cO O O O O O O §aO", -1, 15, -1, $levelArena->getPlayers());
												$this->main->playSound($levelArena->getPlayers());
											break;
											
											case 5:
												$this->main->getServer()->broadcastTitle("§eREADY", "§aO O §cO O O O O §aO O", -1, 15, -1, $levelArena->getPlayers());
												$this->main->playSound($levelArena->getPlayers());
											break;
											
											case 4:
												$this->main->getServer()->broadcastTitle("§6READY", "§aO O O §cO O O §aO O O ", -1, 15, -1, $levelArena->getPlayers());
												$this->main->playSound($levelArena->getPlayers());
											break;
											
											case 3:
												$this->main->getServer()->broadcastTitle("§6READY", "§aO O O O §cO §aO O O O", -1, 15, -1, $levelArena->getPlayers());
												$this->main->playSound($levelArena->getPlayers());
											break;
											
											case 2:
												$this->main->getServer()->broadcastTitle("§6READY", "§aO O O O O O O O O", -1, 15, -1, $levelArena->getPlayers());
												$this->main->teams->shuffleTeams($game); # shuffles
												$this->main->playSound($levelArena->getPlayers());
											break;
											
											case 1:
												$this->main->getServer()->broadcastTitle("§aH I D E", "§7" .$arena["hide-time"]. "s to Hide", -1, 10, -1, $levelArena->getPlayers());
												# -- Assigns a seeker and the rest as hiders -- #
												$this->main->teams->assignPlayers($game);
												$this->main->playSound($levelArena->getPlayers(), 3);
											break;
											default:
											$this->main->getServer()->broadcastTip("§7[ §f{$time} seconds to start §7]", $levelArena->getPlayers());
										}
										$this->main->running[$game]["wait-time"] -= 1;
									} else {
										# -- Change Game Phase -- #
										$this->main->running[$game]["phase"] = "HIDE";
									}
								break;
								case "HIDE":
									$time = $arena["hide-time"];
									if($time >= 1)
									{
										switch($time)
										{
											case 5: case 4: case 3: case 2: case 1:
												$this->main->getServer()->broadcastTitle($time, "§cseconds left to hide", -1, 15, -1, $levelArena->getPlayers());
												$s = ($time == 1) ? 2 : 4;
												$this->main->playSound($levelArena->getPlayers(), $s);
											break;
											default:
												$this->main->getServer()->broadcastTip("§7Releasing the Seeker in:§f {$time}s", $levelArena->getPlayers());
										}
										$this->main->running[$game]["hide-time"] -= 1;
									} else {
										$this->main->getServer()->broadcastTitle("§bGood luck!", "-=-=-=-=-=-=-=-=-=-", -1, 10, -1, $levelArena->getPlayers());
										$this->main->playSound($levelArena->getPlayers(), $s, 3);
										# -- Change Game Phase -- #
										$this->main->running[$game]["phase"] = "PLAY";
									}
								break;
								case "PLAY":
										$time = $arena["play-time"];
										if($time >= 1)
										{
											$mins = floor($time / 60 % 60);
											$secs = ($s = floor($time % 60)) < 10 ? "0" . $s : $s;
											if($playercount >= $minplayer)
											{
												switch($time)
												{
													case 179:
														$this->main->getServer()->broadcastTitle("§7Countdown", "§b".$mins. "§f:§b" .$secs. "§f remaining", -1, 15, -1, $levelArena->getPlayers());
													break;
													
													default:
													$hcount = $this->main->teams->countTeam($game, "hider");
													$scount = $this->main->teams->countTeam($game, "seeker");
													switch(true) {
														case ($hcount == 0 && $scount >= 1) :
															
															foreach($this->main->teams->game[$game] as $name => $team) {
																if($team == "seeker") $this->main->givePrize($name, $team);
															}
															$this->main->announceWinner($game, "S");
															$this->main->running[$game]["phase"] = "RESET";
															$this->main->playSound($levelArena->getPlayers(), 1);
														break;
														
														case ($scount == 0 && $hcount >= 1) :
															foreach($this->main->teams->game[$game] as $name => $team) {
																if($team == "hider") $this->main->givePrize($name, $team);
															}
															$this->main->announceWinner($game, "H");
															$this->main->running[$game]["phase"] = "RESET";
															$this->main->playSound($levelArena->getPlayers(), 1);
														break;
														
														default:
														foreach($levelArena->getPlayers() as $pla)
														{
															$pla->sendTip("§l§fSeekers: " . $scount . " : Hiders: " . $hcount); # ima keep these rather than broadcastTip
															$pla->sendPopup("§l§7Remaing time: §b".$mins. "§f : §b" .$secs);  # bc separating the broadcast means getting the level players again
														}
													}
												}
												$this->main->running[$game]["play-time"] -= 1;
											} else {
												$this->main->getServer()->broadcastMessage($this->main->prefix . " > " . $this->main->config->getNested("messages.game-few-players"), $levelArena->getPlayers());
												$this->main->running[$game]["phase"] = "RESET";
											}
										} else {
											if($hcount >= 1) {
												foreach($this->main->teams->game[$game] as $name => $team) {
													if($team == "hider") $this->main->givePrize($name, $team);
												}
												$this->main->announceWinner($game, "H");
												$this->main->running[$game]["phase"] = "RESET";
												$this->main->playSound($levelArena->getPlayers(), 1);
											}
										}
								break;
								
								case "RESET":
									$time = $arena["reset-time"];
									if($time >= 1) {
										$this->main->getServer()->broadcastTip("§fGame resets in §f {$time} seconds", $levelArena->getPlayers());
										$this->main->running[$game]["reset-time"] -= 1;
									} else {
										$this->main->playSound($levelArena->getPlayers(), 1);
										$this->main->initGame($game);
										if($this->main->config->get("auto-rejoin"))
										{
											foreach($levelArena->getPlayers() as $pl)
											{
												$this->main->joinGame($pl, $game);
											}
										} else {
											foreach($levelArena->getPlayers() as $pl)
											{
												$this->main->leaveArena($pl, $game, true);
											}
										}
									}
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
