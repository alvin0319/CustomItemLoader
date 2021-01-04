<?php

declare(strict_types=1);

namespace alvin0319\CustomItemLoader\item;

use pocketmine\item\Food;

class CustomFoodItem extends Food{

	protected $maxStackSize = 64;

	protected $nutrition;

	protected $canAlwaysEat = false;

	protected $saturation;

	public function __construct(int $id, int $meta = 0, string $name = "Unknown", int $maxStackSize = 64, int $nutrition = 1, bool $canAlwaysEat = false, float $saturation = 1){
		parent::__construct($id, $meta, $name);
		$this->maxStackSize = $maxStackSize;
		$this->nutrition = $nutrition;
		$this->canAlwaysEat = $canAlwaysEat;
		$this->saturation = $saturation;
	}

	public function getMaxStackSize() : int{
		return $this->maxStackSize;
	}

	public function getFoodRestore() : int{
		return $this->nutrition;
	}

	public function requiresHunger() : bool{
		return $this->canAlwaysEat;
	}

	public function getSaturationRestore() : float{
		return $this->saturation;
	}

	public function getResidue(){
		return parent::getResidue(); // TODO: Find out the components
	}
}