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

use pocketmine\nbt\tag\CompoundTag;

/**
 * Base class for components.
 */
abstract class Component{

	public const TAG_COMPONENTS = "components";

	abstract public function getName() : string;

	/**
	 * Builds the basic component tree which will be used to process the component.
	 */
	public function buildComponent(CompoundTag $rootNBT) : void{
	}

	/**
	 * Processes the component.
	 * This method assumes the component tree is already built.
	 */
	abstract public function processComponent(CompoundTag $rootNBT) : void;
}