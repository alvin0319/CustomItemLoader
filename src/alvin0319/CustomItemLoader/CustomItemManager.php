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
use alvin0319\CustomItemLoader\item\CustomToolItem;
use alvin0319\CustomItemLoader\item\properties\CustomItemProperties;
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
use ReflectionClass;
use ReflectionProperty;
use Throwable;

final class CustomItemManager{
	use SingletonTrait {
		getInstance as getInstance_; // FIXME: This will valid when IntelliJ fixes singleton bug
	}

	public static function getInstance() : CustomItemManager{
		return self::getInstance_();
	}

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
		$ref = new ReflectionClass(ItemTranslator::class);
		$this->coreToNetMap = $ref->getProperty("simpleCoreToNetMapping");
		$this->netToCoreMap = $ref->getProperty("simpleNetToCoreMapping");
		$this->coreToNetMap->setAccessible(true);
		$this->netToCoreMap->setAccessible(true);

		$this->coreToNetValues = $this->coreToNetMap->getValue(ItemTranslator::getInstance());
		$this->netToCoreValues = $this->netToCoreMap->getValue(ItemTranslator::getInstance());

		$ref_1 = new ReflectionClass(ItemTypeDictionary::class);
		$this->itemTypeMap = $ref_1->getProperty("itemTypes");
		$this->itemTypeMap->setAccessible(true);

		$this->itemTypeEntries = $this->itemTypeMap->getValue(GlobalItemTypeDictionary::getInstance()->getDictionary());

		$this->packetEntries = [];

		$this->packet = ItemComponentPacket::create($this->packetEntries);
	}

	public function getItems() : array{
		return $this->registered;
	}

	public function isCustomItem(Item $item) : bool{
		foreach($this->registered as $other){
			if($item->equals($other, false, $item->hasNamedTag())){
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
			$id = $item->getProperties()->getId();
			$runtimeId = $item->getProperties()->getRuntimeId();

			$this->coreToNetValues[$id] = $runtimeId;
			$this->netToCoreValues[$runtimeId] = $id;

			$this->itemTypeEntries[] = new ItemTypeEntry($item->getProperties()->getNamespace(), $runtimeId, true);

			$this->packetEntries[] = new ItemComponentPacketEntry($item->getProperties()->getNamespace(), new CacheableNbt($item->getProperties()->getNbt()));

			$this->registered[] = $item;

			$new = clone $item;

			if(StringToItemParser::getInstance()->parse($item->getProperties()->getName()) === null){
				StringToItemParser::getInstance()->register($item->getProperties()->getName(), fn() => $new);
			}

			ItemFactory::getInstance()->register($item, true);
		}catch(Throwable $e){
			throw new \InvalidArgumentException("Failed to register item: " . $e->getMessage(), $e->getLine(), $e);
		}
		$this->refresh();
	}

	private function refresh() : void{
		$this->netToCoreMap->setValue(ItemTranslator::getInstance(), $this->netToCoreValues);
		$this->coreToNetMap->setValue(ItemTranslator::getInstance(), $this->coreToNetValues);
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
		if($prop->isTool()){
			return new CustomToolItem($name, $data);
		}
		return new CustomItem($name, $data);
	}
}