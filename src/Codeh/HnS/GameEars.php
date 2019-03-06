<?php

namespace Codeh\HnS;

use pocketmine\Server;
use pocketmine\Player;

use pocketmine\event\Listener;

#use pocketmine\event\player\PlayerJoinEvent;
#use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

use pocketmine\event\block\BlockBreakEvent;
#use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\event\entity\EntityDamageEvent;
#use pocketmine\event\entity\EntityDamageByEntityEvent;
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
				if($this->main->gameIsReady($game)) {
					$a->saveGame($game, $n[$game]["world"]);
					$a->setLobbySpawn($game, $n[$game]["lobbyspawn"]);
					$a->setHiderSpawn($game, $n[$game]["hiderspawn"]);
					$a->setSeekerSpawn($game, $n[$game]["seekerspawn"]);
					$a->setMin($game, $n[$game]["minplayer"]);
					$a->setMax($game, $n[$game]["maxplayer"]);
					$player->sendMessage(TF::BOLD . $this->main->prefix .TF::RESET . " > Game data has been saved, please use /hns setsign {$game}.");
					
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
					 "world - set your current world as the game world" . TF::EOL .
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
					TF::YELLOW  . $this->main->playercounts[$game] . " / " . $this->main->arenadata->getMax($game),
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
				if($this->main->arenadata->gameExists( TF::clean($text[1]) ))
				{
					if(strpos(TF::clean($text[3]), 'Waiting') !== false and TF::clean($text[2]) !== "F U L L")
					{
						if(array_key_exists($player->getName(), $this->main->gameSession) == false)
						{
							$this->main->joinGame($player, TF::clean($text[1]));
							return;
						} else {
							$player->sendMessage($this->main->prefix . " > You are already in a game...");
						}
					} else {
						$player->sendMessage($this->main->prefix . " > Please try to join later...");
						return;
					}
				} else {
					$player->sendMessage($this->main->prefix . " > Game is missing...");
					return;
				}
			}
		}
	}

	/*public function onJoin(PlayerJoinEvent $event) : void
	{
		$name = $event->getPlayer()->getName();
		if(array_key_exists($name, $this->main->gameSession))
		{
			$this->main->removefromgame($name, $this->main->gameSession[$name]);
		}
	}
	*/

	public function onExhaust(PlayerExhaustEvent $event)
	{
		if(array_key_exists($event->getPlayer()->getName(), $this->main->gameSession))
		{
			$event->setCancelled();
		}
	}
	
	public function onDamage(EntityDamageEvent $event) 
	{
		if($event->getEntity() instanceof Player)
		{
			switch((int) $event->getCause()) {
				case 1:
					$seeker = $event->getDamager();
					$hider = $event->getEntity();
					
					if(($seeker instanceof Player) == false) return;
					#if(($hider instanceof Player) == false) return;

						$seekerName = $seeker->getName();
						$hiderName = $hider->getName();
						
					if(array_key_exists($seekerName, $this->main->gameSession) and array_key_exists($hiderName, $this->main->gameSession))
					{
						$game = $this->main->gameSession[$seekerName];
						if($this->main->running[$game]["phase"] == "PLAY")
						{
							if($this->main->teams->sameTeam($game, $seekerName, $hiderName)) return;
								
							$this->main->teams->setTeamAs($game, $hiderName, "seeker"); # push into seekers
								
							$seeker->addTitle("", str_replace("{player}", $hiderName, $this->main->config->getNested("messages.hider-found")));
							$hider->addTitle("", str_replace("{player}", $seekerName, $this->main->config->getNested("messages.seeker-found")));
								
							$particles = "pocketmine\\level\\particle\\HugeExplodeSeedParticle";
							if (class_exists($particles))
							{
								$hider->getLevel()->addParticle(new $particles($hider->add(0, 2)));
								$this->main->playSound(array($seeker, $hider), 2);
							}
						}
						$event->setCancelled(); # cancels damage
					}
				break;
				default:
				if(array_key_exists($event->getEntity()->getName(), $this->main->gameSession))
				{
					$event->setCancelled();
				}
			}
		}
	}

	public function onDeath(PlayerDeathEvent $event) : void
	{
		$name = $event->getPlayer()->getName();
		if(array_key_exists($name, $this->main->gameSession))
		{
			$this->main->removefromgame($name, $this->main->gameSession[$name]);
		}
	}
	
	public function onQuit(PlayerQuitEvent $event) : void
	{
		$name = $event->getPlayer()->getName();
		if(array_key_exists($name, $this->main->gameSession))
		{
			$this->main->removefromgame($name, $this->main->gameSession[$name]);
		}
	}
	
	public function onLevelChange(EntityLevelChangeEvent $event)
	{
		if ($event->getEntity() instanceof Player) 
		{
			$player = $event->getEntity();
			$from = $event->getOrigin()->getFolderName();
			$to = $event->getTarget()->getFolderName();
			
			if($this->main->arenadata->worldExists($from))
			{
				if(array_key_exists($player->getName(), $this->main->gameSession))
				{
					$game = $this->main->gameSession[$player->getName()];
					$this->main->removefromgame($player->getName(), $game);
				}
			}
			
			if($this->main->arenadata->worldExists($to))
			{
				$game = $this->main->gameSession[$player->getName()];
				if($this->main->running[$game]["phase"] == "WAIT")
				{
					$this->main->joinGame($player, $game);
				} else {
					$player->sendMessage($this->main->prefix . " > " . $this->config->getNested("messages.game-running"));
					$event->setCancelled();
				}
			}
		}
	}
	
	public function onMove(PlayerMoveEvent $event) {
		$username = $event->getPlayer()->getName();
		if(array_key_exists($username, $this->main->gameSession)) {
			$game = $this->main->gameSession[$username];
			if($this->main->running[$game]["phase"] == "HIDE") {
				if($this->main->teams->getTeam($game, $username) == "seeker") {
					$event->setCancelled();
				}
			}
		}
	}
	
	public function testingCommands(PlayerCommandPreprocessEvent $event)
	{
		$name = $event->getPlayer()->getName();
		if(array_key_exists($name, $this->main->gameSession))
		{
			$command = explode(" ", $event->getMessage());
			
			if(in_array($command[0], $this->main->config->getNested("bannedCmds")) == false) return;
			
			$event->getPlayer()->sendMessage($this->main->prefix . " > " . str_replace("{cmd}", $command[0], $this->main->config->getNested("messages.cantUseCmd")));
			$event->setCancelled();
		}
	}
	
}

?>
