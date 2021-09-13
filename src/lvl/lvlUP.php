<?php
namespace lvl;

use lvl\window\SimpleWindowForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class lvlUP extends PluginBase implements Listener
{
    /**
     * @param PlayerCreationEvent $event
     */
    public function onPlayerCreation(PlayerCreationEvent $event){
        $event->setPlayerClass(RemoveSpace::class);
    }

    public function onEnable()
    {
        $this->getLogger()->info(TextFormat::BOLD . TextFormat::GREEN . "Plugin Level ON");
        $this->eco = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
        $this->saveResource("message.yml");
        $this->msgfile = new Config($this->getDataFolder() . "message.yml", Config::YAML, []);
        $this->msg = $this->msgfile->getAll();
        $this->data = new Config($this->getDataFolder() . "data.yml", Config::YAML, array());
        if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    public function replaceVars($str, array $vars) : string{
        foreach($vars as $key => $value){
            $str = str_replace("{" . $key . "}", $value, $str);
        }
        return $str;
    }

    //======================== Join Quit Message ========================//
    public function onJoin(PlayerJoinEvent $e)
    {
        $p = $e->getPlayer();
        $name = $p->getName();
            if(!$this->data->exists(strtolower($name)))
            {
                $this->data->setNested(strtolower($name) . ".level", "1");
                $this->data->setNested(strtolower($name) . ".xp", "0");
                $this->data->setNested(strtolower($name) . ".next_level", "250");
                $this->data->save();
            }

        $e->setJoinMessage($this->replaceVars($this->msg["join.message"], array(
            'player' => $p->getName()
        )));
        $this->displayName($p);
        $p->sendMessage($this->replaceVars($this->msg["welcome.message"], array(
            'player' => $p->getName()
        )));
    }
    public function quit(PlayerQuitEvent $e)
    {
        $p = $e->getPlayer()->getName();
        $e->setQuitMessage($this->replaceVars($this->msg["quit.message"], array(
            'player' => $p
        )));
    }

    public function displayName($p)
    {
        $nm = $p->getName();
        $p->setDisplayName($this->replaceVars($this->msg["display.name"], array(
            'player' => $nm,
            'level' => $this->getLvl($nm)
        )));
    }

    //======================== command ========================//
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        switch($cmd->getName()){
            case "lvl":
                if(!$sender instanceof Player){
                    $sender->sendMessage("Mohon Buka Command Ini Di In Game!");
                    return false;
                }
                if($sender instanceof Player){
                    $this->status($sender);
                }
                break;
            case "lvlabout":
                if($sender instanceof Player){
                    $this->aboutUs($sender);
                }
        }
        return true;
    }
    public function status(Player $p)
    {
        $p = $p->getPlayer();
        $nm = $p->getPlayer()->getName();

            $deskripsi = $this->replaceVars($this->msg["deskripsi.formUI"], array(
                'player' => $nm,
                'level' => $this->getLvl($nm),
                'xp' => $this->getXp($nm),
                'next_level' => $this->getNext($nm),
                'rewand_1' => $this->msg["money.rewand.1"],
                'rewand_2' => $this->msg["money.rewand.2"],
                'rewand_3' => $this->msg["money.rewand.3"],
                'rewand_4' => $this->msg["money.rewand.4"],
                'rewand_5' => $this->msg["money.rewand.5"]
            ));
            $window = new SimpleWindowForm("1", "Profile Level", $deskripsi);
            $window->addButton("1", "Ok");
            $window->showTo($p);

    }

    //======================== getData & addData ========================//
    public function getXp($p)
    {
        return $this->data->getAll()[strtolower($p)]["xp"];
    }
    public function getLvl($p)
    {
        return $this->data->getAll()[strtolower($p)]["level"];
    }
    public function getNext($p)
    {
        return $this->data->getAll()[strtolower($p)]["next_level"];
    }

    public function addXp($p, $xp)
    {
        $this->data->setNested(strtolower($p).".xp", $this->data->getAll()[strtolower($p)]["xp"] + $xp);
        $this->data->save();
    }
    public function addLvl($name)
    {
        $this->data->setNested(strtolower($name).".level", $this->data->getAll()[strtolower($name)]["level"] + 1);
        $this->data->save();

    }

    //======================== event player ========================//
    public function placeBlock(BlockPlaceEvent $e)
    {
        $p = $e->getPlayer();
        $this->updateData($p, $e);
    }
    public function breakBlock(BlockBreakEvent $e)
    {
        $p = $e->getPlayer();
        $this->updateData($p, $e);
    }

    //======================== Update Data ========================//
    public function updateData($player, $e)
    {
        $p = $player->getName();
        if($this->data->getAll()[strtolower($p)]["xp"] == $this->data->getAll()[strtolower($p)]["next_level"])
        {
            $volume = mt_rand();
            $this->data->setNested(strtolower($p).".next_level", $this->data->getAll()[strtolower($p)]["next_level"] + 1000);
            $e->getPlayer()->getLevel()->broadcastLevelSoundEvent($e->getPlayer(), LevelSoundEventPacket::SOUND_LEVELUP, (int) $volume);
            $this->addLvl($p);
            $this->displayName($player);
            $e->getPlayer()->sendMessage($this->replaceVars($this->msg["level.up.message"], array(
                'level' => $this->getLvl($p)
            )));

            //rewand money
            if($this->data->getAll()[strtolower($p)]["level"] == $this->msg["level.rewand.1"])
            {
                $this->eco->addmoney($p, $this->msg["money.rewand.1"]);
            }
            if($this->data->getAll()[strtolower($p)]["level"] == $this->msg["level.rewand.2"])
            {
                $this->eco->addmoney($p, $this->msg["money.rewand.2"]);
            }
            if($this->data->getAll()[strtolower($p)]["level"] == $this->msg["level.rewand.3"])
            {
                $this->eco->addmoney($p, $this->msg["money.rewand.3"]);
            }
            if($this->data->getAll()[strtolower($p)]["level"] == $this->msg["level.rewand.4"])
            {
                $this->eco->addmoney($p, $this->msg["money.rewand.4"]);
            }
            if($this->data->getAll()[strtolower($p)]["level"] == $this->msg["level.rewand.5"])
            {
                $this->eco->addmoney($p, $this->msg["money.rewand.5"]);
            }
        }
        if($this->data->getAll()[strtolower($p)]["level"] <= $this->msg["max.level"])
        {
            $this->addXp($p, 1);
        }
    }

    public function aboutUS(Player $p)
    {
        $p = $p->getPlayer();
        $window = new SimpleWindowForm("1", "About Plugin Level", "Plugin ini dibuat Oleh : Alis Dev\n\nsupport my YTchannel => Alis Dev\n\nThank's >0<");
        $window->addButton("1", "Ok");
        $window->showTo($p);
    }
}
