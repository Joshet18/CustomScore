<?php

namespace Joshet18\CustomScoreboard;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ScoreTask extends Task {

    public function onRun(): void {
        foreach(Server::getInstance()->getOnlinePlayers() as $player){
            ScoreAPI::getInstance()->clear($player);
            if(Loader::getInstance()->getSettings()->get("multi-world", false)){
                if(in_array($player->getWorld()->getFolderName(), Loader::getInstance()->getSettings()->getAll())){
                    ScoreAPI::getInstance()->newScore($player, TextFormat::colorize(Loader::getInstance()->getScoreboards()->getNested("{$player->getWorld()->getFolderName()}.title", "")));
                    $lines = Loader::getInstance()->getScoreboards()->getNested("{$player->getWorld()->getFolderName()}.lines", []);
                    $lines = (is_array($lines) ? $lines : []);
                    $ev = new PlayerScoreTagEvent($player, $lines);
                    $ev->call();
                    foreach($ev->getTags() as $score => $line){
                        $score++;
                        if($score < 16)ScoreAPI::getInstance()->setLine($player, $score, TextFormat::colorize($line));
                    }
                }
            }else{
                ScoreAPI::getInstance()->newScore($player, TextFormat::colorize(Loader::getInstance()->getScoreboards()->getNested("default.title", "")));
                $lines = Loader::getInstance()->getScoreboards()->getNested("default.lines", []);
                $lines = (is_array($lines) ? $lines : []);
                $ev = new PlayerScoreTagEvent($player, $lines);
                $ev->call();
                foreach($ev->getTags() as $score => $line){
                    $score++;
                    if($score < 16)ScoreAPI::getInstance()->setLine($player, $score, TextFormat::colorize($line));
                }
            }
        }
    }
}