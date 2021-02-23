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

use alvin0319\CustomItemLoader\command\ResourcePackCreateCommand;
use alvin0319\CustomItemLoader\item\CustomArmorItem;
use alvin0319\CustomItemLoader\item\CustomDurableItem;
use alvin0319\CustomItemLoader\item\CustomFoodItem;
use alvin0319\CustomItemLoader\item\CustomItem;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\ItemComponentPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\ItemComponentPacketEntry;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;
use Throwable;

use function is_dir;
use function is_numeric;
use function mkdir;

class CustomItemLoader extends PluginBase implements Listener{
	use SingletonTrait;

	/** @var ItemComponentPacket */
	protected ItemComponentPacket $packet;

	protected array $netToCoreValues = [];

	protected array $coreToNetValues = [];

	protected array $itemTypeEntries = [];

	public function onLoad() : void{
		self::setInstance($this);
	}

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveDefaultConfig();

		if(!is_dir($this->getResourcePackFolder())){
			mkdir($this->getResourcePackFolder());
		}

		$this->getServer()->getCommandMap()->register("customitemloader", new ResourcePackCreateCommand());

		$ref = new ReflectionClass(ItemTranslator::class);
		$simpleCoreToNetMap = $ref->getProperty("simpleCoreToNetMapping");
		$simpleNetToCoreMap = $ref->getProperty("simpleNetToCoreMapping");
		$simpleCoreToNetMap->setAccessible(true);
		$simpleNetToCoreMap->setAccessible(true);

		$this->coreToNetValues = $simpleCoreToNetMap->getValue(ItemTranslator::getInstance());
		$this->netToCoreValues = $simpleNetToCoreMap->getValue(ItemTranslator::getInstance());

		$ref_1 = new ReflectionClass(ItemTypeDictionary::class);
		$itemTypes = $ref_1->getProperty("itemTypes");
		$itemTypes->setAccessible(true);
		$intToStringIdMap = $ref_1->getProperty("intToStringIdMap");
		$stringToIntMap = $ref_1->getProperty("stringToIntMap");

		$intToStringIdMap->setAccessible(true);
		$stringToIntMap->setAccessible(true);

		$this->itemTypeEntries = $itemTypes->getValue(ItemTypeDictionary::getInstance());

		$packetEntries = [];

		foreach($this->getConfig()->get("items", []) as $name => $itemData){
			$nbt = $this->parseTag($name, $itemData);
			$packetEntries[] = new ItemComponentPacketEntry($itemData["namespace"], $nbt);
		}

		$simpleNetToCoreMap->setValue(ItemTranslator::getInstance(), $this->netToCoreValues);
		$simpleCoreToNetMap->setValue(ItemTranslator::getInstance(), $this->coreToNetValues);
		$itemTypes->setValue(ItemTypeDictionary::getInstance(), $this->itemTypeEntries);

