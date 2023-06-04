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

use alvin0319\CustomItemLoader\item\properties\component\ArmorComponent;
use alvin0319\CustomItemLoader\item\properties\component\Component;
use alvin0319\CustomItemLoader\item\properties\component\CooldownComponent;
use alvin0319\CustomItemLoader\item\properties\component\DiggerComponent;
use alvin0319\CustomItemLoader\item\properties\component\DisplayNameComponent;
use alvin0319\CustomItemLoader\item\properties\component\DurableComponent;
use alvin0319\CustomItemLoader\item\properties\component\FoodComponent;
use alvin0319\CustomItemLoader\item\properties\component\IdentifierComponent;
use alvin0319\CustomItemLoader\item\properties\component\ItemPropertiesComponent;
use alvin0319\CustomItemLoader\item\properties\component\RenderOffsetsComponent;
use InvalidArgumentException;
use pocketmine\block\BlockToolType;
use pocketmine\data\bedrock\item\upgrade\LegacyItemIdToStringIdMap;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use function is_numeric;

final class CustomItemProperties{
	/** @var string */
	protected string $name;
	/** @var int */
	protected int $id;
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

	/** @var Component[] */
	private array $components = [];

	private CompoundTag $rootNBT;

	public function __construct(string $name, array $data){
		$this->name = $name;
		$this->parseData($data);
	}

	private function parseData(array $data) : void{
		if(!isset($data["namespace"])){
			throw new InvalidArgumentException("namespace is required");
		}
		if(!isset($data["texture"])){
			throw new InvalidArgumentException("texture is required");
		}
		$this->rootNBT = CompoundTag::create()
			->setTag(Component::TAG_COMPONENTS, CompoundTag::create());

		$id = ItemTypeIds::newId();

		$namespace = (string) $data["namespace"];

		$runtimeId = $id + ($id > 0 ? 5000 : -5000);

		$this->id = $id;
		$this->runtimeId = $runtimeId;
		$this->namespace = $namespace;

		$this->addComponent(new IdentifierComponent($runtimeId));
		$this->addComponent(new DisplayNameComponent($this->name));

		$itemPropertiesComponent = new ItemPropertiesComponent();
		$itemPropertiesComponent->setIcon($data["texture"], $namespace);
		$itemPropertiesComponent->addComponent(ItemPropertiesComponent::TAG_USE_DURATION, new IntTag(0));

		$trueTag = new ByteTag(1);

		if(isset($data["allow_off_hand"]) && $data["allow_off_hand"] === true){
			$itemPropertiesComponent->addComponent("allow_off_hand", $trueTag);
		}
		if(isset($data["can_destroy_in_creative"]) && $data["can_destroy_in_creative"] === true){
			$itemPropertiesComponent->addComponent("can_destroy_in_creative", $trueTag);
		}
		if(isset($data["creative_category"])){
			$itemPropertiesComponent->addComponent("creative_category", new IntTag($data["creative_category"]));
//			$this->setCreativeCategory($data["creative_category"]);
		}
		if(isset($data["creative_group"])){
			$itemPropertiesComponent->addComponent("creative_group", new StringTag($data["creative_group"]));
		}
		$handEquipped = false;
		if(isset($data["hand_equipped"])){
			$handEquipped = true;
			$itemPropertiesComponent->addComponent("hand_equipped", $trueTag);
		}
		if(isset($data["max_stack_size"])){
			$this->max_stack_size = (int)$data["max_stack_size"];
			$itemPropertiesComponent->addComponent("max_stack_size", new IntTag($this->max_stack_size));
		}
//		if(isset($data["mining_speed"])){
//			$this->setMiningSpeed($data["mining_speed"]);
//		}

		if(isset($data["food"]) && $data["food"] === true){
			if(!isset($data["nutrition"]) || !isset($data["saturation"]) || !isset($data["can_always_eat"])){
				throw new InvalidArgumentException("Food item must have nutrition, saturation, and can_always_eat");
			}
//			$this->setFood($data["food"], $data["nutrition"], $data["saturation"], $data["can_always_eat"]);
			$this->addComponent(new FoodComponent($data["can_always_eat"], $data["nutrition"], $data["saturation"]));
		}

//		if(isset($data["residue"])){
//			$this->setResidue(ItemFactory::getInstance()->get((int) $data["residue"]["id"], (int) ($data["residue"]["meta"] ?? 0)));
//		}

		if(isset($data["armor"]) && $data["armor"]){
			if(!isset($data["defence_points"]) || !isset($data["armor_slot"]) || !isset($data["armor_class"])){
				throw new InvalidArgumentException("Armor item must have defence_points, armor_slot, and armor_class");
			}
//			$this->setArmor(true, $data["armor_class"], $data["armor_slot"]);
//			$this->setDefencePoints($data["defence_points"]);
			$armor_slot_int = match ($data["armor_slot"]) {
				"helmet" => ArmorInventory::SLOT_HEAD,
				"chest" => ArmorInventory::SLOT_CHEST,
				"leggings" => ArmorInventory::SLOT_LEGS,
				"boots" => ArmorInventory::SLOT_FEET,
				default => throw new InvalidArgumentException("Unknown armor slot {$data["armor_slot"]} given.")
			};
			$this->addComponent(new ArmorComponent($data["armor_class"], $armor_slot_int)); // TODO: defence points
		}
		if(isset($data["foil"])){
//			$this->setFoil($data["foil"]);
			$itemPropertiesComponent->addComponent("foil", $trueTag);
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
//			$this->setDurable($data["durable"], $data["max_durability"]);
			$this->addComponent(new DurableComponent($data["max_durability"]));
		}
		if(isset($data["render_offset"])){
			$x = 16;
			$y = 16;
			if(isset($data["render_offset"]["x"]) && isset($data["render_offset"]["y"])){
				$x = $data["render_offset"]["x"];
				$y = $data["render_offset"]["y"];
			}elseif(isset($data["render_offset"]["size"])){
				$x = $data["render_offset"]["size"];
				$y = $data["render_offset"]["size"];
			}
			$this->addComponent(new RenderOffsetsComponent($x, $y, $handEquipped));
		}
		if(isset($data["cooldown"]) && is_numeric($data["cooldown"])){
//			$this->setCooldown($data["cooldown"]);
			$this->addComponent(new CooldownComponent($data["cooldown"]));
		}

		if(isset($data["dig"])){
			if(!isset($data["dig"]["block_tags"]) || !isset($data["dig"]["speed"])){
				throw new InvalidArgumentException("Property 'dig' must have block_tags and speed");
			}
			$this->addComponent(new DiggerComponent((int) $data["dig"]["speed"], $data["dig"]["block_tags"]));
			$this->mining_speed = (int) $data["dig"]["speed"];
		}

		$this->addComponent($itemPropertiesComponent);

		$legacyId = $data["id"] ?? -1;

		if($legacyId !== -1){
			LegacyItemIdToStringIdMap::getInstance()->add($this->namespace, $legacyId);
		}
	}

	public function addComponent(Component $component) : void{
		$this->components[$component->getName()] = $component;
		$component->buildComponent($this->rootNBT);
		$component->processComponent($this->rootNBT);
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

	public function getNbt(bool $rebuild = false) : CompoundTag{
		if($rebuild){
			$this->rootNBT = CompoundTag::create()
				->setTag(Component::TAG_COMPONENTS, CompoundTag::create());
			$components = $this->components;
			$this->components = [];
			foreach($components as $name => $component){
				$this->addComponent($component);
			}
		}
		return $this->rootNBT;
	}
}
