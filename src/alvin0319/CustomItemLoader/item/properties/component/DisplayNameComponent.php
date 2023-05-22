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

/**
 * This component tells the client to set the name of item.
 * This is essential component for all custom items.
 */
final class DisplayNameComponent extends Component{

	public const TAG_DISPLAY_NAME = "minecraft:display_name";

	public function __construct(private readonly string $displayName){ }

	public function getName() : string{
		return "texture";
	}

	public function buildComponent(CompoundTag $rootNBT) : void{
		$componentTag = $rootNBT->getCompoundTag(Component::TAG_COMPONENTS);
		if($componentTag === null){
			throw new InvalidNBTStateException("Component tree is not built");
		}
		$componentTag->setTag(self::TAG_DISPLAY_NAME, CompoundTag::create());
	}

	public function processComponent(CompoundTag $rootNBT) : void{
		$displayNameTag = $rootNBT->getCompoundTag(Component::TAG_COMPONENTS)?->getCompoundTag(self::TAG_DISPLAY_NAME);
		if($displayNameTag === null){
			throw new InvalidNBTStateException("Component tree is not built");
		}
		$displayNameTag->setString("value", $this->displayName);
	}
}