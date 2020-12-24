<?php

declare(strict_types=1);

namespace alvin0319\CustomItemLoader\item;

use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\Durable;

class CustomDurableItem extends Durable{
	/** @var int */
	protected $maxDurable = -1;
	/** @var int */
	protected $maxStackSize = 64;
	/** @var float */
	protected $miningSpeed = 1;

	public function __construct(int $id, int $meta, string $name, int $maxStackSize = 64, int $maxDurable = 64, float $miningSpeed = 1){
		parent::__construct($id, $meta, $name);
		$this->maxDurable = $maxDurable;
		$this->maxStackSize = $maxStackSize;
		if($miningSpeed <= 0){
			throw new InvalidArgumentException("Mining speed must larger than 0");
		}
		$this->miningSpeed = $miningSpeed;
	}

	public function getMaxDurability() : int{
		return $this->maxDurable;
	}

	public function getMaxStackSize() : int{
		return $this->maxStackSize;
	}

	public function onDestroyBlock(Block $block) : bool{
		return $this->applyDamage(1);
	}

	public function onAttackEntity(Entity $victim) : bool{
		return $this->applyDamage(1);
	}

	public function getMiningEfficiency(Block $block) : float{
		return $this->miningSpeed;
	}
}