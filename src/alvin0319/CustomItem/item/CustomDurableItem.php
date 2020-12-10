<?php

declare(strict_types=1);

namespace alvin0319\CustomItem\item;

use pocketmine\item\Durable;

class CustomDurableItem extends Durable{

	protected $maxDurable = -1;

	public function __construct(int $id, int $meta, string $name, int $maxDurable){
		parent::__construct($id, $meta, $name);
		$this->maxDurable = $maxDurable;
	}

	public function getMaxDurability() : int{
		return $this->maxDurable;
	}
}