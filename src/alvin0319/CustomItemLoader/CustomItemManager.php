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

namespace alvin0319\CustomItemLoader;

use alvin0319\CustomItemLoader\item\CustomArmorItem;
use alvin0319\CustomItemLoader\item\CustomDurableItem;
use alvin0319\CustomItemLoader\item\CustomFoodItem;
use alvin0319\CustomItemLoader\item\CustomItem;
use alvin0319\CustomItemLoader\item\CustomItemBlock;
use alvin0319\CustomItemLoader\item\CustomItemTrait;
use alvin0319\CustomItemLoader\item\properties\CustomItemProperties;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\ItemComponentPacket;
use pocketmine\network\mcpe\protocol\types\ItemComponentPacketEntry;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\AssumptionFailedError;
use ReflectionClass;
use ReflectionProperty;

final class CustomItemManager{
	/** @var Item[] */
	protected static $registered = [];
	/** @var ItemComponentPacket */
	protected static $packet;
	/** @var ReflectionProperty */
	protected static $coreToNetMap;
	/** @var ReflectionProperty */
	protected static $netToCoreMap;
	/** @var array */
	protected static $coreToNetValues = [];
	/** @var array */
	protected static $netToCoreValues = [];
	/** @var ReflectionProperty */
	protected static $itemTypeMap;
	/** @var ItemComponentPacketEntry[] */
	protected static $packetEntries = [];
	/** @var ItemTypeEntry[] */
	protected static $itemTypeEntries = [];

	public static function getItems() : array{
		return self::$registered;
	}

	public static function init() : void{
		$ref = new ReflectionClass(ItemTranslator::class);
		self::$coreToNetMap = $ref->getProperty("simpleCoreToNetMapping");
		self::$netToCoreMap = $ref->getProperty("simpleNetToCoreMapping");
		self::$coreToNetMap->setAccessible(true);
		self::$netToCoreMap->setAccessible(true);

		self::$coreToNetValues = self::$coreToNetMap->getValue(ItemTranslator::getInstance());
		self::$netToCoreValues = self::$netToCoreMap->getValue(ItemTranslator::getInstance());

		$ref_1 = new ReflectionClass(ItemTypeDictionary::class);
		self::$itemTypeMap = $ref_1->getProperty("itemTypes");
		self::$itemTypeMap->setAccessible(true);

		self::$itemTypeEntries = self::$itemTypeMap->getValue(ItemTypeDictionary::getInstance());

		self::$packetEntries = [];

		self::$packet = ItemComponentPacket::create(self::$packetEntries);
	}

	/**
	 * @param CustomItemTrait|Item $item
	 */
	public static function registerItem($item) : void{
		try{
			$id = $item->getProperties()->getId();
			$runtimeId = $item->getProperties()->getRuntimeId();

			self::$coreToNetValues[$id] = $runtimeId;
			self::$netToCoreValues[$runtimeId] = $id;

			self::$itemTypeEntries[] = new ItemTypeEntry($item->getProperties()->getNamespace(), $runtimeId, true);

			self::$packetEntries[] = new ItemComponentPacketEntry($item->getProperties()->getNamespace(), $item->getProperties()->getNbt());

			self::$registered[] = $item;

			ItemFactory::registerItem($item, true);
		}catch(\Throwable $e){
			throw new AssumptionFailedError("Failed to register item: " . $e->getMessage(), $e->getLine(), $e);
		}
		self::refresh();
	}

	private static function refresh() : void{
		self::$netToCoreMap->setValue(ItemTranslator::getInstance(), self::$netToCoreValues);
		self::$coreToNetMap->setValue(ItemTranslator::getInstance(), self::$coreToNetValues);
		self::$itemTypeMap->setValue(ItemTypeDictionary::getInstance(), self::$itemTypeEntries);
		self::$packet = ItemComponentPacket::create(self::$packetEntries);
	}

	public static function getPacket() : ItemComponentPacket{
		return clone self::$packet;
	}

	public static function registerDefaultItems(array $data, bool $reload = false) : void{
		if($reload){
			ItemTranslator::reset();
			ItemTypeDictionary::reset();
			self::init();
		}
		foreach($data as $name => $itemData){
			self::registerItem(self::getItem($name, $itemData));
		}
	}

	public static function getItem(string $name, array $data) : Item{
		$prop = new CustomItemProperties($name, $data);
		if($prop->isDurable()){
			return new CustomDurableItem($name, $data);
		}
		if($prop->isFood()){
			return new CustomFoodItem($name, $data);
		}
		if($prop->isArmor()){
			return new CustomArmorItem($name, $data);
		}
		if($prop->isBlock()){
			return new CustomItemBlock($name, $data);
		}
		return new CustomItem($name, $data);
	}
}