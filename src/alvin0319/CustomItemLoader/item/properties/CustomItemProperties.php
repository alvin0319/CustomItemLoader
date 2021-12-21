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

namespace alvin0319\CustomItemLoader\item\properties;

use InvalidArgumentException;
use pocketmine\block\BlockToolType;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use ReflectionClass;
use function in_array;

final class CustomItemProperties{
	/** @var string */
	protected string $name;
	/** @var int */
	protected int $id;
	/** @var int */
	protected int $meta;
	/** @var string */
	protected string $namespace;
	/** @var int */
	protected int $runtimeId;
	/** @var bool */
	protected bool $durable = false;
	/** @var int|null */
	protected ?int $max_durability = null;
	/** @var bool */
	protected bool $allow_off_hand = false;
	/** @var bool */
	protected bool $can_destroy_in_creative = false;
	/** @var int */
	protected int $creative_category = 1;
	/** @var bool */
	protected bool $hand_equipped = true;
	/** @var int */
	protected int $max_stack_size = 64;
	/** @var int */
	protected float $mining_speed = 1;
	/** @var bool */
	protected bool $food = false;
	/** @var bool */
	protected bool $can_always_eat = false;
	/** @var int|null */
	protected ?int $nutrition = null;
	/** @var float|null */
	protected ?float $saturation = null;
	/** @var Item|null */
	protected ?Item $residue = null;
	/** @var bool */
	protected bool $armor = false;
	/** @var int */
	protected int $defence_points;
	/** @var CompoundTag */
	protected CompoundTag $nbt;
	/** @var bool */
	protected bool $isBlock = false;
	/** @var int */
	protected int $blockId;
	/** @var bool */
	protected bool $tool = false;
	/** @var int */
	protected int $toolType = BlockToolType::NONE;
	/** @var int */
	protected int $toolTier = 0;

	protected bool $add_creative_inventory = false;

	protected int $attack_points = 0;

	protected int $foil;

	protected int $armorSlot = ArmorInventory::SLOT_HEAD;

	public function __construct(string $name, array $data){
		$this->name = $name;
		$this->parseData($data);
	}

