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
use alvin0319\CustomItemLoader\item\CustomItemTrait;
use alvin0319\CustomItemLoader\item\CustomToolItem;
use alvin0319\CustomItemLoader\item\properties\CustomItemProperties;
use alvin0319\libItemRegistrar\libItemRegistrar;
use pocketmine\item\Item;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\protocol\ItemComponentPacket;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemComponentPacketEntry;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\SingletonTrait;
use ReflectionProperty;
use Throwable;

final class CustomItemManager{
	use SingletonTrait;

	/** @var Item[] */
	private array $registered = [];

	private ItemComponentPacket $packet;

	private ReflectionProperty $itemTypeMap;

	/** @var ItemComponentPacketEntry[] */
	private array $packetEntries = [];
	/** @var ItemTypeEntry[] */
	private array $itemTypeEntries = [];

	public function __construct(){
	}

	public function getItems() : array{
		return $this->registered;
	}

	public function isCustomItem(Item $item) : bool{
		foreach($this->registered as $other){
			if($item->equals($other, false, false)){
				return true;
			}
		}
		return false;
	}

	/**
	 * @param CustomItemTrait|Item $item
	 */
	public function registerItem($item) : void{
		try{
			$runtimeId = $item->getProperties()->getRuntimeId();

			$this->itemTypeEntries[] = new ItemTypeEntry($item->getProperties()->getNamespace(), $runtimeId, true);

			$this->packetEntries[] = new ItemComponentPacketEntry($item->getProperties()->getNamespace(), new CacheableNbt($item->getProperties()->getNbt()));

			$this->registered[] = $item;

			$new = clone $item;

			libItemRegistrar::getInstance()->registerItem($new, $runtimeId, true, $item->getProperties()->getNamespace());
		}catch(Throwable $e){
			throw new \InvalidArgumentException("Failed to register item: " . $e->getMessage(), $e->getLine(), $e);
		}
	}

	private function refresh() : void{
		$this->packet = ItemComponentPacket::create($this->packetEntries);
	}

	public function getPacket() : ItemComponentPacket{
		return clone $this->packet;
	}

	public function registerDefaultItems(array $data) : void{
		foreach($data as $name => $itemData){
			$this->registerItem(self::getItem($name, $itemData));
		}
		$this->refresh();
	}

	public static function getItem(string $name, array $data) : Item{
		$prop = new CustomItemProperties($name, $data);
		if($prop->isDurable()){
			return new CustomDurableItem($name, $prop);
		}
		if($prop->isFood()){
			return new CustomFoodItem($name, $prop);
		}
		if($prop->isArmor()){
			return new CustomArmorItem($name, $prop);
		}
		if($prop->isTool()){
			return new CustomToolItem($name, $prop);
		}
		return new CustomItem($name, $prop);
	}
}