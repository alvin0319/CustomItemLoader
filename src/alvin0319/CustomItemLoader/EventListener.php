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

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\tile\ItemFrame;
use function ceil;
use function floor;
use function implode;
use function lcg_value;

final class EventListener implements Listener{

	/** @var TaskHandler[][] */
	protected array $handlers = [];

	/**
	 * @param DataPacketReceiveEvent $event
	 *
	 * @priority HIGHEST
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		if(!$packet instanceof PlayerActionPacket){
			return;
		}
		$handled = false;
		try{
			$pos = new Vector3($packet->x, $packet->y, $packet->z);
			$player = $event->getPlayer();
			if($packet->action === PlayerActionPacket::ACTION_START_BREAK){
				$item = $player->getInventory()->getItemInHand();
				if(!CustomItemManager::getInstance()->isCustomItem($item)){
					return;
				}
				if($pos->distanceSquared($player) > 10000){
					return;
				}

				$target = $player->getLevelNonNull()->getBlock($pos);

				$ev = new PlayerInteractEvent($player, $player->getInventory()->getItemInHand(), $target, null, $packet->face, PlayerInteractEvent::LEFT_CLICK_BLOCK);
				if($player->isSpectator() || $player->getLevelNonNull()->checkSpawnProtection($player, $target)){
					$ev->setCancelled();
				}

				$ev->call();
				if($ev->isCancelled()){
					$player->getInventory()->sendHeldItem($player);
					return;
				}

				$tile = $player->getLevelNonNull()->getTile($pos);
				if($tile instanceof ItemFrame && $tile->hasItem()){
					if(lcg_value() <= $tile->getItemDropChance()){
						$player->getLevelNonNull()->dropItem($tile->getBlock(), $tile->getItem());
					}
					$tile->setItem();
					$tile->setItemRotation(0);
					return;
				}
				$block = $target->getSide($packet->face);
				if($block->getId() === BlockIds::FIRE){
					$player->getLevelNonNull()->setBlock($block, BlockFactory::get(BlockIds::AIR));
					return;
				}

				if(!$player->isCreative()){
					$handled = true;
					//TODO: improve this to take stuff like swimming, ladders, enchanted tools into account, fix wrong tool break time calculations for bad tools (pmmp/PocketMine-MP#211)
					$breakTime = ceil($target->getBreakTime($player->getInventory()->getItemInHand()) * 20);
					if($breakTime > 0){
						if($breakTime > 10){
							$breakTime -= 10;
						}
						$this->scheduleTask(Position::fromObject($pos, $player->getLevelNonNull()), $player->getInventory()->getItemInHand(), $player, $breakTime);
						$player->getLevelNonNull()->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_START_BREAK, (int) (65535 / $breakTime));
					}
				}
			}elseif($packet->action === PlayerActionPacket::ACTION_ABORT_BREAK){
				$player->getLevelNonNull()->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_STOP_BREAK);
				$handled = true;
				$this->stopTask($player, Position::fromObject($pos, $player->getLevelNonNull()));
			}
		}finally{
			if($handled){
				$event->setCancelled();
			}
		}
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof StartGamePacket){
			$packet->experiments = new Experiments([
				"holiday_creator_features" => true
			], true);
			echo "The fuck?\n";
		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$player->sendDataPacket(CustomItemManager::getInstance()->getPacket());
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if(!isset($this->handlers[$player->getName()])){
			return;
		}
		foreach($this->handlers[$player->getName()] as $blockHash => $handler){
			$handler->cancel();
		}
		unset($this->handlers[$player->getName()]);
	}

	private function scheduleTask(Position $pos, Item $item, Player $player, float $breakTime) : void{
		/*
		 * TODO: HACK
		 * This is very hacky method and unverified method.
		 * But We don't have any ways to implement this
		 *
		 * For travelers: This will make a delayed task which breaks block
		 * This is not satisfied method, but no other ways to implement this
		 * If you have find better method, Please make a PR!
		 * Your contribution is very appreciated!
		 *
		 * Tl;DR: Hacky method
		 */
		// Credit: ๖ζ͜͡Apakoh
		$handler = CustomItemLoader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $_) use ($pos, $item, $player) : void{
			$pos->getLevelNonNull()->useBreakOn($pos, $item, $player);
			unset($this->handlers[$player->getName()][$this->blockHash($pos)]);
		}), (int) floor($breakTime));
		if(!isset($this->handlers[$player->getName()])){
			$this->handlers[$player->getName()] = [];
		}
		$this->handlers[$player->getName()][$this->blockHash($pos)] = $handler;
	}

	private function stopTask(Player $player, Position $pos) : void{
		if(!isset($this->handlers[$player->getName()][$this->blockHash($pos)])){
			return;
		}
		$handler = $this->handlers[$player->getName()][$this->blockHash($pos)];
		$handler->cancel();
		unset($this->handlers[$player->getName()][$this->blockHash($pos)]);
	}

	private function blockHash(Position $pos) : string{
		return implode(":", [$pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ(), $pos->getLevelNonNull()->getFolderName()]);
	}
}