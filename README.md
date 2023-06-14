## 📜 Tags:
| Name                     | Description                                |
|--------------------------|--------------------------------------------|
| {player.name}            | show player name                           |
| {player.ping}            | show player ping                           |
| {time.date}              | shows current date                         |
| {server.online}          | shows the players connected to the server  |
| {server.online.max}      | shows the maximum number of players        |
| {server.tps.usage}       | show server tps                            |
| {server.tps.percentage}  | shows the tps percentage of the server     |
| {lunarranks.rank}        | get player rank using (LunarRanks)[https://poggit.pmmp.io/p/LunarRanks]|
|||
## 📋 Commands / Permissions:
| Permission         | Command                |
|--------------------|------------------------|
| customscore        | `/scoreboard`          |
| customscore.reload | `/scoreboard reload`   |
## ⚒ API:
  to add custom tags you can use `PlayerScoreTagEvent`,
  Plugin:
  ```php
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