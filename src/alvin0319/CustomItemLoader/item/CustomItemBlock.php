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

namespace alvin0319\CustomItemLoader\item;

use alvin0319\CustomItemLoader\item\properties\CustomItemProperties;
use pocketmine\block\BlockFactory;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemIdentifier;

final class CustomItemBlock extends ItemBlock{
	use CustomItemTrait {
		__construct as CustomItemTrait___construct;
	}

	public function __construct(string $name, array $data){
		$this->properties = new CustomItemProperties($name, $data);
		parent::__construct(new ItemIdentifier($this->properties->getId(), $this->properties->getMeta()), BlockFactory::getInstance()->get($this->properties->getBlockId(), $this->properties->getMeta()));
		$this->name = $this->properties->getName();
	}
}