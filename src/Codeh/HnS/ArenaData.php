<?php

namespace Codeh\HnS;

use pocketmine\Server;

class ArenaData {
    
    private $main;

    public function __construct(\Codeh\HnS\HnS $core)
    {
		$this->main = $core;
    }
    
	public function init() : void
	{
		$this->db = new \SQLite3($this->main->getDataFolder() . "arenas.db");
		$this->db->exec("CREATE TABLE IF NOT EXISTS games (game BLOB PRIMARY KEY COLLATE NOCASE, world BLOB);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS lobby (game BLOB PRIMARY KEY COLLATE NOCASE, x REAL, y REAL, z REAL);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS hider (game BLOB PRIMARY KEY COLLATE NOCASE, x REAL, y REAL, z REAL);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS seeker (game BLOB PRIMARY KEY COLLATE NOCASE, x REAL, y REAL, z REAL);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS min (game BLOB PRIMARY KEY COLLATE NOCASE, count INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS max (game BLOB PRIMARY KEY COLLATE NOCASE, count INT);");
	}
	
	# -- saving a data -- #
	public function saveGame(string $game, string $world) : void
	{
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO games (game, world) VALUES (:game, :world);");
		$stmt->bindValue(":game", $game);
		$stmt->bindValue(":world", $world);
		$stmt->execute();
	}
	
	public function setLobbySpawn(string $game, array $pos) : void
	{
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO lobby (game, x, y, z) VALUES (:game, :x, :y, :z);");
		$stmt->bindValue(":game", $game);
		$stmt->bindValue(":x", $pos['x']);
		$stmt->bindValue(":y", $pos['y']);
		$stmt->bindValue(":z", $pos['z']);
		$stmt->execute();
	}
	
	public function setHiderSpawn(string $game, array $pos) : void
	{
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO hider (game, x, y, z) VALUES (:game, :x, :y, :z);");
		$stmt->bindValue(":game", $game);
		$stmt->bindValue(":x", $pos['x']);
		$stmt->bindValue(":y", $pos['y']);
		$stmt->bindValue(":z", $pos['z']);
		$stmt->execute();
	}
	
	public function setSeekerSpawn(string $game, array $pos) : void
	{
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO seeker (game, x, y, z) VALUES (:game, :x, :y, :z);");
		$stmt->bindValue(":game", $game);
		$stmt->bindValue(":x", $pos['x']);
		$stmt->bindValue(":y", $pos['y']);
		$stmt->bindValue(":z", $pos['z']);
		$stmt->execute();
	}
	
	public function setMin(string $game, int $i) : void
	{
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO min (game, count) VALUES (:game, :count);");
		$stmt->bindValue(":game", $game);
		$stmt->bindValue(":count", $i);
		$stmt->execute();
	}
	
	public function setMax(string $game, int $i) : void
	{
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO max (game, count) VALUES (:game, :count);");
		$stmt->bindValue(":game", $game);
		$stmt->bindValue(":count", $i);
		$stmt->execute();
	}
	
	# -- getting game data -- #
	public function getAllGames() : array
	{
		$x = [];
		$tablesquery = $this->db->query("SELECT * FROM games;");
		while ($table = $tablesquery->fetchArray(SQLITE3_ASSOC)) {
			$x[ $table['game'] ] = $table['world'];
		}
		return $x;
	}
	
	public function getWorld(string $game) : string
	{
		return $this->db->query("SELECT * FROM games WHERE game = '$game';")->fetchArray(SQLITE3_ASSOC) ["world"];
	}
	
	public function getLobbySpawn(string $game) : array
	{
		$data = $this->db->query("SELECT * FROM lobby WHERE game = '$game';")->fetchArray(SQLITE3_ASSOC);
		return [ $data["x"], $data["y"], $data["z"] ];
	}
	
	public function getHiderSpawn(string $game) : array
	{
		$data = $this->db->query("SELECT * FROM hider WHERE game = '$game';")->fetchArray(SQLITE3_ASSOC);
		return [ $data["x"], $data["y"], $data["z"] ];
	}
	
	public function getSeekerSpawn(string $game) : array
	{
		$data = $this->db->query("SELECT * FROM seeker WHERE game = '$game';")->fetchArray(SQLITE3_ASSOC);
		return [ $data["x"], $data["y"], $data["z"] ];
	}
	
	public function getMin(string $game) : int
	{
		return $this->db->query("SELECT * FROM min WHERE game = '$game';")->fetchArray(SQLITE3_ASSOC) ["count"];
	}
	
	public function getMax(string $game) : int
	{
		return $this->db->query("SELECT * FROM max WHERE game = '$game';")->fetchArray(SQLITE3_ASSOC) ["count"];
	}
}

?>
