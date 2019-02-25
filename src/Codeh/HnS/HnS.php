<?php
namespace Codeh\HnS;

use pocketmine\{Server, Player};

use pocketmine\plugin\PluginBase;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

use pocketmine\item\Item;
use pocketmine\level\Level;

class HnS extends PluginBase
{

	public $prefix, $economy, $config, $arena;
	public $gameState = 1; //0 idle, 1 waiting, 2 playing
	//public $gameMaker = null;
	public $playTime;
	public $hideTime;
	public $waitTime; 
	public $playing = [];
	public $seeker = [], $hider = [];
	public $commands = [];
	public $minp, $maxp;
	public $pcounts = 0; //i dont want milliseconds wasted on count()
	//public $newArena = [];
	
	public function onLoad()
	{
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		//$this->config->save();
		$this->prefix = $this->config->get("prefix");
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
		
		$this->arena = $this->config->getNested("arena.world");

		if(!$this->getServer()->getLevelByName($this->arena) instanceof Level)
		{
			if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $this->arena))
			{
				try {
					$this->getServer()->loadLevel($this->arena);
					
					$this->playTime = $this->config->getNested("game.time-to-play");
					$this->hideTime = $this->config->getNested("game.time-to-hide");
					$this->waitTime = $this->config->getNested("game.time-to-wait");
					$this->maxp = $this->config->getNested("game.max-players");
					$this->minp = $this->config->getNested("game.min-players");

					$this->getServer()->getPluginManager()->registerEvents(new GameEars($this), $this);
					$this->getServer()->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
					$this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 20);
				} catch() {
				}
			} else {
				$this->getLogger()->critical($this->prefix . " ARENA: " . $this->arena . " SEEMS TO BE MISSING");		
				$this->getServer()->getPluginManager()->disablePlugin($this);
				return;
			}
		}
	}
	
	public function onEnable()
	{
		$this->getLogger()->info($this->prefix . " E N A B L E D");	
	}
	
	public function onDisable()
	{
		$this->getLogger()->info($this->prefix . " D I S A B L E D");	
	}
	
	private function notifyPlayer(Player $player, int $type): void
	{
		switch($type)
		{
			case 1:
				$player->addTitle("", "§a+1 Kill");
			break;

			case 2:
				$player->addTitle("", "§c+1 Death");
			break;
		}
	}
	
	/*private function prepareMaker($player , $newArena) : void
	{
		$this->gameMaker = $player->getName();
		$player->sendMessage(TextFormat::BOLD . $this->prefix . TextFormat::EOL . 
				     "You cannot chat, please input commands without /" . TextFormat::EOL .
				     "seekerspawn - set where the seeker will spawn" . TextFormat::EOL .
				     "hiderspawn - set where the hiders will spawn". TextFormat::EOL .
				     "minplayer - min player to start the game(must be > 1)". TextFormat::EOL .
				     "maxplayer - max player allowed in the game" . TextFormat::EOL
				    );
		$player->setGamemode(1);
		$player->teleport($this->getServer()->getLevelByName($newArena)->getSafeSpawn() , 0, 0);
	}*/

	public function onCommand(CommandSender $player, Command $cmd, $label, array $args) : bool
	{
		if($player instanceof Player)
		{
			switch($cmd->getName())
			{
				/*case "hns":
					if(!empty($args[0]))
					{
						if($args[0]=='make' or $args[0]=='create')
						{
							if($player->isOp())
							{
									if(!empty($args[1]))
									{
										if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1]))
										{
											$this->getServer()->loadLevel($args[1]);
											$this->getServer()->getLevelByName($args[1])->loadChunk($this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorX(), $this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorZ());
											$this->newArena["arena"]["world"] = $args[1];
											var_dump($this->newArena);
											$this->prepareMaker($player, $args[1]);
											return true;
										} else {
											$player->sendMessage($this->prefix . " •> ERROR missing world.");
											return true;
										}
									}
									else
									{
										$player->sendMessage($this->prefix . $this->config->getNested("messages.wrongUsage"));
										return true;
									}
							} else {
								$player->sendMessage($this->prefix . $this->config->getNested("messages.notOp"));
								return true;
							}
						}
						else if($args[0] == "leave" or $args[0]=="quit" )
						{
							if(in_array($player->getName(), $this->playing))
							{
								$this->leaveArena($player); 
								return true;
							}
						} else {
							$player->sendMessage($this->prefix . $this->config->getNested("messages.notInGame"));
							return true;
						}
					} else {
						$player->sendMessage($this->prefix . " •> " . "/hns <make-leave> : Create Arena | Leave the game");
						$player->sendMessage($this->prefix . " •> " . "/hnsstart : Start the game in 10 seconds");
					}
				break;*/
				case "quithns":
					if(in_array($player->getName(), $this->playing))
					{
						$this->leaveArena($player);
					}
				break;
					
				case "joinhns":
					if(in_array($player->getName(), $this->playing) == false)
					{
						$this->summon($player, "waiting");
					}
				break;
					
				case "starthns": //Force the game to start immediately
					if($player->isOp() and $this->waitTime > 11)
					{
						$player->sendMessage($this->prefix . " •> " . "§aStarting in 10 seconds...");
						$this->waitTime = 11; //needs to be 11 bc of how ticking works
					}
				break;
				default:
					return true;
			}
			return true;
		} 
	}

	public function announceWinner(String $arena, $name = null)
	{
		if(is_null($name))
		{
			$levelArena = $this->getServer()->getLevelByName($arena);
			$plrs = $levelArena->getPlayers();
			arsort($this->kills);
			foreach($this->kills as $pln => $k)
			{
				if($this->getServer()->getPlayer($pln)->getLevel()->getFolderName() == $arena)
				{
					$this->api->addMoney($pln , mt_rand(390, 408));
					$this->givePrize( $this->getServer()->getPlayer($pln) );
					foreach($this->getServer()->getOnlinePlayers() as $ppl)
					{
						$ppl->sendMessage($this->prefix . " • §l§b".$pln."§f won in ".$arena.", with §b".$k." §fkills");
					}
					return true; //stops at first highest player
				}
			}
		} else {
			foreach($this->getServer()->getOnlinePlayers() as $ppl)
			{
				$ppl->sendMessage($this->prefix . " • §l§b".$name."§f won in ".$arena);
			}
			return true;
		}
	}
	
	public function setSeeker(string $name) : void
	{
		$this->seeker[] = $name;
	}
	
	public function setHider(string $name) : void
	{
		$this->hider[] = $name;
	}
	
	public function leaveArena(Player $player) : void
	{
		$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
		$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
		$player->teleport($spawn , 0, 0);		
		//$player->setGameMode(2);
		$player->setFood(20);
		$player->setHealth(20);
		$this->removefromgame($player->getName());
		$this->cleanPlayer($player);
		$this->pcount -= 1;
	}
	
	public function removefromgame(string $playername)
	{
		if (in_array($playername, $this->playing)){
			unset($this->waiting[ $playername ]);
		}
		if (in_array($playername, $this->seeker)){
			unset($this->seeker[ $playername ]);
		}
		if (in_array($playername, $this->hider)){
			unset($this->hider[ $playername ]);
		}
		
		$this->cleanPlayer($this->getServer()->getPlayer($playername));
	}
	
	private function cleanPlayer($player) : void
	{
		$player->getInventory()->clearAll();
		$player->getCursorInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->setNameTag( $this->getServer()->getPluginManager()->getPlugin('PureChat')->getNametag($player) );
	}

	private function summon($player, string $type)
	{
		$level = $this->getServer()->getLevelByName( $this->arena );
		
		switch($type)
		{
			case "waiting":
				$thespawn = $this->config->getNested("game.waiting");
				array_push($this->playing, $player->getName());
				$this->pcount += 1;
			break;
				
			case "hider":
				$thespawn = $this->config->getNested("game.hider");
			break;
				
			case "seeker":
				$thespawn = $this->config->getNested("game.seeker");
			break;
		}
		
		$spawn = new Position($thespawn["x"] + 0.5 , $thespawn["y"] , $thespawn["z"] + 0.5 , $level);
		$player->teleport($spawn, 0, 0);
		$player->setFood(20);
		$player->setHealth(20);
	}

	/*public function onInteract(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$tile = $player->getLevel()->getTile($block);
		if($tile instanceof Sign) 
		{
			if($this->mode == 26 )
			{
				$tile->setText(TextFormat::AQUA . "[Join]", TextFormat::YELLOW  . "0 / 12", "§f".$this->currentLevel, $this->prefix);
				$this->refreshrbharenas();
				$this->currentLevel = "";
				$this->mode = 0;
				$player->sendMessage($this->prefix . " •> " . "Arena Registered!");
			} else {
				$text = $tile->getText();
				if($text[3] == $this->prefix)
				{
					if($text[0] == TextFormat::AQUA . "[Join]")
					{
						$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
						$namemap = str_replace("§f", "", $text[2]);

						$this->iswaitingrbh[ $player->getName() ] = $namemap;//beta, set to waiting to be able to tp
						$this->kills[ $player->getName() ] = 0; //create kill points
						$this->deaths[ $player->getName() ] = 0; //create death points

						$level = $this->getServer()->getLevelByName($namemap);
						$thespawn = $config->get($namemap . "Lobby");
						$spawn = new Position($thespawn[0]+0.5 , $thespawn[1] ,$thespawn[2]+0.5 ,$level);
						$level->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());

						$player->teleport($spawn, 0, 0);
						$player->getInventory()->clearAll();
						$player->removeAllEffects();
						$player->setHealth(20);
						$player->setGameMode(2);

						return true;
					} else {
						$player->sendMessage($this->prefix . " •> " . "Please try to join later...");
						return true;
					}
				}
			}
		}
		if($this->mode >= 1 && $this->mode <= 12 )
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->currentLevel . "Spawn" . $this->mode, array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage($this->prefix . " •> " . "Spawn " . $this->mode . " has been registered!");
			$this->mode++;
			if($this->mode == 13)
			{
				$player->sendMessage($this->prefix . " •> " . "Tap to set the lobby spawn");
			}
			$config->save();
			return true;
		}
		if($this->mode == 13)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->currentLevel . "Lobby", array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage($this->prefix . " •> " . "Lobby has been registered!");
			$this->mode++;
			if($this->mode == 14)
			{
				$player->sendMessage($this->prefix . " •> " . "Tap anywhere to continue");
			}
			$config->save();
			return true;
		}

		if($this->mode == 14)
		{
			$level = $this->getServer()->getLevelByName($this->currentLevel);
			$level->setSpawn = (new Vector3($block->getX(),$block->getY()+2,$block->getZ()));
			$player->sendMessage($this->prefix . " •> " . "Touch a sign to register Arena!");
			$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
			$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
			$player->teleport($spawn,0,0);

			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set("rbharenas", $this->rbharenas);
			$config->save();
			$this->mode=26;
			return true;
		}
	}*/

	
	public function refreshGame() : void
	{
		$this->waitTime;
		$this->hideTime;
		$this->playTime;
		$this->hider = [];
		$this->seeker = [];
	}
	
	public function givePrize(Player $player) : void
	{

	}
	
}
