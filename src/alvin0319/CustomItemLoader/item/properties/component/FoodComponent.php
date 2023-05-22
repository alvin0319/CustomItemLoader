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

namespace alvin0319\CustomItemLoader\item\properties\component;

use alvin0319\CustomItemLoader\util\InvalidNBTStateException;
use pocketmine\nbt\tag\CompoundTag;

/**
 * This component makes the item edible.
 */
final class FoodComponent extends Component{

	public const TAG_FOOD = "minecraft:food";

	public function __construct(
		private readonly bool $canAlwaysEat,
		private readonly int $nutrition,
		private readonly float $saturationModifier = 0.6
	){ }

	public function getName() : string{
		return "food";
	}

	public function buildComponent(CompoundTag $rootNBT) : void{
		$componentNBT = $rootNBT->getCompoundTag(Component::TAG_COMPONENTS);
		if($componentNBT === null){
			throw new InvalidNBTStateException("Component tree is not built");
		}
		$componentNBT->setTag(self::TAG_FOOD, CompoundTag::create());
	}

	public function processComponent(CompoundTag $rootNBT) : void{
		$foodTag = $rootNBT->getCompoundTag(Component::TAG_COMPONENTS)?->getCompoundTag(self::TAG_FOOD);
		if($foodTag === null){
			throw new InvalidNBTStateException("Component tree is not built");
		}
		$foodTag->setByte("can_always_eat", $this->canAlwaysEat ? 1 : 0);
		$foodTag->setInt("nutrition", $this->nutrition);
		$foodTag->setFloat("saturation_modifier", $this->saturationModifier);
	}
}