	private function parseData(array $data) : void{
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
		$residue = isset($data["residue"]) ? ItemFactory::getInstance()->get((int) $data["residue"]["id"], (int) ($data["residue"]["meta"] ?? 0)) : ItemFactory::getInstance()->get(0);

		$armor = isset($data["armor"]) ? $data["armor"] : false;
		$defence_points = $data["defence_points"] ?? 0;
		$armor_slot = $data["armor_slot"] ?? "helmet";
		$armor_class = $data["armor_class"] ?? "diamond";

		$foil = (int) ($data["foil"] ?? 0);

		$armor_slot_int = match($armor_slot){
			"helmet" => ArmorInventory::SLOT_HEAD,
			"chest" => ArmorInventory::SLOT_CHEST,
			"leggings" => ArmorInventory::SLOT_LEGS,
			"boots" => ArmorInventory::SLOT_FEET,
			default => throw new InvalidArgumentException("Unknown armor slot $armor_slot given.")
		};
		$armor_slot_int += 2; // wtf mojang

		static $accepted_armor_values = ["gold", "none", "leather", "chain", "iron", "diamond", "elytra", "turtle", "netherite"];

		//static $accepted_armor_position_values = ["slot.armor.legs", "none", "slot.weapon.mainhand", "slot.weapon.offhand", "slot.armor.head", "slot.armor.chest", "slot.armor.feet", "slot.hotbar", "slot.inventory", "slot.enderchest", "slot.saddle", "slot.armor", "slot.chest"];

		$isBlock = $data["isBlock"] ?? false;

		$blockId = $isBlock ? $data["blockId"] : 0;

		$add_creative_inventory = ($data["add_creative_inventory"] ?? false);

		$attack_points = (int) ($data["attack_points"] ?? 1);

		$tool = $data["tool"] ?? false;
		$tool_type = $data["tool_type"] ?? BlockToolType::NONE;
		$tool_tier = $data["tool_tier"] ?? 0;

		$isBlock = $data["isBlock"] ?? false;

		$blockId = $isBlock ? $data["blockId"] : 0;

		$add_creative_inventory = ($data["add_creative_inventory"] ?? false);

		$tool = $data["tool"] ?? false;
		$tool_type = $data["tool_type"] ?? BlockToolType::NONE;
		$tool_tier = $data["tool_tier"] ?? 0;
		$nbt = CompoundTag::create()
			->setTag("components", CompoundTag::create()
				->setTag("item_properties", CompoundTag::create()
					->setInt("use_duration", 32)
					->setInt("use_animation", ($food === 1 ? 1 : 0)) // 2 is potion, but not now
					->setByte("allow_off_hand", $allow_off_hand)
					->setByte("can_destroy_in_creative", $can_destroy_in_creative)
					->setByte("creative_category", $creative_category)
					->setByte("hand_equipped", $hand_equipped)
					->setInt("max_stack_size", $max_stack_size)
					->setFloat("mining_speed", $mining_speed)
					->setTag("minecraft:icon", CompoundTag::create()
						->setString("texture", $data["texture"])
						->setString("legacy_id", $data["namespace"])
					)
				)
			)
			->setShort("minecraft:identifier", $runtimeId)
			->setTag("minecraft:display_name", CompoundTag::create()
				->setString("value", $data["name"])
			)
			->setTag("minecraft:on_use", CompoundTag::create()
				->setByte("on_use", 1)
			)->setTag("minecraft:on_use_on", CompoundTag::create()
				->setByte("on_use_on", 1)
			);

		if(isset($data["durable"]) && (bool) ($data["durable"]) !== false){
			$nbt->getCompoundTag("components")?->setTag("minecraft:durability", CompoundTag::create()
				->setShort("damage_change", 1)
				->setShort("max_durable", $data["max_durability"])
			);
			$this->durable = true;
			$this->max_durability = $data["max_durability"];
		}
		if($food === 1){
			$nbt->getCompoundTag("components")?->setTag("minecraft:food", CompoundTag::create()
				->setByte("can_always_eat", $can_always_eat)
				->setFloat("nutrition", $nutrition)
				->setString("saturation_modifier", "low")
			);
			$nbt->getCompoundTag("components")?->setTag("minecraft:use_duration", CompoundTag::create()
				->setInt("value", 1)
			);
			$this->food = true;
			$this->nutrition = $data["nutrition"];
			$this->can_always_eat = (bool) $can_always_eat;
			$this->saturation = $saturation;
			$this->residue = $residue;
		}

		if($armor){
			if(!in_array($armor_class, $accepted_armor_values, true)){
				throw new InvalidArgumentException("Armor class is invalid");
			}
			$nbt->getCompoundTag("components")?->setTag("minecraft:armor", CompoundTag::create()
				->setString("texture_type", $armor_class)
				->setInt("protection", 0)
			);
			$nbt->getCompoundTag("components")?->setTag("minecraft:wearable", CompoundTag::create()
				->setInt("slot", $armor_slot_int)
				->setByte("dispensable", 1)
			);
			/*
			// TODO: find out what does this do
			$nbt->getCompoundTag("components")?->getCompoundTag("item_properties")
				?->setString("enchantable_slot", match($armor_slot){
					"helmet" => "armor_helmet",
					"chest" => "armor_torso",
					"leggings" => "armor_legs",
					"boots" => "armor_feet",
					default => throw new AssumptionFailedError("Unknown armor type $armor_slot")
				});

			$nbt->getCompoundTag("components")?->getCompoundTag("item_properties")
				?->setString("enchantable_value", "10");
			*/
			/*
			$nbt->getCompoundTag("components")?->setTag(new CompoundTag("minecraft:durability", [
				new ShortTag("damage_change", 1),
				new ShortTag("max_durable", $data["max_durability"] ?? 64)
			]));
			*/
			$nbt->getCompoundTag("components")?->setTag("minecraft:durability", CompoundTag::create()
				->setShort("damage_change", 1)
				->setShort("max_durable", $data["max_durability"] ?? 64)
			);
			$this->durable = true;
			$this->max_durability = $data["max_durability"] ?? 64;

			$this->armorSlot = $armor_slot_int;
		}

		$runtimeId = $id + ($id > 0 ? 5000 : -5000);

		$this->id = $id;
		$this->runtimeId = $runtimeId;
		$this->meta = $meta;
		$this->namespace = $namespace;
		$this->allow_off_hand = (bool) $allow_off_hand;
		$this->can_destroy_in_creative = (bool) $can_destroy_in_creative;
		$this->creative_category = (int) $creative_category;
		$this->hand_equipped = (bool) $hand_equipped;
		$this->max_stack_size = $max_stack_size;
		$this->mining_speed = $mining_speed; // TODO: find out property for this

		$this->armor = $armor;
		$this->defence_points = $defence_points;

		$this->isBlock = $isBlock;
		$this->blockId = $blockId;

		$this->add_creative_inventory = $add_creative_inventory;

		$this->tool = $tool;
		$this->toolType = $tool_type;
		$this->toolTier = $tool_tier;

		$this->attack_points = $attack_points;

		$this->foil = $foil;

		$this->nbt = $nbt;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getNamespace() : string{
		return $this->namespace;
	}

	public function getId() : int{
		return $this->id;
	}

	public function getMeta() : int{
		return $this->meta;
	}

	public function getRuntimeId() : int{
		return $this->runtimeId;
	}

	public function getAllowOffhand() : bool{
		return $this->allow_off_hand;
	}

	public function getCanDestroyInCreative() : bool{
		return $this->can_destroy_in_creative;
	}

	public function getCreativeCategory() : int{
		return $this->creative_category;
	}

	public function getHandEquipped() : bool{
		return $this->hand_equipped;
	}

	public function getMaxStackSize() : int{
		return $this->max_stack_size;
	}

	public function getMiningSpeed() : float{
		return $this->mining_speed;
	}

	public function isFood() : bool{
		return $this->food;
	}

	public function getNutrition() : ?int{
		return $this->nutrition;
	}

	public function getSaturation() : ?float{
		return $this->saturation;
	}

	public function getCanAlwaysEat() : bool{
		return $this->can_always_eat;
	}

	public function getResidue() : ?Item{
		return $this->residue;
	}

	public function isDurable() : bool{
		return $this->durable;
	}

	public function getMaxDurability() : int{
		return $this->max_durability;
	}

	public function isArmor() : bool{
		return $this->armor;
	}

	public function getDefencePoints() : int{
		return $this->defence_points;
	}

	public function isBlock() : bool{
		return $this->isBlock;
	}

	public function getBlockId() : int{
		return $this->blockId;
	}

	public function getBlockToolType() : int{
		return $this->toolType;
	}

	public function getBlockToolHarvestLevel() : int{
		return $this->toolTier;
	}

	public function isTool() : bool{
		return $this->tool;
	}

	public function getAddCreativeInventory() : bool{
		return $this->add_creative_inventory;
	}

	public function getAttackPoints() : int{
		return $this->attack_points;
	}

	public function getNbt() : CompoundTag{
		return $this->nbt;
	}

	public function getArmorSlot() : int{
		return $this->armorSlot;
	}

	public static function withoutData() : CustomItemProperties{
		$class = new ReflectionClass(self::class);
		/** @var CustomItemProperties $newInstance */
		$newInstance = $class->newInstanceWithoutConstructor();
		return $newInstance;
	}
}
