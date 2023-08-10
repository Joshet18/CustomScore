## 📜 Tags:
| Name                     | Description                                |
|--------------------------|--------------------------------------------|
| {player.name}            | show player name                           |
| {player.ping}            | show player ping                           |
| {player.xp.level}        |                                            |
| {player.xp.progress}     |                                            |
| {player.xp.remainder}    |                                            |
| {player.xp.current_total}|                                            |
| {time.date}              | shows current date                         |
| {server.online}          | shows the players connected to the server  |
| {server.online.max}      | shows the maximum number of players        |
| {server.tps.usage}       | show server tps                            |
| {server.tps.percentage}  | shows the tps percentage of the server     |

## 📜 Soft Dependencys Tags:
| Name                     | Description              | Plugin                   |
|--------------------------|--------------------------|--------------------------|
| {lunarranks.rank}        | get player's rank        | [LunarRanks](https://poggit.pmmp.io/p/LunarRanks)|
| {ranksystem.ranks}       | get player's ranks       | [RankSystem](https://poggit.pmmp.io/p/RankSystem)|
| {ranksystem.highest_rank}| get the player's highest rank | [RankSystem](https://poggit.pmmp.io/p/RankSystem)|
| {ranksystem.nametag}     | get player's nametag     | [RankSystem](https://poggit.pmmp.io/p/RankSystem)|
| {bedrockeconomy.balance} | get player's balance     | [BedrockEconomy](https://poggit.pmmp.io/p/BedrockEconomy) |
| {bedrockeconomy.balance.cap} | getbalance cap       | [BedrockEconomy](https://poggit.pmmp.io/p/BedrockEconomy) |
| {bedrockeconomy.currency.symbol} | get economy symbol | [BedrockEconomy](https://poggit.pmmp.io/p/BedrockEconomy) |
| {bedrockeconomy.currency.name} | get economy name   | [BedrockEconomy](https://poggit.pmmp.io/p/BedrockEconomy) |

## 📋 Commands / Permissions:
| Permission         | Command                |
|--------------------|------------------------|
| customscore        | `/scoreboard`          |
| customscore.reload | `/scoreboard reload`   |

## TexturePack:
  increase the character limit of the scoreboard
  [TexturePack](https://github.com/Joshet18/CustomScore/blob/main/CustomScoreTexturePack.zip)

## ⚒ API:
  to add custom tags you can use `PlayerScoreTagEvent`
  Example:
  ```php
  use Joshet18\CustomScoreboard\PlayerScoreTagEvent;

  public function onPlayerTags(PlayerScoreTagEvent $ev): void {
    $ev->setTags($this->processTags($ev->getPlayer(), $ev->getTags()));
  }

  private function processTags(Player $player, array $tags):array{
    $result = [];
    foreach($tags as $tag){
      $result[] = str_replace([
        "{customtag.item.name}"
      ],[
        $player->getInventory()->getItemInHand()->getVanillaName()
      ], $tag);
    }
    return $result;
  }
  ```