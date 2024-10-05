<?php

namespace Joshet18\CustomScoreboard;

use pocketmine\console\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Config;
use pocketmine\scheduler\ClosureTask;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use IvanCraft623\RankSystem\RankSystem;
use IvanCraft623\RankSystem\utils\Utils;
use lunarelly\ranks\LunarRanksPlugin;
use pocketmine\scheduler\TaskHandler;
use supercrafter333\PlayedTime\PlayedTimeLoader;
use terpz710\kdrpe\Main as KDRPe;

class Loader extends PluginBase implements Listener
{
  use SingletonTrait;

  private const CONFIG = 1.2;
  private Config $scoreboards, $cf;
  private ScoreAPI $api;
  private ?TaskHandler $score_handler = null, $balance_handler = null;
  private array $cache = [];

  public function onLoad(): void
  {
    self::setInstance($this);
    $this->saveResource("Config.json");
    $this->saveResource("Scoreboards.json");
    $this->cf = new Config($this->getDataFolder() . "Config.json", Config::JSON);
    $this->scoreboards = new Config($this->getDataFolder() . "Scoreboards.json", Config::JSON);
    $this->api = new ScoreAPI();
    if (self::CONFIG > $this->getSettings()->get("version", self::CONFIG)) {
      if (file_exists($this->getDataFolder() . "Config_old.json")) unlink($this->getDataFolder() . "Config_old.json");
      rename($this->getDataFolder() . "Config.json", $this->getDataFolder() . "Config_old.json");
      $this->saveResource("Config.json");
      $this->cf = new Config($this->getDataFolder() . "Config.json", Config::JSON);
      $old = new Config($this->getDataFolder() . "Config_old.json", Config::JSON);
      foreach ($old->getAll() as $name => $value) {
        if (!in_array($name, ["version"])) {
          $this->getSettings()->set($name, $value);
          $this->getSettings()->save();
        }
      }
      if ($this->getSettings()->get("logger", false)) $this->getLogger()->notice("Your Config.json file is outdated. Your old Config.json has been saved as Config_old.json and a new Config.json file has been generated. Please update accordingly.");
    }
  }

  public function onEnable(): void
  {
    $this->getServer()->getAsyncPool()->submitTask(new CheckUpdatesTask($this->getName(), $this->getDescription()->getVersion()));
    $this->reload_handlers();
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
  }

  public function getScoreAPI(): ScoreAPI
  {
    return $this->api;
  }

  public function getSettings(): Config
  {
    return $this->cf;
  }

  public function getScoreboards(): Config
  {
    return $this->scoreboards;
  }

