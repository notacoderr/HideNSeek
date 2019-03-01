<?php

namespace Codeh\HnS;

use pocketmine\Server;

use pocketmine\event\Listener;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;

use pocketmine\item\Item;
use pocketmine\utils\TextFormat as TF;

class GameEars implements Listener{
    
    private $main;

    public function __construct(\Codeh\HnS\HnS $core)
    {
		  $this->main = $core;
    }
	
    public function onChat(PlayerChatEvent $event) {
		if(array_key_exists($event->getPlayer()->getName(), $this->main->gameMaker) == false) return;
		$player = $event->getPlayer();
		$game = $this->main->gameMaker[$event->getPlayer()->getName()];
		$chat = explode(" ", $event->getMessage());
		$cmd = strtolower($chat[0]);
		switch($cmd) {
			case 'world':
				$this->main->cacheGame($game, $cmd, (empty($chat[1]) ? $player->getLevel()->getName() : $chat[1]), $player);
			break;
			case 'lobbyspawn':
				$this->main->cacheGame($game, $cmd, [$player->getX(), $player->getY(), $player->getZ()], $player);
			break;
			case 'hiderspawn':
				$this->main->cacheGame($game, $cmd, [$player->getX(), $player->getY(), $player->getZ()], $player);
			break;
			case 'seekerspawn':
				$this->main->cacheGame($game, $cmd, [$player->getX(), $player->getY(), $player->getZ()], $player);
			break;
			case 'minplayer':
				$this->main->cacheGame($game, $cmd, (empty($chat[1]) ? 2 : intval($chat[1])), $player);
			break;
			case 'maxplayer':
				$this->main->cacheGame($game, $cmd, (empty($chat[1]) ? 6 : intval($chat[1])), $player);
			break;
			case 'done':
			#var_dump($this->main->newArena[$game]);
				$a = $this->main->arenadata;
				$n = $this->main->newArena;
				$init = $this->main->arenas;
				if($this->main->gameIsReady($game)) {
					$a->saveGame($game, $n[$game]["world"]);
					$a->setLobbySpawn($game, $n[$game]["lobbyspawn"]);
					$a->setHiderSpawn($game, $n[$game]["hiderspawn"]);
					$a->setSeekerSpawn($game, $n[$game]["seekerspawn"]);
					$a->setMin($game, $n[$game]["minplayer"]);
					$a->setMax($game, $n[$game]["maxplayer"]);
					$player->sendMessage(TF::BOLD . $this->main->prefix .TF::RESET . " > Game data has been saved, please use /hns setsign [game].");
					
					$this->main->initGame($game);
					unset( $this->main->gameMaker[ $event->getPlayer()->getName() ] ); #remove from the cache
				} else {
					$player->sendMessage(TF::BOLD . $this->main->prefix .TF::RESET . " > Game data isn't complete yet:" . TF::EOL . TF::RESET .
					TF::WHITE."Arena: ". $game . TF::EOL .
					TF::WHITE."World: ". (array_key_exists("world", $n[$game]) ? TF::GREEN . $n[$game]["world"] : TF::RED . "missing") . TF::EOL .
					TF::WHITE."Lobby Spawn: ". (array_key_exists("lobbyspawn", $n[$game]) ? TF::GREEN."set" : TF::RED . "missing") . TF::EOL .
					TF::WHITE."Hider Spawn: ". (array_key_exists("hiderspawn", $n[$game]) ? TF::GREEN."set" : TF::RED . "missing") . TF::EOL .
					TF::WHITE."Seeker Spawn: ". (array_key_exists("seekerspawn", $n[$game]) ? TF::GREEN."set" : TF::RED . "missing") . TF::EOL .
					TF::WHITE."Min players: ". (array_key_exists("minplayer", $n[$game]) ? TF::GREEN."set" : TF::RED . "missing") . TF::EOL .
					TF::WHITE."Max players: " . (array_key_exists("maxplayer", $n[$game]) ? TF::GREEN. "set" : TF::RED . "missing")
					);
				}
			break;
			
			default:
			$player->sendMessage(TF::BOLD . $this->main->prefix . " •> " .TF::RESET . TF::EOL . 
					 "<--------------------------------->" . TF::EOL . 
				     TF::YELLOW . "You cannot chat, but you can use commands." . TF::EOL .
					 TF::YELLOW . "the commands below will only work without /" . TF::EOL . TF::RESET .
					 "world [name]- set where the game world is" . TF::EOL .
					 "lobbyspawn - set where the lobby is" . TF::EOL .
				     "seekerspawn - set where the seeker will spawn" . TF::EOL .
				     "hiderspawn - set where the hiders will spawn". TF::EOL .
				     "minplayer - min player to start the game(must be > 1)". TF::EOL .
				     "maxplayer - max player allowed in the game" . TF::EOL .
					 "done - will save the game data" . TF::EOL .
					 "<--------------------------------->"
				    );
		}
		$event->setCancelled();
	}
	
