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

use pocketmine\item\Armor;

class CustomArmorItem extends Armor{

	/** @var int */
	protected int $maxDurable = -1;
	/** @var int */
	protected int $maxStackSize = 64;
	/** @var int */
	protected int $defencePoint = 1;

	public function __construct(int $id, int $meta, string $name, int $maxStackSize = 64, int $maxDurable = 64, int $defencePoint = 1){
		parent::__construct($id, $meta, $name);
		$this->maxStackSize = $maxStackSize;
		$this->maxDurable = $maxDurable;
		$this->defencePoint = $defencePoint;
	}

	public function getMaxDurability() : int{
		return $this->maxDurable;
	}

	public function getDefensePoints() : int{
		return $this->defencePoint;
	}
}