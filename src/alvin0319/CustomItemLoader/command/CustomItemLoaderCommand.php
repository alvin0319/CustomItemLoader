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

namespace alvin0319\CustomItemLoader\command;

use alvin0319\CustomItemLoader\CustomItemLoader;
use alvin0319\CustomItemLoader\CustomItemManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use function array_shift;
use function count;

final class CustomItemLoaderCommand extends Command implements PluginOwned{
	use PluginOwnedTrait;

	public function __construct(){
		parent::__construct("customitemloader");
		$this->setPermission("customitemloader.command");
		$this->owningPlugin = CustomItemLoader::getInstance();
		$this->setUsage("/customitemloader <reload>"); // TODO: add more command
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}
		if(count($args) < 1){
			throw new InvalidCommandSyntaxException();
		}
		switch(array_shift($args)){
			case "reload":
				CustomItemManager::getInstance()->registerDefaultItems(CustomItemLoader::getInstance()->getConfig()->get("items", []), true);
				$sender->sendMessage("Config was successfully loaded! the player who join next time will be affected.");
				break;
			default:
				throw new InvalidCommandSyntaxException();
		}
		return true;
	}
}