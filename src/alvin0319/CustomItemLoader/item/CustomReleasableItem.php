<?php

declare(strict_types=1);

namespace alvin0319\CustomItemLoader\item;

use pocketmine\item\Item;
use pocketmine\item\Releasable;
use pocketmine\player\Player;

final class CustomReleasableItem extends Item implements Releasable{
	use CustomItemTrait;

	public function getMaxStackSize() : int{
		return $this->getProperties()->getMaxStackSize();
	}

	public function getMiningEfficiency(bool $isCorrectTool) : float{
		return $this->properties->getMiningSpeed();
	}

	public function getMaxDurability() : int{
		return $this->getProperties()->getMaxDurability();
	}

	public function canStartUsingItem(Player $player) : bool{
		// TODO: Check player item in hand
		return true;
	}
}