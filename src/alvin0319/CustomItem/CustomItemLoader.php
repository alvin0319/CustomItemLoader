<?php

declare(strict_types=1);

namespace alvin0319\CustomItem;

use alvin0319\CustomItem\item\CustomDurableItem;
use alvin0319\CustomItem\item\CustomItem;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\ItemComponentPacket;
use pocketmine\network\mcpe\protocol\PacketViolationWarningPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\ItemComponentPacketEntry;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\plugin\PluginBase;
use ReflectionClass;

use function explode;
use function is_bool;

class CustomItemLoader extends PluginBase implements Listener{

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

		$coreToNetValues = $simpleCoreToNetMap->getValue(ItemTranslator::getInstance());
		$netToCoreValues = $simpleNetToCoreMap->getValue(ItemTranslator::getInstance());

		$ref_1 = new ReflectionClass(ItemTypeDictionary::class);
		$itemTypes = $ref_1->getProperty("itemTypes");
		$itemTypes->setAccessible(true);
		$intToStringIdMap = $ref_1->getProperty("intToStringIdMap");
		$stringToIntMap = $ref_1->getProperty("stringToIntMap");

		$intToStringIdMap->setAccessible(true);
		$stringToIntMap->setAccessible(true);

		$itemTypesValues = $itemTypes->getValue(ItemTypeDictionary::getInstance());

		//var_dump($coreToNetValues);
		//var_dump($netToCoreValues);

		foreach($this->getConfig()->get("items", []) as $itemData){
			foreach($needKeys as $key){
				if(!isset($itemData[$key])){
					$this->getLogger()->critical("Failed to find {$key}, skipping...");
					continue 2;
				}
			}
			$id = (int) $itemData["id"];
			$meta = (int) $itemData["meta"];

			$namespace = (string) $itemData["namespace"];

			$runtimeId = ($id << 16) | $meta;

			$itemTypesValues[] = $entry = new ItemTypeEntry($namespace, $runtimeId, true);

			$coreToNetValues[$entry->getNumericId()] = $runtimeId;
			$netToCoreValues[$runtimeId] = $entry->getNumericId();

			ItemFactory::registerItem((($itemData["durable"] ?? false) ? new CustomDurableItem($id, $meta, explode(":", $namespace)[1], $itemData["max_durability"]) : new CustomItem($id, $meta, explode(":", $namespace)[1])));
		}

		$simpleNetToCoreMap->setValue(ItemTranslator::getInstance(), $netToCoreValues);
		$simpleCoreToNetMap->setValue(ItemTranslator::getInstance(), $coreToNetValues);
		$itemTypes->setValue(ItemTypeDictionary::getInstance(), $itemTypesValues);
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();

		$entries = [];
		foreach($this->getConfig()->get("items") as $itemData){
			$id = (int) $itemData["id"];
			$meta = (int) $itemData["meta"];
			$runtimeId = ($id << 16) | $meta;
			$namespace = (string) $itemData["namespace"];
			$entries[] = new ItemComponentPacketEntry($itemData["namespace"], new CompoundTag("", [
				new CompoundTag("components", [
					new CompoundTag("item_properties", [
						new ByteTag("allow_off_hand", (int) ($itemData["properties"]["allow_offhand"] ?? false)),
						new IntTag("max_stack_size", (int) ($itemData["properties"]["max_stack_size"] ?? 64)),
						new ByteTag("hand_equipped", (int) ($itemData["properties"]["hand_equipped"] ?? true))
					]),
					new CompoundTag("minecraft:icon", [
						new StringTag("texture", $itemData["properties"]["texture"])
					]),
				]),
				new ShortTag("id", $runtimeId),
				new StringTag("name", $namespace)
			]));
		}

		$player->sendDataPacket(ItemComponentPacket::create($entries));
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();

		if($packet instanceof PacketViolationWarningPacket){
			$this->getLogger()->info("Packet violation warning packet received.");
			$this->getLogger()->info("Message: {$packet->getMessage()}");
			$this->getLogger()->info("Priority: {$packet->getSeverity()}");
			$this->getLogger()->info("Packet id: {$packet->getPacketId()}");
			$event->setCancelled();
		}
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof ResourcePackStackPacket && !is_bool($packet->experiments)){
			$pk = new \alvin0319\CustomItem\packet\ResourcePackStackPacket();
			$pk->experiments = true;
			$pk->resourcePackStack = $packet->resourcePackStack;
			$pk->behaviorPackStack = $packet->behaviorPackStack;
			$pk->mustAccept = $packet->mustAccept;
			$event->getPlayer()->sendDataPacket($pk);
			$event->setCancelled();
		}elseif($packet instanceof StartGamePacket){

		}
	}
}