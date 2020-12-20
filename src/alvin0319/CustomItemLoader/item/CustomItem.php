<?php

declare(strict_types=1);

namespace alvin0319\CustomItemLoader\item;

use pocketmine\item\Item;

class CustomItem extends Item{

	protected $maxStackSize = 64;

	public function __construct(int $id, int $meta = 0, string $name = "Unknown", int $maxStackSize = 64){
		parent::__construct($id, $meta, $name);
		$this->maxStackSize = $maxStackSize;
	}

	public function getMaxStackSize() : int{
		return $this->maxStackSize;
	}
}