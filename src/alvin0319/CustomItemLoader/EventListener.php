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

use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\Position;
use function floor;
use function implode;
use function var_dump;

final class EventListener implements Listener{

	/** @var TaskHandler[][] */
	protected array $handlers = [];

	/** @priority HIGHEST */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		if(!$packet instanceof PlayerAuthInputPacket){
			return;
		}
		$blockActions = $packet->getBlockActions();
		if($blockActions === null){
			return;
		}
		$player = $event->getOrigin()->getPlayer();
		if($player === null){
			return;
		}
		$handled = false;
		try{
			foreach($blockActions as $action){
				$item = $player->getInventory()->getItemInHand();
				if(!CustomItemManager::getInstance()->isCustomItem($item)){
					continue;
				}
				if($action instanceof PlayerBlockActionWithBlockInfo){
					$blockPos = $action->getBlockPosition();
					$pos = new Vector3($blockPos->getX(), $blockPos->getY(), $blockPos->getZ());
					if($action->getActionType() === PlayerAction::START_BREAK){
						$player->attackBlock($pos, $action->getFace());
						$handled = true;
					}elseif($action->getActionType() === PlayerAction::CRACK_BLOCK){
						$player->continueBreakBlock($pos, $action->getFace());
						$speed = $this->calculateBreakProgressPerTick($player->getWorld()->getBlock($pos), $player);
						$player->getNetworkSession()->sendDataPacket(
							LevelEventPacket::create(
								LevelEvent::BLOCK_BREAK_SPEED,
								(int) (65535 * $speed),
								$pos
							)
						);
						var_dump($speed);
					}
				}elseif($action instanceof PlayerBlockActionStopBreak){
					$player->stopBreakBlock(new Vector3(0, 0, 0));
				}
				break;
			}
		}finally{
			if($handled){
				var_dump("Cancelled");
				$event->cancel();
			}
		}
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packets = $event->getPackets();
		foreach($packets as $packet){
			if($packet instanceof StartGamePacket){
				$packet->levelSettings->experiments = new Experiments([
					"data_driven_items" => true
				], true);
			}elseif($packet instanceof ResourcePackStackPacket){
				$packet->experiments = new Experiments([
					"data_driven_items" => true
				], true);
			}
		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$player->getNetworkSession()->sendDataPacket(CustomItemManager::getInstance()->getPacket());
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
		$handler = CustomItemLoader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($pos, $item, $player) : void{
			$pos->getWorld()->useBreakOn($pos, $item, $player);
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
		return implode(":", [$pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ(), $pos->getWorld()->getFolderName()]);
	}

	/**
	 * Returns the calculated break speed as percentage progress per game tick.
	 */
	private function calculateBreakProgressPerTick(Block $block, Player $player) : float{
		if(!$block->getBreakInfo()->isBreakable()){
			return 0.0;
		}
		//TODO: improve this to take stuff like swimming, ladders, enchanted tools into account, fix wrong tool break time calculations for bad tools (pmmp/PocketMine-MP#211)
		$breakTimePerTick = $block->getBreakInfo()->getBreakTime($player->getInventory()->getItemInHand()) * 20;

		if($breakTimePerTick > 0){
			return 1 / $breakTimePerTick;
		}
		return 1;
	}
}