<?php

declare(strict_types=1);

namespace alvin0319\CustomItemLoader\item;

use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\item\Item;

class CustomItem extends Item{
	/** @var int */
	protected $maxStackSize = 64;
	/** @var float */
	protected $miningSpeed = 1;

	public function __construct(int $id, int $meta = 0, string $name = "Unknown", int $maxStackSize = 64, float $miningSpeed = 1){
		parent::__construct($id, $meta, $name);
		$this->maxStackSize = $maxStackSize;
		if($miningSpeed <= 0){
			throw new InvalidArgumentException("Mining speed must larger than 0");
		}
		$this->miningSpeed = $miningSpeed;
	}

	public function getMaxStackSize() : int{
		return $this->maxStackSize;
	}

	public function getMiningEfficiency(Block $block) : float{
		return $this->miningSpeed;
	}
}