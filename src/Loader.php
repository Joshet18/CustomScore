<?php

namespace Joshet18\CustomScoreboard;

use pocketmine\console\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Config;

use IvanCraft623\RankSystem\RankSystem;
use IvanCraft623\RankSystem\utils\Utils;
use lunarelly\ranks\LunarRanksPlugin;

class Loader extends PluginBase implements Listener{
  use SingletonTrait;
  
  private Config $scoreboards, $cf;
  
  public function onLoad(): void {
    self::setInstance($this);
    $this->saveResource("Config.json");
    $this->saveResource("Scoreboards.json");
    $this->cf = new Config($this->getDataFolder()."Config.json", Config::JSON);
    $this->scoreboards = new Config($this->getDataFolder()."Scoreboards.json", Config::JSON);
  }

  public function onEnable(): void {
    $this->getServer()->getAsyncPool()->submitTask(new CheckUpdatesTask($this->getName(), $this->getDescription()->getVersion()));
    $this->getScheduler()->scheduleRepeatingTask(new ScoreTask(), (20 * (int)$this->getScoreboards()->get("update-score", 2)));
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
  }

  public function getSettings(): Config { return $this->cf; }

  public function getScoreboards(): Config { return $this->scoreboards; }

  public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
    if(strtolower($cmd->getName()) === "scoreboard"){
      if(!isset($args[0])){
        $sender->sendMessage("§cUsage: /scoreboard help");
        return false;
      }
      switch($args[0]){
        case "reload":
          if($sender->hasPermission("customscore.reload") or $sender instanceof ConsoleCommandSender){
            $this->cf = new Config($this->getDataFolder()."Config.json", Config::JSON);
            $this->scoreboards = new Config($this->getDataFolder()."Scoreboards.json", Config::JSON);
            $sender->sendMessage("§d[§eCustom§6ScoreBoard§d] §7>§a Settings applied successfully");
            foreach($this->getServer()->getOnlinePlayers() as $player)Scoreboard::removeScoreboard($player);
          }else{
            $sender->sendMessage("§d[§eCustom§6ScoreBoard§d] §7> §cYou don't have permission to use this command");
          }
        break;
        case "help":
        case "?":
          $sender->sendMessage("§a/scoreboard §ereload");
        break;
      }
    }
    return true;
  }

  public function onEntityChangeWolrd(EntityTeleportEvent $ev): void {
    $entity = $ev->getEntity();
    if($entity instanceof Player){
      if($ev->getFrom()->getWorld()->getId() === $ev->getTo()->getWorld()->getId())return;
      Scoreboard::removeScoreboard($entity);
    }
  }

  public function onPlayerLeave(PlayerJoinEvent $ev): void {
    Scoreboard::removeScoreboard($ev->getPlayer());
  }

  public function onPlayerTags(PlayerScoreTagEvent $ev): void {
    $ev->setTags($this->processDefaultTags($ev->getPlayer(), $ev->getTags()));
  }

  private function processDefaultTags(Player $player, array $tags):array{
    $result = [];
    foreach($tags as $tag){
      $result[] = str_replace([
        "{player.name}",
        "{player.ping}",
        "{player.xp.level}",
		    "{player.xp.progress}",
	    	"{player.xp.remainder}",
        "{player.xp.current_total}",
        "{time.date}",
        "{server.online}",
        "{server.online.max}",
        "{server.tps.usage}",
        "{server.tps.percentage}",
        "{lunarranks.rank}",
        "{ranksystem.ranks}",
        "{ranksystem.highest_rank}",
        "{ranksystem.nametag}"
      ],[
        $player->getName(),
        $player->getNetworkSession()->getPing(),
        $player->getXpManager()->getXpLevel(),
        $player->getXpManager()->getXpProgress(),
        $player->getXpManager()->getRemainderXp(),
        $player->getXpManager()->getCurrentTotalXp(),
        date($this->getScoreboards()->get("date-format", "d-m-Y")),
        count($this->getServer()->getOnlinePlayers()),
        $this->getServer()->getMaxPlayers(),
        $this->getServer()->getTicksPerSecond(),
        $this->getServer()->getTickUsage(),
        (!class_exists(LunarRanksPlugin::class) ? "Not found" : LunarRanksPlugin::getInstance()->getRank($player)->getDisplayName()),
        (!class_exists(RankSystem::class) ? "Not found" : Utils::ranks2string(RankSystem::getInstance()->getSessionManager()->get($player)->getRanks())),
        (!class_exists(RankSystem::class) ? "Not found" : RankSystem::getInstance()->getSessionManager()->get($player)->getHighestRank()->getName()),
        (!class_exists(RankSystem::class) ? "Not found" : RankSystem::getInstance()->getSessionManager()->get($player)->getNameTagFormat())
      ], $tag);
    }
    return $result;
  }
}