		$this->packet = ItemComponentPacket::create($packetEntries);
	}

	public function getResourcePackFolder() : string{
		return $this->getDataFolder() . "resource_packs/";
	}

	public function parseTag(string $name, array $data) : CompoundTag{
		$this->validateData($data);

		$id = (int) $data["id"];
		$meta = (int) $data["meta"];

		$namespace = (string) $data["namespace"];

		$runtimeId = $id + ($id > 0 ? 5000 : -5000);

		$allow_off_hand = (int) ($data["allow_off_hand"] ?? false);
		$can_destroy_in_creative = (int) ($data["can_destroy_in_creative"] ?? false);
		$creative_category = (int) ($data["creative_category"] ?? 1); // 1 건축 2 자연 3 아이템
		$hand_equipped = (int) ($data["hand_equipped"] ?? true);
		$max_stack_size = (int) ($data["max_stack_size"] ?? 64);
		$mining_speed = (float) ($data["mining_speed"] ?? 1);

		$food = (int) ($data["food"] ?? false);
		$can_always_eat = (int) ($data["can_always_eat"] ?? false);
		$nutrition = (int) ($data["nutrition"] ?? 1);
		$saturation = (float) ($data["saturation"] ?? 1);
		$residue = isset($data["residue"]) ? ItemFactory::get((int) $data["residue"]["id"], (int) ($data["residue"]["meta"] ?? 0)) : ItemFactory::get(0);

		$armor = (bool) ($data["armor"] ?? false);

		$nbt = new CompoundTag("", [
			new CompoundTag("components", [
				new CompoundTag("minecraft:icon", [
					new StringTag("texture", $data["texture"])
				]),
				new CompoundTag("item_properties", [
					new IntTag("use_duration", 32),
					new IntTag("use_animation", ($food === 1 ? 1 : 0)), // 2 is potion, but not now
					new ByteTag("allow_off_hand", $allow_off_hand),
					new ByteTag("can_destroy_in_creative", $can_destroy_in_creative),
					new ByteTag("creative_category", $creative_category),
					new ByteTag("hand_equipped", $hand_equipped),
					new IntTag("max_stack_size", $max_stack_size),
					new FloatTag("mining_speed", $mining_speed),
					new ByteTag("animates_in_toolbar", 1),
				])
			]),
			new ShortTag("minecraft:identifier", $runtimeId),
			new CompoundTag("minecraft:display_name", [
				new StringTag("value", $data["name"])
			]),
			new CompoundTag("minecraft:on_use", [
				new ByteTag("on_use", 1)
			]),
			new CompoundTag("minecraft:on_use_on", [
				new ByteTag("on_use_on", 1)
			])
		]);
		$durable = false;
		if(isset($data["durable"]) && (bool) ($data["durable"]) !== false){
			$nbt->getCompoundTag("components")->setTag(new CompoundTag("minecraft:durability", [
				new ShortTag("damage_change", 1),
				new ShortTag("max_durable", $data["max_durability"])
			]));
			$durable = true;
		}
		if($food === 1){
			$nbt->getCompoundTag("components")->setTag(new CompoundTag("minecraft:food", [
				new ByteTag("can_always_eat", $can_always_eat),
				new FloatTag("nutrition", $nutrition),
				new StringTag("saturation_modifier", "low")
			]));
			$nbt->getCompoundTag("components")->setTag(new CompoundTag("minecraft:use_duration", [
				new IntTag("value", 1)
			]));
		}
		$runtimeId = $id + ($id > 0 ? 5000 : -5000);
		$this->coreToNetValues[$id] = $runtimeId;
		$this->netToCoreValues[$runtimeId] = $id;

		$this->itemTypeEntries[] = $entry = new ItemTypeEntry($namespace, $runtimeId, true);

		try{
			if($durable){
				ItemFactory::registerItem(new CustomDurableItem($id, $meta, $name, $data["max_stack_size"], $data["max_durability"], $mining_speed));
			}elseif($food){
				ItemFactory::registerItem(new CustomFoodItem($id, $meta, $name, $data["max_stack_size"], $nutrition, $can_always_eat === 1, $saturation, $residue));
			}elseif($armor){
				ItemFactory::registerItem(new CustomArmorItem($id, $meta, $name, $data["max_stack_size"], $data["max_durability"], ($data["defence_point"] ?? 1)));
			}else{
				ItemFactory::registerItem(new CustomItem($id, $meta, $name, $data["max_stack_size"], $mining_speed));
			}
		}catch(Throwable $e){
			throw new AssumptionFailedError("Cannot register item $name($id:$meta): item is already registered or item id is out of range");
		}
		return $nbt;
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$player->sendDataPacket(clone $this->packet);
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof StartGamePacket){
			$packet->experiments = new Experiments([], true);
		}
	}

	public function validateData(array $data) : void{
		if(!isset($data["id"]) || !is_numeric($data["id"])){
			throw new AssumptionFailedError("Array with key \"id\" not found");
		}
		if(!isset($data["meta"]) || !is_numeric($data["meta"])){
			throw new AssumptionFailedError("Array with key \"meta\" not found");
		}
		if(!isset($data["namespace"])){
			throw new AssumptionFailedError("Array with key \"namespace\" not found");
		}
		if(!isset($data["name"])){
			throw new AssumptionFailedError("Array with key \"name\" not found");
		}
		if(!isset($data["texture"])){
			throw new AssumptionFailedError("Array with key \"texture\" not found");
		}
	}
}