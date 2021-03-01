<?php

declare(strict_types=1);

namespace alvin0319\CustomItemLoader\item;

use alvin0319\CustomItemLoader\item\properties\CustomItemProperties;

trait CustomItemTrait{

	protected CustomItemProperties $properties;

	public function __construct(string $name, array $data){
		$this->properties = new CustomItemProperties($name, $data);
		$this->id = $this->properties->getId();
		$this->meta = $this->properties->getMeta();
		$this->name = $this->properties->getName();
	}

	public function getProperties() : CustomItemProperties{
		return $this->properties;
	}
}