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

use alvin0319\CustomItemLoader\item\properties\CustomItemProperties;
use pocketmine\item\ItemIdentifier;

trait CustomItemTrait{
	/** @var CustomItemProperties */
	protected CustomItemProperties $properties;

	public function __construct(string $name, CustomItemProperties $properties){
		$this->properties = $properties;
		parent::__construct(new ItemIdentifier($this->properties->getId()), $this->properties->getName());
	}

	public function getProperties() : CustomItemProperties{
		return $this->properties;
	}

	public function getAttackPoints() : int{
		return $this->properties->getAttackPoints();
	}

	public function getCooldownTicks() : int{
		return $this->properties->getCooldown();
	}

	public function getBlockToolType() : int{
		return $this->properties->getBlockToolType();
	}

	public function getBlockToolHarvestLevel() : int{
		return $this->properties->getBlockToolHarvestLevel();
	}

	// TODO: This needs to be fixed in order to display break progress correctly.
	public function getMiningEfficiency(bool $isCorrectTool) : float{
		return $this->properties->getMiningSpeed();
	}

	public function getMaxStackSize() : int{
		return $this->getProperties()->getMaxStackSize();
	}
}