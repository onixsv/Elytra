<?php

declare(strict_types=1);

namespace alvin0319\Elytra\item;

use pocketmine\item\Durable;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Elytra extends Durable{

	public function getMaxDurability() : int{
		return 432;
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function onClickAir(Player $player, Vector3 $directionVector) : ItemUseResult{
		if($player->getArmorInventory()->getChestplate()->getId() === 0){
			$this->pop();
			$player->getArmorInventory()->setChestplate($this);
			return ItemUseResult::SUCCESS();
		}
		return ItemUseResult::FAIL();
	}
}