<?php

declare(strict_types=1);

namespace alvin0319\CustomItemLoader\item;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\Durable;

class CustomDurableItem extends Durable{

	protected $maxDurable = -1;

	protected $maxStackSize = 64;

	public function __construct(int $id, int $meta, string $name, int $maxStackSize, int $maxDurable){
		parent::__construct($id, $meta, $name);
		$this->maxDurable = $maxDurable;
		$this->maxStackSize = $maxStackSize;
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
}