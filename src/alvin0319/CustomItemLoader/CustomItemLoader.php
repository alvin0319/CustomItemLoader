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
use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use RuntimeException;
use Webmozart\PathUtil\Path;
use function class_exists;
use function is_dir;
use function mkdir;

class CustomItemLoader extends PluginBase{
	use SingletonTrait;

	public function onLoad() : void{
		self::setInstance($this);
	}

	public function onEnable() : void{
		$this->saveDefaultConfig();

		if(!is_dir($this->getResourcePackFolder()) && !mkdir($concurrentDirectory = $this->getResourcePackFolder()) && !is_dir($concurrentDirectory)){
			throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
		}

		if(class_exists(UpdateNotifier::class)){
			UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
		}

		CustomItemManager::reset();
		CustomItemManager::getInstance()->registerDefaultItems($this->getConfig()->get("items", []));

		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

		if($this->getServer()->getPort() !== 19132){
			// TODO: proxy support
			// maybe behind on the proxy
			// Proxies such as WDPE will send StartGamePacket only once and won't send again (maybe its logic?)
			// so if this plugin is behind on proxy and is not lobby server the item texture won't appear
			// the solution for this is use this plugin also on lobby server so that player can receive modified StartGamePacket
			$this->getLogger()->notice("Detected this server isn't running on 19132 port. If you are running this server behind proxy, make sure to use this plugin on lobby.");
		}

		$this->getServer()->getCommandMap()->registerAll("customitemloader", [
			new CustomItemLoaderCommand(),
			new ResourcePackCreateCommand()
		]);
	}

	public function getResourcePackFolder() : string{
		return Path::join($this->getDataFolder(), "resource_packs");
	}
}
