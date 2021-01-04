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

class CustomFoodItem extends Food{

	protected $maxStackSize = 64;

	protected $nutrition;

	protected $canAlwaysEat = false;

	protected $saturation;

	public function __construct(int $id, int $meta = 0, string $name = "Unknown", int $maxStackSize = 64, int $nutrition = 1, bool $canAlwaysEat = false, float $saturation = 1){
		parent::__construct($id, $meta, $name);
		$this->maxStackSize = $maxStackSize;
		$this->nutrition = $nutrition;
		$this->canAlwaysEat = $canAlwaysEat;
		$this->saturation = $saturation;
	}

	public function getMaxStackSize() : int{
		return $this->maxStackSize;
	}

	public function getFoodRestore() : int{
		return $this->nutrition;
	}

	public function requiresHunger() : bool{
		return $this->canAlwaysEat;
	}

	public function getSaturationRestore() : float{
		return $this->saturation;
	}

	public function getResidue(){
		return parent::getResidue(); // TODO: Find out the components
	}
}