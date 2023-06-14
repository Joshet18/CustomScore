<?php

declare(strict_types=1);

namespace Joshet18\CustomScoreboard;

use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\player\Player;
use pocketmine\Server;

class Scoreboard {

	protected static $scoreboards = [];
	protected static $prefix = "[ScoreBoardAPI]";

	public static function isScore(Player $player):bool{
		return isset(self::$scoreboards[$player->getName()]);
	}

	public static function unsetScore(Player $player):void{
		unset(self::$scoreboards[$player->getName()]);
	}

	public static function newScore(Player $player, string $title) : void{
		if(isset(self::$scoreboards[$player->getName()])){
			return;
			//self::removeScoreboard($player);
		}
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = "sidebar";
		$pk->objectiveName = "§r";
		$pk->displayName = $title;
		$pk->criteriaName = "dummy";
		$pk->sortOrder = 0;
		$player->getNetworkSession()->sendDataPacket($pk);
		self::$scoreboards[$player->getName()] = "§r";
	}

	public static function clear(Player $player) : void{
		for($line = 0; $line <= 15; $line++){
			self::removeLine($player, $line);
		}
	}

	public static function removeScoreboard(Player $player): void{
		if(!self::isScore($player))return;
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = "§r";
		$player->getNetworkSession()->sendDataPacket($pk);
		unset(self::$scoreboards[$player->getName()]);
	}

	public static function removeLine(Player $player, int $line) : void{
		$pk = new SetScorePacket();
		$pk->type = SetScorePacket::TYPE_REMOVE;
		$entry = new ScorePacketEntry();
		$entry->objectiveName = "§r";
		$entry->score = 15 - $line;
		$entry->scoreboardId = ($line);
		$pk->entries[] = $entry;
		$player->getNetworkSession()->sendDataPacket($pk);
	}
	
  public static function setLine(Player $player, int $score, string $line): void {
    if(!isset(self::$scoreboards[$player->getName()])){
      self::sendError("Cannot set a score to a player with no scoreboard");
      return;
    }
    if($score > 15 || $score < 1){
      self::sendError("Score must be between the value of 1-15. {$score} out of range");
      return;
    }
		$entry = new ScorePacketEntry();
		$entry->objectiveName = "§r";
		$entry->type = $entry::TYPE_FAKE_PLAYER;
		$entry->customName = $line;
		$entry->score = $score;
		$entry->scoreboardId = $score;
		$entry->actorUniqueId = $player->getId();
		$pk = new SetScorePacket();
		$pk->type = $pk::TYPE_CHANGE;
		$pk->entries[] = $entry;
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public static function setEmptyLine(Player $player, int $line) : void{
		$text = str_repeat(" ", $line);
		self::setLine($player, $line, $text);
	}
	
  private static function sendError(string $error): void {
    Server::getInstance()->getLogger()->error(Scoreboard::$prefix." ".$error);
  }
}