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

namespace alvin0319\CustomItemLoader;

use alvin0319\CustomItemLoader\command\ResourcePackCreateCommand;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use function is_dir;
use function mkdir;

class CustomItemLoader extends PluginBase implements Listener{
	use SingletonTrait;

	public function onLoad() : void{
		self::setInstance($this);
	}

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveDefaultConfig();

		if(!is_dir($this->getResourcePackFolder())){
			mkdir($this->getResourcePackFolder());
		}

		$this->getServer()->getCommandMap()->register("customitemloader", new ResourcePackCreateCommand());

		CustomItemManager::init();
		CustomItemManager::registerDefaultItems($this->getConfig()->get("items", []));
	}

	public function getResourcePackFolder() : string{
		return $this->getDataFolder() . "resource_packs/";
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$player->getNetworkSession()->sendDataPacket(CustomItemManager::getPacket());
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packets = $event->getPackets();
		foreach($packets as $packet){
			if($packet instanceof StartGamePacket){
				$packet->experiments = new Experiments([
					"data_driven_items" => true
				], true);
			}elseif($packet instanceof ResourcePackStackPacket){
				$packet->experiments = new Experiments([
					"data_driven_items" => true
				], true);
			}
		}
	}
}