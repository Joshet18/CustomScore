<?php

declare(strict_types=1);

namespace Joshet18\CustomScoreboard;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class ScoreAPI {
    use SingletonTrait;

	protected $scoreboards = [];
	protected $prefix = "[ScoreAPI]";

    public static function getInstance(): self{
        return self::$instance;
    }

    public function __construct(){
        self::setInstance($this);
    }

	public function isScore(Player $player):bool{
		return isset($this->scoreboards[$player->getName()]);
	}

	public function unsetScore(Player $player):void{
		unset($this->scoreboards[$player->getName()]);
	}

	public function newScore(Player $player, string $title) : void{
		$ev = new NewScoreEvent($player);
		$ev->call();
		if ($ev->isCancelled()) return;
		if(isset($this->scoreboards[$player->getName()]))return;
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = "sidebar";
		$pk->objectiveName = "objective";
		$pk->displayName = $title;
		$pk->criteriaName = "dummy";
		$pk->sortOrder = 0;
		$player->getNetworkSession()->sendDataPacket($pk);
		$this->scoreboards[$player->getName()] = "objective";
	}

	public function clear(Player $player) : void{
		for($line = 0; $line <= 15; $line++){
			self::removeLine($player, $line);
		}
	}

	public function removeScoreboard(Player $player) : void{
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = "objective";
		$player->getNetworkSession()->sendDataPacket($pk);
		unset($this->scoreboards[$player->getName()]);
	}

	public function removeLine(Player $player, int $line) : void{
		$pk = new SetScorePacket();
		$pk->type = SetScorePacket::TYPE_REMOVE;
		$entry = new ScorePacketEntry();
		$entry->objectiveName = "objective";
		$entry->score = 15 - $line;
		$entry->scoreboardId = ($line);
		$pk->entries[] = $entry;
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function setLine(Player $player, int $score, string $line): bool {
		if(!isset($this->scoreboards[$player->getName()])){
			//self::sendError("Cannot set a score to a player with no scoreboard");
			return false;
        }
    	if($score > 15 || $score < 1){
      		self::sendError("Score must be between the value of 1-15. {$score} out of range");
      		return false;
    	}
		$entry = new ScorePacketEntry();
		$entry->objectiveName = "objective";
		$entry->type = $entry::TYPE_FAKE_PLAYER;
		$entry->customName = $line;
		$entry->score = $score;
		$entry->scoreboardId = $score;
		$entry->actorUniqueId = $player->getId();
		$pk = new SetScorePacket();
		$pk->type = $pk::TYPE_CHANGE;
		$pk->entries[] = $entry;
		$player->getNetworkSession()->sendDataPacket($pk);
		return true;
	}

	public function setEmptyLine(Player $player, int $line) : void{
		$text = str_repeat(" ", $line);
		self::setLine($player, $line, $text);
	}
	
  private function sendError(string $error): void {
    Server::getInstance()->getLogger()->error($this->prefix." ".$error);
  }
}