	public function onBlockBreak(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		if(array_key_exists($player->getName(), $this->main->settingSign))
		{
			$block = $event->getBlock();
			$tile = $player->getLevel()->getTile($block);
			if($tile instanceof \pocketmine\tile\Sign)
			{
				$game = $this->main->settingSign[ $player->getName() ];
				$tile->setText(
					TF::BOLD . TF::RED . $this->main->prefix,
					TF::BOLD . TF::AQUA . $game,
					TF::YELLOW  . $this->main->arenadata->getPlayerCounts($game) . " / " . $this->main->arenadata->getMax($game),
					TF::GREEN . "Waiting"
				);
				unset( $this->main->settingSign[ $player->getName() ] );
				$player->sendMessage($this->main->prefix . " > " . " Sign registered!");
			}
			$event->setCancelled();
		}
	}
	
	public function onInteract(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$tile = $player->getLevel()->getTile($block);
		if($tile instanceof \pocketmine\tile\Sign) 
		{
			$text = $tile->getText();
			if(TF::clean($text[0]) == $this->main->prefix)
			{
				if(array_key_exists(TF::clean($text[1]), $this->main->arenas))
				{
					if(strpos(TF::clean($text[3]), 'Waiting') !== false and TF::clean($text[2]) !== "F U L L")
					{
						if(array_key_exists($player->getName(), $this->main->gameSession) == false)
						{
							$this->main->summon($player, TF::clean($text[1]));
							return;
						} else {
							$player->sendMessage($this->main->prefix . " > You are already in a game...");
						}
					} else {
						$player->sendMessage($this->main->prefix . " > Please try to join later...");
						return;
					}
				}
			}
		}
	}
	
	/*
	public function onJoin(PlayerJoinEvent $event) : void
	{
		$player = $event->getPlayer();
		if(in_array($player->getLevel()->getFolderName(), $this->rbharenas))
		{
			$this->leaveArena($player);
		}
	}
	
	public function onQuit(PlayerQuitEvent $event) : void
	{
		$player = $event->getPlayer();
		if(in_array($player->getLevel()->getFolderName(), $this->rbharenas))
		{
			$this->leaveArena($player);
		}
	}

	public function onBlockBreak(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName(); 
		if(in_array($level, $this->rbharenas))
		{
			$event->setCancelled();
		}
	}

	public function onBlockPlace(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName(); 
		if(in_array($level, $this->rbharenas))
		{
			$event->setCancelled();
		}
	}
	
	public function onDamage(EntityDamageEvent $event)
	{
		if($event instanceof EntityDamageByEntityEvent)
		{
			if($event->getEntity() instanceof Player && $event->getDamager() instanceof Player)
			{
				$a = $event->getEntity()->getName(); $b = $event->getDamager()->getName();
				if(array_key_exists($a, $this->iswaitingrbh) || array_key_exists($b, $this->iswaitingrbh))
				{
					$event->setCancelled();
					return true;
				}
				if(in_array($a, $this->isplayingrbh) && in_array($event->getEntity()->getLevel()->getFolderName(), $this->rbharenas))
				{
					$event->setCancelled(false); //for other plugin's cancelling damage event

					if($event->getCause() == 2)
					{
						$event->setDamage(0.0); //hack, to remove damage on projectile hit entity event
					}

					if($event->getDamage() >= $event->getEntity()->getHealth())
					{
						$event->setDamage(0.0); //hack, to avoid players from getting killed
						$event->setCancelled();
						$inv = $event->getDamager()->getInventory();
						if(!$inv->contains( Item::get(Item::ARROW) ))
						{
							$inv->addItem( $this->getArrow() );
						}
						$this->addKill($event->getDamager()->getName()); $this->notifyPlayer($event->getDamager(), 1);
						$this->addDeath($event->getEntity()->getName()); $this->notifyPlayer($event->getEntity(), 2);
						$this->randSpawn($event->getEntity(), $event->getEntity()->getLevel()->getFolderName());
					}
				}	
				return true;
			}
		} else {
			$a = $event->getEntity()->getName();
			if(in_array($a, $this->isplayingrbh) || array_key_exists($a, $this->iswaitingrbh))
			{
				return $event->setCancelled();
			}
		}
	}
	
	public function onTeleport(EntityLevelChangeEvent $event)
	{
		if ($event->getEntity() instanceof Player) 
		{
			$player = $event->getEntity();
			$from = $event->getOrigin()->getFolderName();
			$to = $event->getTarget()->getFolderName();
			if($this->arena == $from && $this->arena != $to)
			{
				$event->getEntity()->setGameMode(2);
				$this->leaveArena($player);
				$this->cleanPlayer($player);
				return true;
			}
			if($this->arena == $to)
			{
				switch($this->gameState)
				{
					case 1:
						$this->summon($player, "waiting");
					break;
					
					case 2:
						$player->sendMessage($this->prefix . $this->config->getNested("messages.game-running"));
						return $event->setCancelled();
					break;
					
				}
			}
		}
	}
	
	public function testingCommands(PlayerCommandPreprocessEvent $event)
	{
		if($event->getPlayer()->isLoggedIn == false)
		{
				$command = explode(" ", $event->getMessage());
				if($command[0] == "/login" || $command[0] == "/register") return;
				$event->setCancelled();
				$event->getPlayer()->sendMessage($this->main->config->getNested("otherMessages.notLoggedIn"));
		}
	}*/
}

?>
