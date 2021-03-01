<?php

/*
 *    ____          _                  ___ _                 _                    _
 *   / ___|   _ ___| |_ ___  _ __ ___ |_ _| |_ ___ _ __ ___ | |    ___   __ _  __| | ___ _ __
 *  | |  | | | / __| __/ _ \| '_ ` _ \ | || __/ _ \ '_ ` _ \| |   / _ \ / _` |/ _` |/ _ \ '__|
 *  | |__| |_| \__ \ || (_) | | | | | || || ||  __/ | | | | | |__| (_) | (_| | (_| |  __/ |
 *   \____\__,_|___/\__\___/|_| |_| |_|___|\__\___|_| |_| |_|_____\___/ \__,_|\__,_|\___|_|
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace alvin0319\CustomItemLoader\item;

use pocketmine\item\Food;
use pocketmine\item\Item;

class CustomFoodItem extends Food{
	use CustomItemTrait;

	public function getMaxStackSize() : int{
		return $this->getProperties()->getMaxStackSize();
	}

	public function getFoodRestore() : int{
		return $this->getProperties()->getNutrition();
	}

	public function requiresHunger() : bool{
		return $this->getProperties()->getCanAlwaysEat();
	}

	public function getSaturationRestore() : float{
		return $this->getProperties()->getSaturation();
	}

	public function getResidue() : Item{
		return $this->getProperties()->getResidue();
	}
}