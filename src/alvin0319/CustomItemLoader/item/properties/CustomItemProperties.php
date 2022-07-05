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
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\utils\AssumptionFailedError;
use ReflectionClass;
use function in_array;
use function is_numeric;

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
	/** @var float */
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

	private int $cooldown = 0;

	public function __construct(string $name, array $data){
		$this->name = $name;
		$this->parseData($data);
	}

	private function parseData(array $data) : void{
		if(!isset($data["id"])){
			throw new InvalidArgumentException("id is required");
		}
		if(!isset($data["meta"])){
			throw new InvalidArgumentException("meta is required");
		}
		if(!isset($data["namespace"])){
			throw new InvalidArgumentException("namespace is required");
		}
		if(!isset($data["texture"])){
			throw new InvalidArgumentException("texture is required");
		}
		$id = (int) $data["id"];
		$meta = (int) $data["meta"];

		$namespace = (string) $data["namespace"];

		$runtimeId = $id + ($id > 0 ? 5000 : -5000);

		$this->id = $id;
		$this->runtimeId = $runtimeId;
		$this->meta = $meta;
		$this->namespace = $namespace;

		$this->buildBaseComponent($data["texture"], $namespace, $runtimeId, $this->name);

		if(isset($data["allow_off_hand"])){
			$this->setAllowOffhand($data["allow_off_hand"]);
		}
		if(isset($data["can_destroy_in_creative"])){
			$this->setCanDestroyInCreative($data["can_destroy_in_creative"]);
		}
		if(isset($data["creative_category"])){
			$this->setCreativeCategory($data["creative_category"]);
		}
		if(isset($data["hand_equipped"])){
			$this->setHandEquipped($data["hand_equipped"]);
		}
		if(isset($data["max_stack_size"])){
			$this->setMaxStackSize($data["max_stack_size"]);
		}
		if(isset($data["mining_speed"])){
			$this->setMiningSpeed($data["mining_speed"]);
		}

		if(isset($data["food"]) && $data["food"]){
			if(!isset($data["nutrition"]) || !isset($data["saturation"]) || !isset($data["can_always_eat"])){
				throw new InvalidArgumentException("Food item must have nutrition, saturation, and can_always_eat");
			}
			$this->setFood($data["food"], $data["nutrition"], $data["saturation"], $data["can_always_eat"]);
		}

		if(isset($data["residue"])){
			$this->setResidue(ItemFactory::getInstance()->get((int) $data["residue"]["id"], (int) ($data["residue"]["meta"] ?? 0)));
		}

		if(isset($data["armor"]) && $data["armor"]){
			if(!isset($data["defence_points"]) || !isset($data["armor_slot"]) || !isset($data["armor_class"])){
				throw new InvalidArgumentException("Armor item must have defence_points, armor_slot, and armor_class");
			}
			$this->setArmor(true, $data["armor_slot"], $data["armor_class"]);
			$this->setDefencePoints($data["defence_points"]);
		}
		if(isset($data["foil"])){
			$this->setFoil($data["foil"]);
		}
		if(isset($data["add_creative_inventory"])){
			$this->setAddCreativeInventory($data["add_creative_inventory"]);
		}
		if(isset($data["attack_points"])){
			$this->setAttackPoints($data["attack_points"]);
		}
		if(isset($data["tool"])){
			if(!isset($data["tool_type"]) || !isset($data["tool_tier"])){
				throw new InvalidArgumentException("Tool item must have tool_type and tool_tier");
			}
			$this->setTool($data["tool"]);
			$this->setBlockToolType($data["tool_type"]);
			$this->setBlockToolHarvestLevel($data["tool_tier"]);
		}
		if(isset($data["durable"])){
			if(!isset($data["max_durability"])){
				throw new InvalidArgumentException("Durable item must have max_durability");
			}
			$this->setDurable($data["durable"], $data["max_durability"]);
		}
		if(isset($data["render_offset"])){
			if(!isset($data["render_offset"]["size"])){
				throw new InvalidArgumentException("Render offset item must have size");
			}
			$this->setRenderOffsets($data["render_offset"]["size"]);
		}
		if(isset($data["cooldown"]) && is_numeric($data["cooldown"])){
			$this->setCooldown($data["cooldown"]);
		}
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
		return $this->max_durability ?? 64;
	}

	public function isArmor() : bool{
		return $this->armor;
	}

	public function getDefencePoints() : int{
		return $this->defence_points;
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

	public function getCooldown() : int{
		return $this->cooldown;
	}

	public function getFoil() : bool{
		return $this->foil === 1;
	}

	/**
	 * Marks this item as a durable.
	 *
	 * @param bool $durable
	 * @param int  $maxDurability
	 *
	 * @return void
	 */
	public function setDurable(bool $durable, int $maxDurability = 0) : void{
		$this->durable = $durable;
		if($this->durable){
			$this->max_durability = $maxDurability;
			$this->nbt->getCompoundTag("components")?->setTag("minecraft:durability", CompoundTag::create()
				->setTag("damage_chance", CompoundTag::create()
					->setInt("min", 100) // maybe make this a config value
					->setInt("max", 100) // maybe make this a config value
				)
				->setInt("max_durability", $maxDurability)
			);
		}
	}

	/**
	 * Sets the max durability of the item.
	 *
	 * This does not automatically set the item component, use {@see setDurable()} for that
	 *
	 * @param int $max_durability
	 *
	 * @return void
	 */
	public function setMaxDurability(int $max_durability) : void{
		$this->max_durability = $max_durability;
	}

	/**
	 * Sets whether this item is equitable in the offhand slot.
	 *
	 * @param bool $allow_off_hand
	 *
	 * @return void
	 */
	public function setAllowOffhand(bool $allow_off_hand) : void{
		$this->allow_off_hand = $allow_off_hand;
		$this->nbt->getCompoundTag("components")?->getCompoundTag("item_properties")?->setByte("allow_off_hand", $allow_off_hand ? 1 : 0);
	}

	/**
	 * Sets whether this item is displayed like sword. (usually this is used for swords, armors, or tools)
	 *
	 * @param bool $hand_equipped
	 *
	 * @return void
	 */
	public function setHandEquipped(bool $hand_equipped) : void{
		$this->hand_equipped = $hand_equipped;
		$this->nbt->getCompoundTag("components")?->getCompoundTag("item_properties")?->setByte("hand_equipped", $hand_equipped ? 1 : 0);
	}

	/**
	 * Sets whether this item can break blocks in creative mode.
	 *
	 * @param bool $can_destroy_in_creative
	 *
	 * @return void
	 */
	public function setCanDestroyInCreative(bool $can_destroy_in_creative) : void{
		$this->can_destroy_in_creative = $can_destroy_in_creative;
		$this->nbt->getCompoundTag("components")?->getCompoundTag("item_properties")?->setByte("can_destroy_in_creative", $can_destroy_in_creative ? 1 : 0);
	}

	/**
	 * Sets the creative category of this item.
	 *
	 * @param int $creative_category
	 *
	 * @return void
	 */
	public function setCreativeCategory(int $creative_category) : void{
		$this->creative_category = $creative_category;
		$this->nbt->getCompoundTag("components")?->getCompoundTag("item_properties")?->setInt("creative_category", $creative_category);
	}

	/**
	 * Sets the mining speed of item
	 *
	 * @param float $mining_speed
	 *
	 * @return void
	 */
	public function setMiningSpeed(float $mining_speed) : void{
		$this->mining_speed = $mining_speed;
		$this->nbt->getCompoundTag("components")?->getCompoundTag("item_properties")?->setFloat("mining_speed", $mining_speed);
	}

	/**
	 * Mark item as armor and set its slot.
	 *
	 * @param bool   $armor
	 * @param string $armorClass
	 * @param string $armorSlot
	 *
	 * @return void
	 */
	public function setArmor(bool $armor, string $armorClass, string $armorSlot) : void{
		$this->armor = $armor;
		$armor_slot_int = match ($armorSlot) {
			"helmet" => ArmorInventory::SLOT_HEAD,
			"chest" => ArmorInventory::SLOT_CHEST,
			"leggings" => ArmorInventory::SLOT_LEGS,
			"boots" => ArmorInventory::SLOT_FEET,
			default => throw new InvalidArgumentException("Unknown armor slot $armorSlot given.")
		};

		static $acceptedArmorValues = ["gold", "none", "leather", "chain", "iron", "diamond", "elytra", "turtle", "netherite"];

		static $armorSlotToStringMap = [
//			"none",
//			"slot.weapon.mainhand",
//			"slot.weapon.offhand",
			ArmorInventory::SLOT_HEAD => "slot.armor.head",
			ArmorInventory::SLOT_CHEST => "slot.armor.chest",
			ArmorInventory::SLOT_LEGS => "slot.armor.legs",
			ArmorInventory::SLOT_FEET => "slot.armor.feet",
//			"slot.hotbar",
//			"slot.inventory",
//			"slot.enderchest",
//			"slot.saddle",
//			"slot.armor",
//			"slot.chest"
		];
		if(!in_array($armorClass, $acceptedArmorValues, true)){
			throw new InvalidArgumentException("Armor class is invalid");
		}
		$this->nbt->getCompoundTag("components")?->setTag("minecraft:armor", CompoundTag::create()
			->setString("texture_type", $armorClass)
			->setInt("protection", 0)
		);
		$this->nbt->getCompoundTag("components")?->setTag("minecraft:wearable", CompoundTag::create()
			->setString("slot", $armorSlotToStringMap[$armor_slot_int] ?? throw new AssumptionFailedError("Unknown armor slot type"))
			->setByte("dispensable", 1)
		);
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
	}

	/**
	 * Sets the slot of armor (0-3)
	 *
	 * This does not automatically set the item component, use {@see setArmor()} for that
	 *
	 * @param int $armorSlot
	 *
	 * @return void
	 */
	public function setArmorSlot(int $armorSlot) : void{
		$this->armorSlot = $armorSlot;
	}

	/**
	 * Sets the cooldown of the item
	 *
	 * @param int $cooldown
	 *
	 * @return void
	 */
	public function setCooldown(int $cooldown) : void{
		$this->cooldown = $cooldown;
		if($this->cooldown > 0){
			$this->nbt->getCompoundTag("components")?->setTag("minecraft:cooldown", CompoundTag::create()
				->setString("category", "attack")
				->setFloat("duration", $this->cooldown / 20)
			);
		}
	}

	/**
	 * Marks item as a foil.
	 *
	 * @param bool $foil
	 *
	 * @return void
	 */
	public function setFoil(bool $foil) : void{
		$this->foil = $foil ? 1 : 0;

		$this->nbt->getCompoundTag("components")?->getCompoundTag("item_properties")
			?->setByte("foil", $foil ? 1 : 0);
	}

	/**
	 * Marks item as food.
	 *
	 * @param bool  $food
	 * @param int   $nutrition
	 * @param float $saturation
	 * @param bool  $canAlwaysEat
	 *
	 * @return void
	 */
	public function setFood(bool $food, int $nutrition = 0, float $saturation = 0, bool $canAlwaysEat = false) : void{
		$this->food = $food;
		if($this->food){
			if($this->durable){
				throw new AssumptionFailedError("Food cannot be durable");
			}
			$this->nbt->getCompoundTag("components")?->setTag("minecraft:food", CompoundTag::create()
				->setByte("can_always_eat", $canAlwaysEat ? 1 : 0)
				->setInt("nutrition", $nutrition)
				->setFloat("saturation_modifier", 0.6)
			);
			$this->saturation = $saturation;
			$this->nutrition = $nutrition;
		}
	}

	/**
	 * Sets the nutrition of the food item.
	 *
	 * This does not automatically set the item component, use {@see setFood()} for that
	 *
	 * @param int $nutrition
	 *
	 * @return void
	 */
	public function setNutrition(int $nutrition) : void{
		$this->nutrition = $nutrition;
	}

	/**
	 * Sets the saturation of the food item.
	 *
	 * This does not automatically set the item component, use {@see setFood()} for that
	 *
	 * @param float $saturation
	 *
	 * @return void
	 */
	public function setSaturation(float $saturation) : void{
		$this->saturation = $saturation;
	}

	/**
	 * Sets whether the food item can be eaten even on the max saturation.
	 *
	 * This does not automatically set the item component, use {@see setFood()} for that
	 *
	 * @param bool $can_always_eat
	 *
	 * @return void
	 */
	public function setCanAlwaysEat(bool $can_always_eat) : void{
		$this->can_always_eat = $can_always_eat;
	}

	/**
	 * Sets the residue of the food item.
	 *
	 * @param Item $residue
	 *
	 * @return void
	 */
	public function setResidue(Item $residue) : void{
		$this->residue = $residue;
	}

	/**
	 * Sets the defence point of the item.
	 *
	 * @param int $defence_points
	 *
	 * @return void
	 */
	public function setDefencePoints(int $defence_points) : void{
		$this->defence_points = $defence_points;
	}

	/**
	 * Sets the tool type of the item.
	 *
	 * @param int $toolType
	 *
	 * @return void
	 */
	public function setBlockToolType(int $toolType) : void{
		$this->toolType = $toolType;
	}

	/**
	 * Sets the harvest level of item.
	 *
	 * @param int $toolTier
	 *
	 * @return void
	 */
	public function setBlockToolHarvestLevel(int $toolTier) : void{
		$this->toolTier = $toolTier;
	}

	/**
	 * Marks item as a tool.
	 *
	 * @param bool $tool
	 *
	 * @return void
	 */
	public function setTool(bool $tool) : void{
		$this->tool = $tool;
	}

	/**
	 * Sets whether add this item to creative inventory or not.
	 *
	 * @param bool $add_creative_inventory
	 *
	 * @return void
	 */
	public function setAddCreativeInventory(bool $add_creative_inventory) : void{
		$this->add_creative_inventory = $add_creative_inventory;
	}

	/**
	 * Sets the attack point (damage) of item
	 *
	 * @param int $attack_points
	 *
	 * @return void
	 */
	public function setAttackPoints(int $attack_points) : void{
		$this->attack_points = $attack_points;
	}

	/**
	 * Sets the render offsets of item
	 *
	 * Usually used to manually set the render offset when item texture is not following official minecraft PNG size.
	 *
	 * @param int $pngSize
	 *
	 * @return void
	 */
	public function setRenderOffsets(int $pngSize) : void{
		[$x, $y, $z] = $this->calculateOffset($pngSize);
		// TODO: Find out rotation and position formula
		$this->nbt->getCompoundTag("components")?->setTag("minecraft:render_offsets", CompoundTag::create()
			->setTag("main_hand", CompoundTag::create()
				->setTag("first_person", CompoundTag::create()
					->setTag("scale", new ListTag([
						new FloatTag($x),
						new FloatTag($y),
						new FloatTag($z)
					]))
				)
				->setTag("third_person", CompoundTag::create()
					->setTag("scale", new ListTag([
						new FloatTag($x),
						new FloatTag($y),
						new FloatTag($z)
					]))
				)
			)
			->setTag("off_hand", CompoundTag::create()
				->setTag("first_person", CompoundTag::create()
					->setTag("scale", new ListTag([
						new FloatTag($x),
						new FloatTag($y),
						new FloatTag($z)
					]))
				)
				->setTag("third_person", CompoundTag::create()
					->setTag("scale", new ListTag([
						new FloatTag($x),
						new FloatTag($y),
						new FloatTag($z)
					]))
				)
			)
		);
	}

	private function setMaxStackSize(int $max_stack_size) : void{
		$this->max_stack_size = $max_stack_size;
		$this->nbt->getCompoundTag("components")?->getCompoundTag("item_properties")
			->setInt("max_stack_size", $max_stack_size);
	}

	public static function withoutData() : CustomItemProperties{
		$class = new ReflectionClass(self::class);
		/** @var CustomItemProperties $newInstance */
		$newInstance = $class->newInstanceWithoutConstructor();
		return $newInstance;
	}

	private function calculateOffset(int $size) : array{
		if(!$this->hand_equipped){
			[$x, $y, $z] = [0.075, 0.125, 0.075];
		}else{
			[$x, $y, $z] = [0.1, 0.1, 0.1];
		}
		$newX = $x / ($size / 16);
		$newY = $y / ($size / 16);
		$newZ = $z / ($size / 16);
		return [$newX, $newY, $newZ];
	}

	private function buildBaseComponent(string $texture, string $namespace, int $runtimeId, string $name) : void{
		$this->nbt = CompoundTag::create()
			->setTag("components", CompoundTag::create()
				->setTag("item_properties", CompoundTag::create()
					->setInt("use_duration", 32)
					->setTag("minecraft:icon", CompoundTag::create()
						->setString("texture", $texture)
						->setString("legacy_id", $namespace)
					)
				)
			)
			->setShort("minecraft:identifier", $runtimeId)
			->setTag("minecraft:display_name", CompoundTag::create()
				->setString("value", $name)
			);
	}
}
