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
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;

/**
 * This component "fixes" the render offset of the item as known as glitched size.
 * Note that this component is still needs to be investigated more.
 */
final class RenderOffsetsComponent extends Component{

	public const TAG_RENDER_OFFSETS = "minecraft:render_offsets";

	public function __construct(
		private readonly int $x,
		private readonly int $y,
		private readonly bool $handEquipped
	){
	}

	public function getName() : string{
		return "render_offsets";
	}

	public function buildComponent(CompoundTag $rootNBT) : void{
		$componentNBT = $rootNBT->getCompoundTag(self::TAG_COMPONENTS);
		if($componentNBT === null){
			throw new InvalidNBTStateException("Component tree is not built");
		}
		$componentNBT->setTag(self::TAG_RENDER_OFFSETS, CompoundTag::create());
	}

	public function processComponent(CompoundTag $rootNBT) : void{
		$renderOffsetsNBT = $rootNBT->getCompoundTag(self::TAG_COMPONENTS)?->getCompoundTag(self::TAG_RENDER_OFFSETS);
		if($renderOffsetsNBT === null){
			throw new InvalidNBTStateException("Component tree is not built");
		}
		[$x, $y, $z] = $this->calculateOffset($this->x, $this->y);
		$renderOffsetsNBT->setTag("main_hand", CompoundTag::create()
			->setTag("first_person", CompoundTag::create()
				->setTag("scale", new ListTag([
					new FloatTag($x),
					new FloatTag($y),
					new FloatTag($z)
				]))
			)->setTag("third_person", CompoundTag::create()
				->setTag("scale", new ListTag([
					new FloatTag($x),
					new FloatTag($y),
					new FloatTag($z)
				]))
			)
		)->setTag("off_hand", CompoundTag::create()
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
		);
	}

	private function calculateOffset(int $width, int $height) : array{
		if(!$this->handEquipped){
			[$x, $y, $z] = [0.075, 0.125, 0.075];
		}else{
			[$x, $y, $z] = [0.1, 0.1, 0.1];
		}
		$newX = $x / ($width / 16);
		$newY = $y / ($height / 16);
		$newZ = $z / ($width / 16);
		return [$newX, $newY, $newZ];
	}
}