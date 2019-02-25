<?php

namespace Codeh\HnS;

use pocketmine\Server;
use pocketmine\Player;

use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\level\Position;

use pocketmine\tile\Sign;
use pocketmine\level\Level;
use pocketmine\item\Item;

class RefreshSigns extends pocketmine\scheduler\Task
{
	
public function __construct(\Codeh\Hns\Hns $core)
{
	$this->plugin = $core;
}
  
public function onRun($tick)
{
	
	$level = $this->plugin->getServer()->getDefaultLevel();
	$tiles = $level->getTiles();
	foreach($tiles as $t) {
		if($t instanceof Sign) {	
			$text = $t->getText();
			if($text[3] == $this->plugin->prefix)
			{
				$namemap = str_replace("§f", "", $text[2]);
				$arenalevel = $this->plugin->getServer()->getLevelByName( $namemap );
				$playercount = count($arenalevel->getPlayers());
				$ingame = TextFormat::AQUA . "[Join]";
				$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
				if($config->get($namemap . "PlayTime") <> $this->plugin->playtime)
				{
					$ingame = TextFormat::DARK_PURPLE . "[Running]";
				}
				if( $playercount >= 12)
				{
					$ingame = TextFormat::GOLD . "[Full]";
				}
				$t->setText($ingame, TextFormat::YELLOW  . $playercount . " / 12", $text[2], $this->plugin->prefix);
			}
		}
	}
}

}
