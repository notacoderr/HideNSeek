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
	
	public function testingCommands(PlayerCommandPreprocessEvent $event)
	{
		if($event->getPlayer()->isLoggedIn == false)
		{
				$command = explode(" ", $event->getMessage());
				if($command[0] == "/login" || $command[0] == "/register") return;
				$event->setCancelled();
				$event->getPlayer()->sendMessage($this->main->config->getNested("otherMessages.notLoggedIn"));
		}
	}
}

?>
