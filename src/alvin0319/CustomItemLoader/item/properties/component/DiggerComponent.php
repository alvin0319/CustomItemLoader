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

namespace alvin0319\CustomItemLoader\item\properties\component;

use alvin0319\CustomItemLoader\util\InvalidNBTStateException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use function implode;
use function in_array;

/**
 * This component modifies items destroy speed.
 * {@link DiggerComponent::ACCEPTED_BLOCK_TAGS ACCEPTED_BLOCK_TAGS} has the list of accepted block tags, which will only be applied to modify destroy speed.
 */
final class DiggerComponent extends Component{

	// https://wiki.bedrock.dev/blocks/block-tags.html
	public const ACCEPTED_BLOCK_TAGS = [
		"wood", // all wood blocks
		"pumpkin", // pumpkin, carved pumpkin, jack o'lantern
		"plant", // grasses, saplings, flowers
		"stone", // all stone blocks including cobblestone
		"metal", // Block of iron, gold, caldron, Iron bars
		"diamond_pick_diggable", // diamond pickaxe can dig
		"gold_pick_diggable", // gold pickaxe can dig
		"iron_pick_diggable", // iron pickaxe can dig
		"stone_pick_diggable", // stone pickaxe can dig
		"wood_pick_diggable", // wooden pickaxe can dig
		"dirt", // Farmland
		"sand", // sand, red sand
		"gravel", // gravel
		"snow", // snow related blocks
		"rail", // all rail blocks
		"water", // water (wtf??)
		"mob_spawner", // monster spawner
		"lush_plants_replaceable", // TODO: find out what this is for
		"azalea_log_replaceable", // TODO: find out what this is for
		"not_feature_replaceable", // chest, bedrock, end portal frame, mob spawner (maybe non-replaceable blocks?)
		"text_sign", // all kind of signs
		"minecraft:crop", // beetroot, carrot, potato, wheat (wtf mojang, why this only has minecraft: prefix...)
		"fertilize_area" // All types of Flowers, except Tall Flowers & Wither Rose; Crimson Nylium, Warped Nylium, Grass, Moss
	];

	public const TAG_DIGGER = "minecraft:digger";

	public const TAG_USE_EFFICIENCY = "use_efficiency";
	public const TAG_DESTROY_SPEEDS = "destroy_speeds";

	public function __construct(
		private readonly int $speed,
		private readonly array $blockTags = []
	){
	}

	public function getName() : string{
		return "digger";
	}

	public function buildComponent(CompoundTag $rootNBT) : void{
		$componentNBT = $rootNBT->getCompoundTag(self::TAG_COMPONENTS);
		if($componentNBT === null){
			throw new InvalidNBTStateException("Component tree is not built");
		}
		$componentNBT->setTag(self::TAG_DIGGER, CompoundTag::create()
			->setByte(self::TAG_USE_EFFICIENCY, 1)
			->setTag(self::TAG_DESTROY_SPEEDS, new ListTag([]))
		);
	}

	public function processComponent(CompoundTag $rootNBT) : void{
		$diggerNBT = $rootNBT->getCompoundTag(self::TAG_COMPONENTS)?->getCompoundTag(self::TAG_DIGGER);
		if($diggerNBT === null){
			throw new InvalidNBTStateException("Component tree is not built");
		}
		$destroySpeeds = $diggerNBT->getListTag(self::TAG_DESTROY_SPEEDS);
		if($destroySpeeds === null){
			throw new InvalidNBTStateException("Component tree is not built");
		}
		foreach($this->blockTags as $tag){
			if(!in_array($tag, self::ACCEPTED_BLOCK_TAGS, true)){
				throw new \InvalidArgumentException("Invalid block tag $tag");
			}
		}
		$this->addDestroySpeed($destroySpeeds, $this->blockTags, $this->speed);
	}

	public function addDestroySpeed(ListTag $tag, array $blockTags, int $speed) : void{
		$tag->push(
			CompoundTag::create()
				->setTag("block", CompoundTag::create()
					->setString("tags", "q.any_tag('" . implode("', '", $blockTags) . "')")
				)
				->setInt("speed", $speed)
		);
	}
}