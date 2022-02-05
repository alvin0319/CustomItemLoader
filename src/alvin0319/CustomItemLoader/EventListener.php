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
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\ItemFrame;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\Position;
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
			$pos = new Vector3($packet->blockPosition->getX(), $packet->blockPosition->getY(), $packet->blockPosition->getZ());
			$player = $event->getOrigin()?->getPlayer() ?: throw new AssumptionFailedError("This packet cannot be received from non-logged in player");
			if($packet->action === PlayerAction::START_BREAK){
				$item = $player->getInventory()->getItemInHand();
				if(!CustomItemManager::getInstance()->isCustomItem($item)){
					return;
				}
				if($pos->distanceSquared($player->getPosition()) > 10000){
					return;
				}

				$target = $player->getWorld()->getBlock($pos);

				$ev = new PlayerInteractEvent($player, $player->getInventory()->getItemInHand(), $target, null, $packet->face, PlayerInteractEvent::LEFT_CLICK_BLOCK);
				if($player->isSpectator()){
					$ev->cancel();
				}

				$ev->call();
				if($ev->isCancelled()){
					$event->getOrigin()->getInvManager()?->syncSlot($player->getInventory(), $player->getInventory()->getHeldItemIndex());
					return;
				}

				$frameBlock = $player->getWorld()->getBlock($pos);
				if($frameBlock instanceof ItemFrame && $frameBlock->getFramedItem() !== null){
					if(lcg_value() <= $frameBlock->getItemDropChance()){
						$player->getWorld()->dropItem($frameBlock->getPosition(), $frameBlock->getFramedItem());
					}
					$frameBlock->setFramedItem(null);
					$frameBlock->setItemRotation(0);
					$player->getWorld()->setBlock($pos, $frameBlock);
					return;
				}
				$block = $target->getSide($packet->face);
				if($block->getId() === BlockLegacyIds::FIRE){
					$player->getWorld()->setBlock($block->getPosition(), BlockFactory::getInstance()->get(BlockLegacyIds::AIR, 0));
					return;
				}

				if(!$player->isCreative()){
					$handled = true;
					//TODO: improve this to take stuff like swimming, ladders, enchanted tools into account, fix wrong tool break time calculations for bad tools (pmmp/PocketMine-MP#211)
					$breakTime = ceil($target->getBreakInfo()->getBreakTime($player->getInventory()->getItemInHand()) * 20);
					if($breakTime > 0){
						if($breakTime > 10){
							$breakTime -= 10;
						}
						$this->scheduleTask(Position::fromObject($pos, $player->getWorld()), $player->getInventory()->getItemInHand(), $player, $breakTime);
						$player->getWorld()->broadcastPacketToViewers($pos, LevelEventPacket::create(LevelEvent::BLOCK_START_BREAK, (int) (65535 / $breakTime), $pos->asVector3()));
						$player->getWorld()->broadcastPacketToViewers($pos, LevelSoundEventPacket::nonActorSound(LevelSoundEvent::BREAK_BLOCK, $pos, false));
					}
				}
			}elseif($packet->action === PlayerAction::ABORT_BREAK){
				$player->getWorld()->broadcastPacketToViewers($pos, LevelEventPacket::create(LevelEvent::BLOCK_STOP_BREAK, 0, $pos->asVector3()));
				$handled = true;
				$this->stopTask($player, Position::fromObject($pos, $player->getWorld()));
			}
		}finally{
			if($handled){
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
}