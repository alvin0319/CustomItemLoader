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

use pocketmine\inventory\ArmorInventory;
use pocketmine\nbt\tag\CompoundTag;
use function in_array;

/**
 * {@link ArmorComponent ArmorComponent} makes the item wearable.
 * If you want to make your item durable, you also have to add {@link DurableComponent DurableComponent}.
 */
final class ArmorComponent extends Component{

	public const TAG_ARMOR = "minecraft:armor";
	public const TAG_WEARABLE = "minecraft:wearable";

	/**
	 * @phpstan-param ArmorInventory::SLOT_* $armorSlot
	 */
	public function __construct(
		private readonly string $armorClass,
		private readonly int $armorSlot,
        private readonly int $defensePoints
	){
		static $acceptedArmorValues = ["gold", "none", "leather", "chain", "iron", "diamond", "elytra", "turtle", "netherite"];
		if(!in_array($armorClass, $acceptedArmorValues, true)){
			throw new \InvalidArgumentException("Invalid armor class $armorClass");
		}
	}

	public function getName() : string{
		return "armor";
	}

	public function buildComponent(CompoundTag $rootNBT) : void{
		/*
		// TODO: find out what does this do
		$this->nbt->getCompoundTag("components")?->getCompoundTag("item_properties")
			?->setString("enchantable_slot", match($armor_slot){
				"helmet" => "armor_helmet",
				"chest" => "armor_torso",
				"leggings" => "armor_legs",
				"boots" => "armor_feet",
				default => throw new AssumptionFailedError("Unknown armor type $armor_slot")
			});

		$this->nbt->getCompoundTag("components")?->getCompoundTag("item_properties")
			?->setString("enchantable_value", "10");
		*/
		$componentNBT = $rootNBT->getCompoundTag(self::TAG_COMPONENTS);
		$componentNBT->setTag(self::TAG_ARMOR, CompoundTag::create());
		$componentNBT->setTag(self::TAG_WEARABLE, CompoundTag::create());
	}

	public function processComponent(CompoundTag $rootNBT) : void{
		$componentNBT = $rootNBT->getCompoundTag(self::TAG_COMPONENTS);
		$armorNBT = $componentNBT->getCompoundTag(self::TAG_ARMOR);
		$wearableNBT = $componentNBT->getCompoundTag(self::TAG_WEARABLE);
		$armorNBT->setString("texture_type", $this->armorClass);
		$armorNBT->setInt("protection", $this->defensePoints);

		static $armorInfoIntToStringMap = [
			ArmorInventory::SLOT_HEAD => "slot.armor.head",
			ArmorInventory::SLOT_CHEST => "slot.armor.chest",
			ArmorInventory::SLOT_LEGS => "slot.armor.legs",
			ArmorInventory::SLOT_FEET => "slot.armor.feet",
		];

		$wearableNBT->setString("slot", $armorInfoIntToStringMap[$this->armorSlot]);
		$wearableNBT->setByte("dispensable", 1);
	}
}