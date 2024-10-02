<?php

namespace Joshet18\CustomScoreboard;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class NewScoreEvent extends PlayerEvent implements Cancellable {

    public function __construct(protected Player $player)
    {
        
    }

    use CancellableTrait;

}