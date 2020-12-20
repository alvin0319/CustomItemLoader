<?php

declare(strict_types=1);

namespace alvin0319\CustomItemLoader;

use alvin0319\CustomItemLoader\item\CustomDurableItem;
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
use ReflectionClass;

use function assert;
use function is_array;

class CustomItemLoader extends PluginBase implements Listener{
	/** @var ItemComponentPacket */
	protected $packet;

	protected $netToCoreValues = [];

	protected $coreToNetValues = [];

	protected $itemTypeEntries = [];

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveDefaultConfig();
		$needKeys = [
			"id",
			"meta",
			"namespace"
		];
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
			$packetEntries[] = new ItemComponentPacketEntry($itemData["namespace"], $nbt)
		}

		$simpleNetToCoreMap->setValue(ItemTranslator::getInstance(), $this->netToCoreValues);
		$simpleCoreToNetMap->setValue(ItemTranslator::getInstance(), $this->coreToNetValues);
		$itemTypes->setValue(ItemTypeDictionary::getInstance(), $this->itemTypeEntries);

		$this->packet = ItemComponentPacket::create($packetEntries);
	}

	public function parseTag(string $name, array $data) : CompoundTag{
		assert(isset($data["components"]) === false || is_array($data["components"]) === false, "Array with key \"components\" not found");
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

		$nbt = new CompoundTag("", [
			new CompoundTag("components", [
				new ByteTag("allow_off_hand", $allow_off_hand),
				new ByteTag("can_destroy_in_creative", $can_destroy_in_creative),
				new ByteTag("creative_category", $creative_category),
				new ByteTag("hand_equipped", $hand_equipped),
				new IntTag("max_stack_size", $max_stack_size),
				new FloatTag("mining_speed", $mining_speed),
				new ByteTag("animates_in_toolbar", 1),
				new CompoundTag("minecraft:icon", [
					new StringTag("texture", $data["texture"])
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
			$nbt->setTag(new CompoundTag("minecraft:durability", [
				new ShortTag("damage_change", 1),
				new ShortTag("max_durable", $data["max_durability"])
			]));
			$durable = true;
		}
		$runtimeId = $id + ($id > 0 ? 5000 : -5000);
		$this->coreToNetValues[$id] = $runtimeId;
		$this->netToCoreValues[$runtimeId] = $id;

		$this->itemTypeEntries[] = $entry = new ItemTypeEntry($namespace, $runtimeId, true);

		if($durable){
			ItemFactory::registerItem(new CustomDurableItem($id, $meta, $name, $data["max_stack_size"], $data["max_durability"]));
		}else{
			ItemFactory::registerItem(new CustomItem($id, $meta, $name, $data["max_stack_size"]));
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
}