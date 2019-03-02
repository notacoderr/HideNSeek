<?php
namespace Codeh\HnS;

use pocketmine\plugin\PluginBase;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

use pocketmine\item\Item;

use pocketmine\{Server, Player};
use pocketmine\level\{Level, Position};

class HnS extends PluginBase
{

	public $prefix, $economy, $config;
	#public $gameState = 1; //0 idle, 1 waiting, 2 playing
	public $gameMaker = [];
	public $settingSign = [];
	public $gameSession = [];
	public $playTime;
	public $hideTime;
	public $waitTime; 
	public $commands = [];
	public $playercounts = []; //i dont want milliseconds wasted on count()
	public $arenas = []; # loaded arenas
	public $running = []; # running or arenas in progress
	public $newArena = [];
	
	public function onLoad()
	{
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		$this->prefix = $this->config->getNested("game.prefix");
		$this->checkConfig();
	}
	
	private function checkConfig()
	{
		if(!$this->config->get("engine"))
		{
			$this->getServer()->getPluginManager()->disablePlugin($this); return;
		}
		
		if(class_exists(onebone\economyapi\EconomyAPI::class) and $this->config->getNested("reward.enable"))
		{
			$this->economy = EconomyAPI::getInstance();
			foreach($this->config->getNested("reward.commands") as $c) { $this->commands[] = $c; }
		}

		$this->playTime = $this->config->getNested("game.time-to-play");
		$this->hideTime = $this->config->getNested("game.time-to-hide");
		$this->waitTime = $this->config->getNested("game.time-to-wait");

		$this->arenadata = new ArenaData($this);
		$this->arenadata->init();
		
		foreach($this->arenadata->getAllGames() as $g => $w) {
			if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $w)) {
				if(!$this->getServer()->isLevelLoaded($w)) $this->getServer()->loadLevel($w);
				$this->initGame($g);
				$this->getLogger()->info($this->prefix . TextFormat::GREEN . " > Game: " . $g . " has been loaded");
			} else {
				$this->getLogger()->info($this->prefix . TextFormat::RED . " > World: " . $w . " cannot be loaded, please check it");
			}
		}
	}
	
	public function onEnable()
	{
		$this->getLogger()->info($this->prefix . " E N A B L E D");
		$this->getServer()->getPluginManager()->registerEvents(new GameEars($this), $this);
		$this->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 20);
	}
	
	public function onDisable()
	{
		$this->getLogger()->info($this->prefix . " D I S A B L E D");
	}
	
	
	private function prepareMaker($player, $game) : void {
		$this->gameMaker[ $player->getName() ] = $game;
		$player->sendMessage(TextFormat::BOLD . $this->prefix . TextFormat::RESET . " > " . TextFormat::EOL . 
					 "<--------------------------------->" . TextFormat::EOL . 
				     TextFormat::YELLOW . "You cannot chat, but you can use commands." . TextFormat::EOL .
					 TextFormat::YELLOW . "the commands below will only work without /" . TextFormat::EOL . TextFormat::RESET .
					 "world - set your current world as the game world" . TextFormat::EOL .
					 "lobbyspawn - set where the lobby is" . TextFormat::EOL .
				     "seekerspawn - set where the seeker will spawn" . TextFormat::EOL .
				     "hiderspawn - set where the hiders will spawn". TextFormat::EOL .
				     "minplayer - min player to start the game(must be > 1)". TextFormat::EOL .
				     "maxplayer - max player allowed in the game" . TextFormat::EOL .
					 "done - will save the game data" . TextFormat::EOL .
					 "<--------------------------------->" . TextFormat::EOL
				    );
	}
	
	public function gameIsReady(string $game) : bool {
		return count($this->newArena[$game]) == 6;
	}
	
	public function cacheGame(string $game, string $type, $data, $player) : void {
		switch ($type) {
			case "world":
				if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $data))
				{
					if($this->arenadata->worldExists($data))
					{
						$player->sendMessage($this->prefix . " > Sorry! {$data} is already registered with other arena");
					} else {
						$this->newArena[$game]["world"] = $data;
						$player->sendMessage($this->prefix . " > world has been saved");
					}
				} else {
					$player->sendMessage($this->prefix . " > ERROR missing world.");
				}
			break;
			
			case "lobbyspawn":
				if(is_array($data))
				{
					$this->newArena[$game]["lobbyspawn"]["x"] = $data[0];
					$this->newArena[$game]["lobbyspawn"]["y"] = $data[1];
					$this->newArena[$game]["lobbyspawn"]["z"] = $data[2];
					$player->sendMessage($this->prefix . " > position was saved");
				} else {
					$player->sendMessage($this->prefix . " > there was an error");
				}
			break;
			
			case "hiderspawn":
				if(is_array($data))
				{
					$this->newArena[$game]["hiderspawn"]["x"] = $data[0];
					$this->newArena[$game]["hiderspawn"]["y"] = $data[1];
					$this->newArena[$game]["hiderspawn"]["z"] = $data[2];
					$player->sendMessage($this->prefix . " > position was saved");
				} else {
					$player->sendMessage($this->prefix . " > there was an error");
				}
			break;
			
			case "seekerspawn":
				if(is_array($data))
				{
					$this->newArena[$game]["seekerspawn"]["x"] = $data[0];
					$this->newArena[$game]["seekerspawn"]["y"] = $data[1];
					$this->newArena[$game]["seekerspawn"]["z"] = $data[2];
					$player->sendMessage($this->prefix . " > position was saved");
				} else {
					$player->sendMessage($this->prefix . " > there was an error");
				}
			break;
			
			case "minplayer":
				if(is_integer($data))
				{
					$this->newArena[$game]["minplayer"] = $data;
					$player->sendMessage($this->prefix . " > data was saved");
				} else {
					$player->sendMessage($this->prefix . " > there was an error");
				}
			break;
			
			case "maxplayer":
				if(is_integer($data))
				{
					$this->newArena[$game]["maxplayer"] = $data;
					$player->sendMessage($this->prefix . " > data was saved");
				} else {
					$player->sendMessage($this->prefix . " > there was an error");
				}
			break;
		}
	}
	
	public function initGame(string $game) : void {
		$this->playercounts[ $game ] = 0;
		
		$this->running[ $game ] = [
			"game" => $game,
			"phase" => "WAIT",
			"wait-time" => $this->waitTime,
			"hide-time" => $this->hideTime,
			"play-time" => $this->playTime
		];
		
		$this->arenas[ $game ] = [
			"waiting" => [],
			"seekers" => [],
			"hiders" => []
		];
	}

	public function onCommand(CommandSender $player, Command $cmd, $label, array $args) : bool
	{
		if($player instanceof Player)
		{
			switch($cmd->getName())
			{
				case "hns":
					if(!empty($args[0]))
					{
						if($player->isOp())
						{
							if($args[0]=='make' or $args[0]=='create')
							{
								
								if(!empty($args[1]))
								{
									$this->newArena[ $args[1] ] = []; #•>
									$player->sendMessage($this->prefix . " > A new game is ready to be set up, please use /hns setup {$args[1]}");
								}
								else
								{
									$player->sendMessage($this->prefix . $this->config->getNested("messages.wrongUsage"));
									return true;
								}
							}
							if($args[0] == "setup")
							{
								if(!empty($args[1]))
								{
									if(array_key_exists($args[1], $this->newArena))
									{
										$this->prepareMaker($player, $args[1]);
										return true;
									} else {
										$player->sendMessage($this->prefix . $this->config->getNested("messages.wrongUsage"));
										return false;
									}
								}
							}
							if($args[0] == "setsign")
							{
								if(!empty($args[1]))
								{
									if(array_key_exists($args[1], $this->arenas))
									{
										$this->settingSign[ $player->getName() ] = $args[1];
										$player->sendMessage($this->prefix . " > Break a sign to register it, you can register multiple signs");
										return true;
									} else {
										$player->sendMessage($this->prefix . $this->config->getNested("messages.wrongUsage"));
										return false;
									}
								}
							}
						} else {
							$player->sendMessage($this->prefix . $this->config->getNested("messages.notOp"));
							return true;
						}
					} else {
						$player->sendMessage($this->prefix . " > " . "/hns <make-leave> : Create Arena | Leave the game");
						$player->sendMessage($this->prefix . " > " . "/hnsstart : Start the game in 10 seconds");
					}
				break;
				
				case "quithns":
					if(array_key_exists($player->getName(), $this->gameSession))
					{
						$this->leaveArena($player, $this->gameSession[$player->getName()], true);
					} else {
						$player->sendMessage($this->prefix . " > You are not in a Hide N Seek game");
					}
				break;
					
				case "randomhns":
					if(array_key_exists($player->getName(), $this->gameSession) == false)
					{
						#$this->summon($player, "waiting");
						if(!empty($this->running))
						{
							foreach($this->running as $x)
							{
								if($x["phase"] == "WAIT")
								{
									$this->summon($player, $x["game"], "waiting");
									return true;
								}
							}
						} else {
							$player->sendMessage($this->prefix . " > There are no registered game");
						}
					}
					return false;
				break;
					
				case "starthns": //Force the game to start immediately
					if($player->isOp())
					{
						if(array_key_exists($player->getName(), $this->gameSession))
						{
							$session = $this->gameSession[ $player->getName() ];
							if(($time = $this->running[$session]["wait-time"]) > 11)
							{
								$player->sendMessage($this->prefix . " > §aStarting in 10 seconds...");
								$this->running[$session]["wait-time"] = 11; //needs to be 11 bc of how ticking works
							}
						} else {
							$player->sendMessage($this->prefix . " > You are not in a Hide N Seek game");
						}
					}
				break;
				default:
					return true;
			}
			return true;
		} 
	}

	public function concludeGame(String $arena, string $team, array $names)
	{
		$msg = $this->config->getNested("messages.teamWon");
		$msg = str_replace("{team}", $team == "S" ? "Seekers" : "Hiders", $msg);
		$msg = str_replace("{arena}", $arena, $msg);
		$this->getServer()->broadcastMessage($msg);
		foreach($names as $n)
		{
			if(($pObj = $this->getServer()->getPlayer($n)) instanceof Player)
			{
				$this->givePrize($pObj);
			}
		}
	}
	
	public function setWaiting(string $name, string $game) : void
	{
		array_push($this->arenas[$game]["waiting"], $name);
	}

	public function setSeeker(string $name, string $game) : void
	{
		array_push($this->arenas[$game]["seekers"], $name);
	}
	
	public function setHider(string $name, string $game) : void
	{
		array_push($this->arenas[$game]["hiders"], $name);
	}
	
	public function leaveArena(Player $player, string $game, bool $manual = false) : void
	{
		if($manual) {
			$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
			$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
			$player->teleport($spawn , 0, 0);
		}		

		$player->setFood(20);
		$player->setHealth(20);
		
		$this->removefromgame($player->getName(), $game);
	}
	
	public function removefromgame(string $playername, string $game)
	{
		if (in_array($playername, $this->arenas[$game]["waiting"])){
			unset($this->arenas[$game]["waiting"][ $playername ]);
		}
		if (in_array($playername, $this->arenas[$game]["seekers"])){
			unset($this->arenas[$game]["seekers"][ $playername ]);
		}
		if (in_array($playername, $this->arenas[$game]["hiders"])){
			unset($this->arenas[$game]["hiders"][ $playername ]);
		}
		if (array_key_exists($playername, $this->gameSession)){
			unset($this->gameSession[ $playername ]);
		}
		
		$this->playercounts[$game] -= 1;
		
		if(($pObj = $this->getServer()->getPlayer( $playername )->isOnline() ))
		{
			$this->cleanPlayer($this->getServer()->getPlayer( $playername ));
		}
	}
	
	private function cleanPlayer($player) : void
	{
		$player->getInventory()->clearAll();
		$player->getCursorInventory()->clearAll();
		$player->getArmorInventory()->clearAll();

		if(!is_null($pc = $this->getServer()->getPluginManager()->getPlugin('PureChat')))
		{
			$player->setNameTag( $pc->getNametag($player) );
		}

		$player->setNameTagVisible(); # should turn name back again
	}

	public function summon($player, string $game, string $type = "waiting")
	{
		$level = $this->getServer()->getLevelByName( $this->arenadata->getWorld($game) );
		
		switch($type)
		{
			case "waiting":
				$point = $this->arenadata->getLobbySpawn($game);
				$this->playercounts[ $game ] += 1;
				$this->setWaiting($player->getName(), $game);
				$this->gameSession[ $player->getName() ] = $game;
			break;
				
			case "hider":
				$point = $this->arenadata->getHiderSpawn($game);
				$this->setHider($player->getName(), $game);
			break;
				
			case "seeker":
				$point = $this->arenadata->getSeekerSpawn($game);
				$this->setSeeker($player->getName(), $game);
			break;
		}
		$target = new Position($point[0] + 0.5 , $point[1] , $point[2] + 0.5 , $level);
		$player->teleport($target, 0, 0);
		$player->setFood(20);
		$player->setHealth(20);
	}
	
	public function givePrize(Player $player) : void
	{

	}
	
}
