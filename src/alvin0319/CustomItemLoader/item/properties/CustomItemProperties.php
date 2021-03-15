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

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use ReflectionClass;

final class CustomItemProperties{
	/** @var string */
	protected $name;
	/** @var int */
	protected $id;
	/** @var int */
	protected $meta;
	/** @var string */
	protected $namespace;
	/** @var int */
	protected $runtimeId;
	/** @var bool */
	protected $durable = false;
	/** @var int|null */
	protected $max_durability = null;
	/** @var bool */
	protected $allow_off_hand = false;
	/** @var bool */
	protected $can_destroy_in_creative = false;
	/** @var int */
	protected $creative_category = 1;
	/** @var bool */
	protected $hand_equipped = true;
	/** @var int */
	protected $max_stack_size = 64;
	/** @var int */
	protected $mining_speed = 1;
	/** @var bool */
	protected $food = false;
	/** @var bool */
	protected $can_always_eat = false;
	/** @var int|null */
	protected $nutrition = null;
	/** @var float|null */
	protected $saturation = null;
	/** @var Item|null */
	protected $residue = null;
	/** @var bool */
	protected $armor = false;
	/** @var int */
	protected $defence_points;
	/** @var CompoundTag */
	protected $nbt;

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
		$residue = isset($data["residue"]) ? ItemFactory::get((int) $data["residue"]["id"], (int) ($data["residue"]["meta"] ?? 0)) : ItemFactory::get(0);

		$armor = isset($data["armor"]) ? $data["armor"] : false;
		$defence_points = $data["defence_points"] ?? 0;

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
				]),
				new CompoundTag("minecraft:render_offsets", [
					new CompoundTag("main_hand", [
						new CompoundTag("first_person", [
							new ListTag("position", [
								new FloatTag("", 0),
								new FloatTag("", 0),
								new FloatTag("", 0)
							]),
							new ListTag("rotation", [
								new FloatTag("", 0),
								new FloatTag("", 0),
								new FloatTag("", 0)
							]),
							new ListTag("scale", [
								new FloatTag("", 0.1),
								new FloatTag("", 0.1),
								new FloatTag("", 0.1)
							])
						]),
						new CompoundTag("third_person", [
							new ListTag("position", [
								new FloatTag("", 0),
								new FloatTag("", 0),
								new FloatTag("", 0)
							]),
							new ListTag("rotation", [
								new FloatTag("", 0),
								new FloatTag("", 0),
								new FloatTag("", 0)
							]),
							new ListTag("scale", [
								new FloatTag("", 0.1),
								new FloatTag("", 0.1),
								new FloatTag("", 0.1)
							])
						])
					]),
					new CompoundTag("off_hand", [
						new CompoundTag("first_person", [
							new CompoundTag("position", [
								new FloatTag("", 0),
								new FloatTag("", 0),
								new FloatTag("", 0)
							]),
							new CompoundTag("rotation", [
								new FloatTag("", 0),
								new FloatTag("", 0),
								new FloatTag("", 0)
							]),
							new CompoundTag("scale", [
								new FloatTag("", 0.1),
								new FloatTag("", 0.1),
								new FloatTag("", 0.1)
							])
						]),
					])
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
		if(isset($data["durable"]) && (bool) ($data["durable"]) !== false){
			$nbt->getCompoundTag("components")->setTag(new CompoundTag("minecraft:durability", [
				new ShortTag("damage_change", 1),
				new ShortTag("max_durable", $data["max_durability"])
			]));
			$this->durable = true;
			$this->max_durability = $data["max_durability"];
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
			$this->food = true;
			$this->nutrition = $data["nutrition"];
			$this->can_always_eat = (bool) $can_always_eat;
			$this->saturation = $saturation;
			$this->residue = $residue;
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

	public function getNbt() : CompoundTag{
		return $this->nbt;
	}

	public static function withoutData() : CustomItemProperties{
		$class = new ReflectionClass(self::class);
		/** @var CustomItemProperties $newInstance */
		$newInstance = $class->newInstanceWithoutConstructor();
		return $newInstance;
	}
}