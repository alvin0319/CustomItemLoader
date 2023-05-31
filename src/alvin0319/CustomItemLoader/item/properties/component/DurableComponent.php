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
 * This component makes the item to have durability.
 */
final class DurableComponent extends Component{

	public const TAG_DURABILITY = "minecraft:durability";

	public function __construct(private readonly int $maxDurability){ }

	public function getName() : string{
		return "durable";
	}

	public function buildComponent(CompoundTag $rootNBT) : void{
		$componentNBT = $rootNBT->getCompoundTag(self::TAG_COMPONENTS);
		if($componentNBT === null){
			throw new InvalidNBTStateException("Component tree is not built");
		}
		$componentNBT->setTag(self::TAG_DURABILITY, CompoundTag::create());
	}

	public function processComponent(CompoundTag $rootNBT) : void{
		$durableNBT = $rootNBT->getCompoundTag(self::TAG_COMPONENTS)?->getCompoundTag(self::TAG_DURABILITY);
		if($durableNBT === null){
			throw new InvalidNBTStateException("Component tree is not built");
		}
		$durableNBT->setTag("damage_chance", CompoundTag::create()
			->setInt("min", 100) // maybe make this a config value
			->setInt("max", 100) // maybe make this a config value
		);
		$durableNBT->setInt("max_durability", $this->maxDurability);
	}
}