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
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\StringToItemParser;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\protocol\ItemComponentPacket;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemComponentPacketEntry;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

final class CustomItemManager{
	use SingletonTrait;

	/** @var Item[] */
	protected array $registered = [];

	protected ItemComponentPacket $packet;

	protected ReflectionProperty $coreToNetMap;

	protected ReflectionProperty $netToCoreMap;

	protected array $coreToNetValues = [];

	protected array $netToCoreValues = [];

	protected ReflectionProperty $itemTypeMap;

	/** @var ItemComponentPacketEntry[] */
	protected array $packetEntries = [];
	/** @var ItemTypeEntry[] */
	protected array $itemTypeEntries = [];

	public function __construct(){
		$ref = new ReflectionClass(ItemTypeDictionary::class);
		$this->coreToNetMap = $ref->getProperty('stringToIntMap');
		$this->netToCoreMap = $ref->getProperty('intToStringIdMap');
		$this->itemTypeMap = $ref->getProperty('itemTypes');
		$this->coreToNetMap->setAccessible(true);
		$this->netToCoreMap->setAccessible(true);
		$this->itemTypeMap ->setAccessible(true);

		$this->coreToNetValues = $this->coreToNetMap->getValue(GlobalItemTypeDictionary::getInstance()->getDictionary());
		$this->netToCoreValues = $this->netToCoreMap->getValue(GlobalItemTypeDictionary::getInstance()->getDictionary());

		$this->itemTypeEntries = $this->itemTypeMap->getValue(GlobalItemTypeDictionary::getInstance()->getDictionary());

		$this->packetEntries = [];

		$this->packet = ItemComponentPacket::create($this->packetEntries);
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
			$properties = $item->getProperties();
			$namespace = $properties->getNamespace();
			$meta = $properties->getMeta();

			$this->coreToNetValues[$namespace] = $properties->getRuntimeId();
			$this->netToCoreValues[$properties->getRuntimeId()] = $namespace;

			$this->itemTypeEntries[] = new ItemTypeEntry($namespace, $properties->getRuntimeId(), true);

			$this->packetEntries[] = new ItemComponentPacketEntry($namespace, new CacheableNbt($properties->getNbt()));

			$this->registered[] = $item;

			$new = clone $item;

			if(StringToItemParser::getInstance()->parse($properties->getName()) === null){
				StringToItemParser::getInstance()->register($properties->getName(), fn() => $new);
			}

			ItemFactory::getInstance()->register($item, true);

			GlobalItemDataHandlers::getSerializer()->map($item, fn() => new SavedItemData($namespace, $meta, null, null));
			GlobalItemDataHandlers::getDeserializer()->map($namespace, fn() => $new);
		}catch(Throwable $e){
			throw new \InvalidArgumentException("Failed to register item: " . $e->getMessage(), $e->getLine(), $e);
		}
		$this->refresh();
	}

	private function refresh() : void{
		$this->netToCoreMap->setValue(GlobalItemTypeDictionary::getInstance()->getDictionary(), $this->netToCoreValues);
		$this->coreToNetMap->setValue(GlobalItemTypeDictionary::getInstance()->getDictionary(), $this->coreToNetValues);
		$this->itemTypeMap->setValue(GlobalItemTypeDictionary::getInstance()->getDictionary(), $this->itemTypeEntries);
		$this->packet = ItemComponentPacket::create($this->packetEntries);
	}

	public function getPacket() : ItemComponentPacket{
		return clone $this->packet;
	}

	public function registerDefaultItems(array $data, bool $reload = false) : void{
		if($reload){
			ItemTranslator::reset();
			GlobalItemTypeDictionary::reset();
		}
		foreach($data as $name => $itemData){
			$this->registerItem(self::getItem($name, $itemData));
		}
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