  private function reload_handlers(): void
  {
    $this->score_handler?->cancel();
    $this->balance_handler?->cancel();

    $this->score_handler = $this->getScheduler()->scheduleRepeatingTask(new ScoreTask(), (20 * (int)$this->getSettings()->get("update-score", 2)));
    if (class_exists(BedrockEconomy::class)) $this->balance_handler = $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () {
      foreach ($this->getServer()->getOnlinePlayers() as $player) $this->updatePlayerBalance($player);
    }), 20 * (int)$this->getSettings()->get("update-player-balance-tag", 5));
  }

  public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool
  {
    if (strtolower($cmd->getName()) === "scoreboard") {
      if (!isset($args[0])) {
        $sender->sendMessage("§cUsage: /scoreboard help");
        return false;
      }
      switch ($args[0]) {
        case "reload":
          if ($sender->hasPermission("customscore.reload") or $sender instanceof ConsoleCommandSender) {
            $this->cf = new Config($this->getDataFolder() . "Config.json", Config::JSON);
            $this->scoreboards = new Config($this->getDataFolder() . "Scoreboards.json", Config::JSON);
            $sender->sendMessage("§d[§eCustom§6ScoreBoard§d] §7>§a Settings applied successfully");
            $this->reload_handlers();
            foreach ($this->getServer()->getOnlinePlayers() as $player) $this->getScoreAPI()->removeScoreboard($player);
          } else {
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

  public function onEntityChangeWolrd(EntityTeleportEvent $ev): void
  {
    $entity = $ev->getEntity();
    if ($entity instanceof Player) {
      if ($ev->getFrom()->getWorld()->getId() === $ev->getTo()->getWorld()->getId()) return;
      $this->getScoreAPI()->removeScoreboard($entity);
    }
  }

  public function onPlayerLeave(PlayerJoinEvent $ev): void
  {
    unset($this->cache[$ev->getPlayer()->getName()]);
    $this->getScoreAPI()->removeScoreboard($ev->getPlayer());
  }

  public function onPlayerTags(PlayerScoreTagEvent $ev): void
  {
    $tags = $this->processDefaultTags($ev->getPlayer(), $ev->getTags());
    $ev->setTags($tags);
  }

  private function processDefaultTags(Player $player, array $tags): array
  {
    $result = [];
    foreach ($tags as $tag) {
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
        "{ranksystem.nametag}",
        "{bedrockeconomy.balance}",
        "{bedrockeconomy.balance.cap}",
        "{bedrockeconomy.currency.symbol}",
        "{bedrockeconomy.currency.name}",
        "{kdrpe.kills}",
        "{kdrpe.deaths}",
        "{kdrpe.kdr}",
        "{kdrpe.killstreak}",
        "{ptscore.total_time}",
        "{ptscore.session_time}",
      ], [
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
        (!class_exists(LunarRanksPlugin::class) ? "{lunarranks.rank}" : LunarRanksPlugin::getInstance()->getRank($player)->getDisplayName()),
        (!class_exists(RankSystem::class) ? "{ranksystem.ranks}" : Utils::ranks2string(RankSystem::getInstance()->getSessionManager()->get($player)->getRanks())),
        (!class_exists(RankSystem::class) ? "{ranksystem.highest_rank}" : RankSystem::getInstance()->getSessionManager()->get($player)->getHighestRank()->getName()),
        (!class_exists(RankSystem::class) ? "{ranksystem.nametag}" : RankSystem::getInstance()->getSessionManager()->get($player)->getNameTagFormat()),
        (!class_exists(BedrockEconomy::class) ? "{bedrockeconomy.balance}" : number_format($this->getPlayerBalance($player), 0, ".", BedrockEconomy::getInstance()->getCurrencyManager()->getNumberSeparator())),
        (!class_exists(BedrockEconomy::class) ? "{bedrockeconomy.balance.cap}" : number_format((is_null($cap = BedrockEconomy::getInstance()->getCurrencyManager()->getBalanceCap()) ? 0 : $cap), 0, ".", BedrockEconomy::getInstance()->getCurrencyManager()->getNumberSeparator())),
        (!class_exists(BedrockEconomy::class) ? "{bedrockeconomy.currency.symbol}" : BedrockEconomy::getInstance()->getCurrencyManager()->getSymbol()),
        (!class_exists(BedrockEconomy::class) ? "{bedrockeconomy.currency.name}" : BedrockEconomy::getInstance()->getCurrencyManager()->getName()),
        (!class_exists(KDRPe::class) ? "{kdrpe.kills}" : ($k = KDRPe::getInstance()->getKdrManager()->getKills($player->getName()))),
        (!class_exists(KDRPe::class) ? "{kdrpe.deaths}" : ($d = KDRPe::getInstance()->getKdrManager()->getDeaths($player->getName()))),
        (!class_exists(KDRPe::class) ? "{kdrpe.kdr}" : ($d === 0 ? $k : round($k / $d, 3))),
        (!class_exists(KDRPe::class) ? "{kdrpe.killstreak}" : KDRPe::getInstance()->getKdrManager()->getKillStreak($player->getName())),
        (!class_exists(PlayedTimeLoader::class) ? "{ptscore.total_time}" : $this->played_time_format(PlayedTimeLoader::getInstance()->getPlayedTimeManager()->getTotalTime($player))),
        (!class_exists(PlayedTimeLoader::class) ? "{ptscore.session_time}" : $this->played_time_format(PlayedTimeLoader::getInstance()->getPlayedTimeManager()->getSessionTime($player)))
      ], $tag);
    }
    return $result;
  }

  private function played_time_format(\DateInterval|null $dt): string
  {
    if ($dt === null) return "§cNOT FOUND";
    $str = "";
    $cfg = $this->getSettings()->getNested(...);
    $color = $this->getSettings()->getNested("played-time:dateformat-letters.color", "&e");
    if ($dt->y > 0) $str .= $color . $dt->y . $cfg("played-time:dateformat-letters.year", "y") . "§7, ";
    if ($dt->m > 0) $str .= $color . $dt->m . $cfg("played-time:dateformat-letters.month", "mo") . "§7, ";
    if ($dt->d > 0) $str .= $color . $dt->d . $cfg("played-time:dateformat-letters.day", "d") . "§7, ";
    if ($dt->h > 0) $str .= $color . $dt->h . $cfg("played-time:dateformat-letters.hour", "h") . "§7, ";
    if ($dt->i > 0) $str .= $color . $dt->i . $cfg("played-time:dateformat-letters.minute", "m") . "§7, ";
    if ($dt->s > 0) $str .= $color . $dt->s . $cfg("played-time:dateformat-letters.second", "s");
    return $str;
  }

  private function getPlayerBalance(Player $player): int
  {
    if (isset($this->cache[$player->getName()])) return (int)$this->cache[$player->getName()];
    return 0;
  }


  public function updatePlayerBalance(string|Player $player): void
  {
    if (!$player instanceof Player) $player = $this->getServer()->getPlayerExact($player);
    if (!$player?->isOnline()) return;
    BedrockEconomyAPI::beta()->get($player->getName())->onCompletion(
      function (?int $balance) use ($player): void {
        if (is_numeric($balance)) $this->cache[$player->getName()] = $balance;
      },
      function (): void {}
    );
  }
}
