<?php

declare(strict_types=1);

namespace alvin0319\CustomItemLoader;

use alvin0319\CustomItemLoader\item\CustomDurableItem;
use alvin0319\CustomItemLoader\item\CustomFoodItem;
use alvin0319\CustomItemLoader\item\CustomItem;
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

final class CustomItemManager{
	protected static array $registered = [];
	protected static ItemComponentPacket $packet;

	protected static \ReflectionProperty $coreToNetMap;
	protected static \ReflectionProperty $netToCoreMap;

	protected static array $coreToNetValues = [];
	protected static array $netToCoreValues = [];

	protected static \ReflectionProperty $itemTypeMap;

	protected static array $packetEntries = [];

	protected static array $itemTypeEntries = [];

	/**
	 * Function getItems
	 * @return Item[]
	 */
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

			self::$itemTypeEntries[] = $entry = new ItemTypeEntry($item->getProperties()->getNamespace(), $runtimeId, true);

			self::$packetEntries[] = new ItemComponentPacketEntry($item->getProperties()->getNamespace(), $item->getProperties()->getNbt());

			self::$registered[] = $item;

			ItemFactory::registerItem($item, true);
		}catch(\Throwable $e){
			//throw new AssumptionFailedError("Failed to register item: " . $e->getMessage(), 0, $e);
			throw $e;
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

	public static function registerDefaultItems(array $data) : void{
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
		return new CustomItem($name, $data);
	}
}