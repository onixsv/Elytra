<?php
declare(strict_types=1);

namespace alvin0319\Elytra;

use alvin0319\Elytra\item\Elytra;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class Loader extends PluginBase implements Listener{

	/** @var bool[] */
	protected array $gliding = [];

	protected function onEnable() : void{
		ItemFactory::getInstance()->register($item = new Elytra(new ItemIdentifier(ItemIds::ELYTRA, 0), "Elytra"), true);
		CreativeInventory::getInstance()->add($item);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
			foreach($this->getServer()->getOnlinePlayers() as $player){
				if($this->isGliding($player)){
					$elytra = $player->getArmorInventory()->getChestplate();
					if($elytra instanceof Elytra){
						//$elytra->applyDamage(1);
						$player->getArmorInventory()->setChestplate($elytra);
						$player->resetFallDistance();
					}
				}
			}
		}), 20);
	}

	public function startGlide(Player $player) : void{
		$this->gliding[$player->getName()] = true;
		$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::GLIDING, true);
	}

	public function stopGlide(Player $player) : void{
		$this->gliding[$player->getName()] = false;
		$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::GLIDING, false);
	}

	public function isGliding(Player $player) : bool{
		return $player->getArmorInventory()->getChestplate() instanceof Elytra && ($this->gliding[$player->getName()] ?? false);
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();

		$player = $event->getOrigin()->getPlayer();
		if(!$player instanceof Player){
			return;
		}

		if($packet instanceof PlayerActionPacket){
			switch($packet->action){
				case PlayerAction::START_GLIDE:
					$this->startGlide($player);
					break;
				case PlayerAction::STOP_GLIDE:
					$this->stopGlide($player);
					break;
			}
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 *
	 * @priority        HIGHEST
	 * @handleCancelled true
	 */
	public function onEntityDamage(EntityDamageEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player){
			if($event->getCause() === EntityDamageEvent::CAUSE_FALL){
				if($player->getLocation()->getPitch() > -45 && $player->getLocation()->getPitch() < 45){
					$event->cancel();
				}
			}
		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$this->gliding[$player->getName()] = false;
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if(isset($this->gliding[$player->getName()])){
			unset($this->gliding[$player->getName()]);
		}
	}
}