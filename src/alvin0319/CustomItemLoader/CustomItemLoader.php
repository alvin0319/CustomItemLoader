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

use alvin0319\CustomItemLoader\command\CustomItemLoaderCommand;
use alvin0319\CustomItemLoader\command\ResourcePackCreateCommand;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use function is_dir;
use function mkdir;

class CustomItemLoader extends PluginBase{
	use SingletonTrait;

	public function onLoad() : void{
		self::setInstance($this);
	}

	public function onEnable() : void{
		$this->saveDefaultConfig();

		if(!is_dir($this->getResourcePackFolder())){
			mkdir($this->getResourcePackFolder());
		}

		$this->getServer()->getCommandMap()->registerAll("customitemloader", [
			new ResourcePackCreateCommand(),
			new CustomItemLoaderCommand()
		]);

		if($this->getServer()->getPort() !== 19132){
			// TODO: proxy support
			// maybe behind on the proxy
			// Proxies such as WDPE will send StartGamePacket only once and won't send again (maybe its logic?)
			// so if this plugin is behind on proxy and is not lobby server the item texture won't appear
			// the solution for this is use this plugin also on lobby server so that player can receive modified StartGamePacket
			$this->getLogger()->notice("Detected this server isn't running on 19132 port. If you are running this server behind proxy, make sure to use this plugin on lobby.");
		}

		CustomItemManager::init();
		CustomItemManager::registerDefaultItems($this->getConfig()->get("items", []));

		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
	}

	public function getResourcePackFolder() : string{
		return $this->getDataFolder() . "resource_packs/";
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof StartGamePacket){
			$packet->experiments = new Experiments([], true);
		}
	}
}