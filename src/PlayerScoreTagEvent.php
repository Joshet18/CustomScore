<?php

namespace Joshet18\CustomScoreboard;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerScoreTagEvent extends PlayerEvent {
    private array $tags;

    public function __construct(Player $player, array $tags){
        $this->player = $player;
        $this->tags = $tags;
    }

    public function getTags():array{ return $this->tags; }
    public function setTags(array $tags):void{ $this->tags = $tags; }
}