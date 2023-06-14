<?php

namespace Joshet18\CustomScoreboard;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ScoreTask extends Task {

    public function onRun(): void {
        foreach(Server::getInstance()->getOnlinePlayers() as $player){
            if(Loader::getInstance()->getSettings()->get("multi-world", false)){
                if(in_array($player->getWorld()->getFolderName(), Loader::getInstance()->getSettings()->getAll())){
                    Scoreboard::newScore($player, TextFormat::colorize(Loader::getInstance()->getScoreboards()->getNested("{$player->getWorld()->getFolderName()}.title", "")));
                    $lines = Loader::getInstance()->getScoreboards()->getNested("{$player->getWorld()->getFolderName()}.lines", []);
                    $ev = new PlayerScoreTagEvent($player, (is_array($lines) ? $lines : []));
                    $ev->call();
                    foreach($ev->getTags() as $score => $line){
                        if(($score + 1) < 16)Scoreboard::setLine($player, $score + 1, TextFormat::colorize($line));
                    }
                }
            }else{
                Scoreboard::newScore($player, TextFormat::colorize(Loader::getInstance()->getScoreboards()->getNested("default.title", "")));
                $lines = Loader::getInstance()->getScoreboards()->getNested("default.lines", []);
                $ev = new PlayerScoreTagEvent($player, (is_array($lines) ? $lines : []));
                $ev->call();
                foreach($ev->getTags() as $score => $line){
                    if(($score + 1) < 16)Scoreboard::setLine($player, $score + 1, TextFormat::colorize($line));
                }
            }
        }
    }
}