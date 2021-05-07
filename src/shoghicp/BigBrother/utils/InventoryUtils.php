<?php
/**
 *  ______  __         ______               __    __
 * |   __ \|__|.-----.|   __ \.----..-----.|  |_ |  |--..-----..----.
 * |   __ <|  ||  _  ||   __ <|   _||  _  ||   _||     ||  -__||   _|
 * |______/|__||___  ||______/|__|  |_____||____||__|__||_____||__|
 *             |_____|
 *
 * BigBrother plugin for PocketMine-MP
 * Copyright (C) 2014-2015 shoghicp <https://github.com/shoghicp/BigBrother>
 * Copyright (C) 2016- BigBrotherTeam
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author BigBrotherTeam
 * @link   https://github.com/BigBrotherTeam/BigBrother
 *
 */

declare(strict_types=1);

namespace shoghicp\BigBrother\utils;

use InvalidArgumentException;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\utils\BinaryStream;
use const pocketmine\DEBUG;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\WindowTypes;

use pocketmine\entity\object\ItemEntity;
use pocketmine\inventory\transaction\action\CreativeInventoryAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\tile\EnderChest as TileEnderChest;
use pocketmine\tile\Tile;

use ReflectionClass;
use ReflectionException;
use UnexpectedValueException;
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\network\OutboundPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\WindowConfirmationPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityEquipmentPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\OpenWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SetSlotPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\WindowItemsPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\WindowPropertyPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\CollectItemPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\CloseWindowPacket as ServerCloseWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\ClickWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\CloseWindowPacket as ClientCloseWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\CreativeInventoryActionPacket;

class InventoryUtils{

	/** @var DesktopPlayer */
	private $player;
	/** @var array */
	private $windowInfo = [];
	/** @var ShapedRecipe[][] */
	private $shapedRecipes;
	/** @var ShapelessRecipe[][] */
	private $shapelessRecipes;
	/** @var Item */
	private $playerHeldItem;
	/** @var Item[] */
	private $playerCraftSlot;
	/** @var Item[] */
	private $playerCraftTableSlot;
	/** @var Item[] */
	private $playerArmorSlot;
	/** @var Item[] */
	private $playerInventorySlot;
	/** @var Item[] */
	private $playerHotBarSlot;

	/**
	 * @param DesktopPlayer $player
	 */
	public function __construct(DesktopPlayer $player){
		$this->player = $player;

		$this->playerCraftSlot = array_fill(0, 5, Item::get(Item::AIR, 0, 0));
		$this->playerCraftTableSlot = array_fill(0, 10, Item::get(Item::AIR, 0, 0));
		$this->playerArmorSlot = array_fill(0, 5, Item::get(Item::AIR, 0, 0));
		$this->playerInventorySlot = array_fill(0, 27, Item::get(Item::AIR, 0, 0));
		$this->playerHotBarSlot = array_fill(0, 9, Item::get(Item::AIR, 0, 0));
		$this->playerHeldItem = Item::get(Item::AIR, 0, 0);

		$this->shapelessRecipes = $player->getServer()->getCraftingManager()->getShapelessRecipes();//TODO: custom recipes
		$this->shapedRecipes = $player->getServer()->getCraftingManager()->getShapedRecipes();//TODO: custom recipes
	}

	/**
	 * @param Item[] $items
	 * @return Item[]
	 */
	public function getInventory(array $items) : array{
		foreach($this->playerInventorySlot as $item){
			$items[] = $item;
		}

		return $items;
	}

	/**
	 * @param Item[] $items
	 * @return Item[]
	 */
	public function getHotBar(array $items) : array{
		foreach($this->playerHotBarSlot as $item){
			$items[] = $item;
		}

		return $items;
	}

	/**
	 * @param int $windowId
	 * @param int $inventorySlot
	 * @param int|null $targetWindowId
	 * @param int|null $targetInventorySlot
	 * @return Item reference(&)
	 */
	private function &getItemAndSlot(int $windowId, int $inventorySlot, int &$targetWindowId = null, int &$targetInventorySlot = null) : Item{
		$targetInventorySlot = $inventorySlot;
		$targetWindowId = $windowId;

		switch($windowId){
			case ContainerIds::INVENTORY:
				if($inventorySlot >= 0 and $inventorySlot < 5){
					$item = &$this->playerCraftTableSlot[$inventorySlot];
				}elseif($inventorySlot >= 5 and $inventorySlot < 9){
					$targetWindowId = ContainerIds::ARMOR;
					$inventorySlot -= 5;
					$targetInventorySlot = $inventorySlot;
					$item = &$this->playerArmorSlot[$inventorySlot];
				}elseif($inventorySlot >= 9 and $inventorySlot < 36){
					$inventorySlot -= 9;
					$item = &$this->playerInventorySlot[$inventorySlot];
				}elseif($inventorySlot >= 36 and $inventorySlot < 45){
					$inventorySlot -= 36;
					$targetInventorySlot = $inventorySlot;
					$item = &$this->playerHotBarSlot[$inventorySlot];
				}else{
					throw new InvalidArgumentException("inventorySlot: " . $inventorySlot . " is out of range!!");
				}
			break;
			default:
				if($inventorySlot >= $this->windowInfo[$windowId]["slots"]){
					$targetWindowId = ContainerIds::INVENTORY;
					$inventorySlot -= $this->windowInfo[$windowId]["slots"];

					if($inventorySlot >= 27 and $inventorySlot < 36){
						$inventorySlot -= 27;
						$targetInventorySlot = $inventorySlot;
						$item = &$this->playerHotBarSlot[$inventorySlot];
					}else{
						$targetInventorySlot = $inventorySlot + 9;
						$item = &$this->playerInventorySlot[$inventorySlot];
					}
				}else{
					if($windowId === 127){
						$item = &$this->playerCraftTableSlot[$inventorySlot];
					}else{
						$item = &$this->windowInfo[$windowId]["items"][$inventorySlot];
					}
				}
			break;
		}

		return $item;
	}

	/*private function get(int $windowId, int $inventorySlot, Item $selectedItem){
		switch($windowId){
			case ContainerIds::INVENTORY:


			break;
		}
	}*/

	private function dropHeldItem() : void{
		if(!$this->playerHeldItem->isNull()){
			$this->player->dropItem($this->playerHeldItem);
			$this->playerHeldItem = Item::get(Item::AIR, 0, 0);
			$this->player->getCursorInventory()->setItem(0, Item::get(Item::AIR, 0, 0));
		}
	}

	/**
	 * @param Item[] $craftingItem
	 */
	private function dropCraftingItem(array &$craftingItem) : void{
		foreach($craftingItem as $slot => $item){
			if(!$item->isNull()){
				$pk = new SetSlotPacket();
				$pk->windowId = count($craftingItem) === 9 ? 127 : 0;
				$pk->slotData = Item::get(Item::AIR, 0, 0);
				$pk->slot = $slot;
				$this->player->putRawPacket($pk);

				$this->player->getCraftingGrid()->setItem(0, Item::get(Item::AIR, 0, 0));
				$craftingItem[$slot] = Item::get(Item::AIR, 0, 0);
				if($slot !== 0){
					$this->player->dropItem($item);
				}
			}
		}
	}

	public function sendHeldItem(){//send cursor item
		$pk = new SetSlotPacket();
		$pk->windowId = -1;
		$pk->slotData = $this->playerHeldItem;
		$pk->slot = -1;

		$this->player->putRawPacket($pk);
	}

	/**
	 * @param ContainerOpenPacket $packet
	 * @return OutboundPacket|null
	 */
	public function onWindowOpen(ContainerOpenPacket $packet) : ?OutboundPacket{
		switch($packet->type){
			case WindowTypes::CONTAINER:
				$type = "minecraft:generic_9x3";
				$title = "chest";
			break;
			case WindowTypes::WORKBENCH:
				$type = "minecraft:crafting";
				$title = "crafting";
			break;
			case WindowTypes::FURNACE:
				$type = "minecraft:furnace";
				$title = "furnace";
			break;
			case WindowTypes::ENCHANTMENT:
				$type = "minecraft:enchantment";
				$title = "enchant";
			break;
			case WindowTypes::ANVIL:
				$type = "minecraft:anvil";
				$title = "repair";
			break;
			default://TODO: http://wiki.vg/Inventory#Windows
				echo "[InventoryUtils] ContainerOpenPacket: ".$packet->type."\n";

				$pk = new ContainerClosePacket();
				$pk->windowId = $packet->windowId;
				$this->player->handleDataPacket($pk);

				return null;
		}

		$saveSlots = 0;
		if(($tile = $this->player->getLevel()->getTile(new Vector3((int) $packet->x, (int) $packet->y, (int) $packet->z))) instanceof Tile){
			if($tile instanceof TileEnderChest){
				$title = "enderchest";
			}elseif($tile instanceof InventoryHolder){
				$slots = $saveSlots = $tile->getInventory()->getSize();
				if($title === "chest" and $slots === 54){
					$type = "minecraft:generic_9x6";
					$title = "chestDouble";
				}
			}
		}

		if($title === "crafting"){
			$saveSlots = 10;
		}elseif($title === "repair"){
			$saveSlots = 3;
		}

		$pk = new OpenWindowPacket();
		$pk->windowId = $packet->windowId;
		$pk->inventoryType = $type;//
		$pk->windowTitle = json_encode(["translate" => "container.".$title]);

		$this->windowInfo[$packet->windowId] = ["type" => $packet->type, "slots" => $saveSlots, "items" => []];

		return $pk;
	}

	/**
	 * @param ClientCloseWindowPacket $packet
	 * @return ContainerClosePacket|null
	 */
	public function onWindowCloseFromPCtoPE(ClientCloseWindowPacket $packet) : ?ContainerClosePacket{
		$this->dropCraftingItem($this->playerCraftSlot);
		$this->dropCraftingItem($this->playerCraftTableSlot);

		$this->dropHeldItem();

		if($packet->windowId !== ContainerIds::INVENTORY){//Player Inventory
			$pk = new ContainerClosePacket();
			$pk->windowId = $packet->windowId;

			return $pk;
		}

		return null;
	}

	/**
	 * @param ContainerClosePacket $packet
	 * @return ServerCloseWindowPacket
	 */
	public function onWindowCloseFromPEtoPC(ContainerClosePacket $packet) : ServerCloseWindowPacket{
		$this->dropHeldItem();

		$pk = new ServerCloseWindowPacket();
		$pk->windowId = $packet->windowId;

		unset($this->windowInfo[$packet->windowId]);

		return $pk;
	}

	/**
	 * @param InventorySlotPacket $packet
	 * @return OutboundPacket|null
	 */
	public function onWindowSetSlot(InventorySlotPacket $packet) : ?OutboundPacket{
		$pk = new SetSlotPacket();
		$pk->windowId = $packet->windowId;

		switch($packet->windowId){
			case ContainerIds::INVENTORY:
				$pk->slotData = $packet->item->getItemStack();

				if($packet->inventorySlot >= 0 and $packet->inventorySlot < $this->player->getInventory()->getHotbarSize()){
					$pk->slot = $packet->inventorySlot + 36;
					$inventorySlot = $packet->inventorySlot;

					$this->playerHotBarSlot[$inventorySlot] = $packet->item->getItemStack();
				}elseif($packet->inventorySlot >= $this->player->getInventory()->getHotbarSize() and $packet->inventorySlot < $this->player->getInventory()->getSize()){
					$pk->slot = $packet->inventorySlot;
					$inventorySlot = $packet->inventorySlot - 9;

					$this->playerInventorySlot[$inventorySlot] = $packet->item->getItemStack();
				}elseif($packet->inventorySlot >= $this->player->getInventory()->getSize() and $packet->inventorySlot < $this->player->getInventory()->getSize() + 4){
					// ignore this packet (this packet is not needed because this is duplicated packet)
					$pk = null;
				}

				return $pk;
			case ContainerIds::ARMOR:
				$pk->windowId = ContainerIds::INVENTORY;
				$pk->slotData = $packet->item->getItemStack();
				$pk->slot = $packet->inventorySlot + 5;

				$this->playerArmorSlot[$packet->inventorySlot] = $packet->item->getItemStack();

				return $pk;
			case ContainerIds::HOTBAR:
			case ContainerIds::UI://TODO
			break;
			default:
				if(isset($this->windowInfo[$packet->windowId])){
					$pk->slotData = $packet->item->getItemStack();
					$pk->slot = $packet->inventorySlot;

					$this->windowInfo[$packet->windowId]["items"][$packet->inventorySlot] = $packet->item->getItemStack();

					return $pk;
				}
				echo "[InventoryUtils] InventorySlotPacket: 0x".bin2hex(chr($packet->windowId))."\n";
			break;
		}
		return null;
	}

	/**
	 * @param ContainerSetDataPacket $packet
	 * @return OutboundPacket[]
	 */
	public function onWindowSetData(ContainerSetDataPacket $packet) : array{
		if(!isset($this->windowInfo[$packet->windowId])){
			echo "[InventoryUtils] ContainerSetDataPacket: 0x".bin2hex(chr($packet->windowId))."\n";
		}

		$packets = [];
		switch($this->windowInfo[$packet->windowId]["type"]){
			case WindowTypes::FURNACE:
				switch($packet->property){
					case ContainerSetDataPacket::PROPERTY_FURNACE_TICK_COUNT://Smelting
						$pk = new WindowPropertyPacket();
						$pk->windowId = $packet->windowId;
						$pk->property = 3;
						$pk->value = 200;//TODO: changed?
						$packets[] = $pk;

						$pk = new WindowPropertyPacket();
						$pk->windowId = $packet->windowId;
						$pk->property = 2;
						$pk->value = $packet->value;
						$packets[] = $pk;
					break;
					case ContainerSetDataPacket::PROPERTY_FURNACE_LIT_TIME://Fire icon
						$pk = new WindowPropertyPacket();
						$pk->windowId = $packet->windowId;
						$pk->property = 1;
						$pk->value = 200;//TODO: changed?
						$packets[] = $pk;

						$pk = new WindowPropertyPacket();
						$pk->windowId = $packet->windowId;
						$pk->property = 0;
						$pk->value = $packet->value;
						$packets[] = $pk;
					break;
					default:
						echo "[InventoryUtils] ContainerSetDataPacket: 0x".bin2hex(chr($packet->windowId))."\n";
					break;
				}
			break;
			default:
				echo "[InventoryUtils] ContainerSetDataPacket: 0x".bin2hex(chr($packet->windowId))."\n";
			break;
		}

		return $packets;
	}

	/**
	 * @param InventoryContentPacket $packet
	 * @return OutboundPacket[]
	 */
	public function onWindowSetContent(InventoryContentPacket $packet) : array{
		$packets = [];

		switch($packet->windowId){
			case ContainerIds::INVENTORY:
				$pk = new WindowItemsPacket();
				$pk->windowId = $packet->windowId;

				for($i = 0; $i < 5; ++$i){
					$pk->slotData[] = Item::get(Item::AIR, 0, 0);//Craft
				}

				for($i = 0; $i < 4; ++$i){
					$pk->slotData[] = $this->playerArmorSlot[$i];//Armor
				}

				$hotBar = [];
				$inventory = [];
				for($i = 0; $i < count($packet->items); $i++){
					if($i >= 0 and $i < 9){
						$hotBar[] = $packet->items[$i]->getItemStack();
					}else{
						$inventory[] = $packet->items[$i]->getItemStack();
						$pk->slotData[] = $packet->items[$i]->getItemStack();
					}
				}

				foreach($hotBar as $item){
					$pk->slotData[] = $item;
				}

				$pk->slotData[] = Item::get(Item::AIR, 0, 0);//offhand

				$this->playerInventorySlot = $inventory;
				$this->playerHotBarSlot = $hotBar;

				$packets[] = $pk;
			break;
			case ContainerIds::ARMOR:
				foreach($packet->items as $slot => $item){
					$pk = new SetSlotPacket();
					$pk->windowId = ContainerIds::INVENTORY;
					$pk->slotData = $item->getItemStack();
					$pk->slot = $slot + 5;

					$packets[] = $pk;
				}

				for($i = 0; $i < 4; ++$i){
					$this->playerArmorSlot[$i] = $packet->items[$i]->getItemStack();
				}
			break;
			//case ContainerIds::CREATIVE:
			case ContainerIds::HOTBAR:
			case ContainerIds::UI://TODO
			break;
			default:
				if(isset($this->windowInfo[$packet->windowId])){
					$items = [];
					foreach($packet->items as $slot => $item){
						$items[] = $item->getItemStack();
					}

					$pk = new WindowItemsPacket();
					$pk->windowId = $packet->windowId;
					$pk->slotData = $items;

					$this->windowInfo[$packet->windowId]["items"] = $items;

					$pk->slotData = $this->getInventory($pk->slotData);
					$pk->slotData = $this->getHotBar($pk->slotData);

					var_dump(count($pk->slotData));

					$packets[] = $pk;
				}else{
					echo "[InventoryUtils] InventoryContentPacket: 0x".bin2hex(chr($packet->windowId))."\n";
				}
			break;
		}

		return $packets;
	}

	/**
	 * @param ClickWindowPacket $packet
	 * @return InventoryTransactionPacket|null
	 */
	public function onWindowClick(ClickWindowPacket $packet) : ?InventoryTransactionPacket{
		$item = clone $packet->clickedItem;
		$heldItem = clone $this->playerHeldItem;
		$accepted = false;
		$otherAction = [];
		$isContainer = true;

		if($packet->slot === -1){
			return null;
		}

		var_dump($packet);

		switch($packet->mode){
			case 0:
				switch($packet->button){
					case 0://Left mouse click
						if($packet->slot === -999){
							$accepted = true;
							$isContainer = false;

							$dropItem = clone $this->playerHeldItem;
							$this->playerHeldItem = Item::get(Item::AIR, 0, 0);
							$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_WORLD, 0, NetworkInventoryAction::ACTION_MAGIC_SLOT_DROP_ITEM, Item::get(Item::AIR, 0, 0), $dropItem);
						}else{
							$accepted = true;

							if($item->equals($this->playerHeldItem, true, true)){
								$item->setCount($item->getCount() + $this->playerHeldItem->getCount());
								$this->playerHeldItem = Item::get(Item::AIR, 0, 0);
							}else{
								list($this->playerHeldItem, $item) = [$item, $this->playerHeldItem];//reverse
							}
						}
					break;
					case 1://Right mouse click
						if($packet->slot === -999){
							$accepted = true;
							$isContainer = false;

							$dropItem = $this->playerHeldItem->pop();
							$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_WORLD, 0, NetworkInventoryAction::ACTION_MAGIC_SLOT_DROP_ITEM, Item::get(Item::AIR, 0, 0), $dropItem);
						}else{
							$accepted = true;

							if($this->playerHeldItem->isNull()){
								$this->playerHeldItem = clone $item;
								$this->playerHeldItem->setCount((int) ceil($item->getCount() / 2));
								$item->setCount((int) floor($item->getCount() / 2));
							}else{
								if($item->isNull()){
									$item = $this->playerHeldItem->pop();
								}elseif($item->equals($this->playerHeldItem, true, true)){
									$this->playerHeldItem->pop();
									$item->setCount($item->getCount() + 1);
								}else{
									list($this->playerHeldItem, $item) = [$item, $this->playerHeldItem];//reverse
								}
							}
						}
					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 1:
				switch($packet->button){
					case 0://Shift + left mouse click
					case 1://Shift + right mouse click

					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 2:
				switch($packet->button){
					case 0://Number key 1
					case 1://Number key 2
					case 2://Number key 3
					case 3://Number key 4
					case 4://Number key 5
					case 5://Number key 6
					case 6://Number key 7
					case 7://Number key 8
					case 8://Number key 9
						if($this->playerHeldItem->isNull()){
							$accepted = true;

							$newItem = $this->getItemAndSlot($packet->windowId, $packet->slot);
							$item = $this->playerHotBarSlot[$packet->button];
							$this->playerHotBarSlot[$packet->button] = $newItem;
							$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_CONTAINER, ContainerIds::INVENTORY, $packet->button, $item, $newItem);
						}
					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 3:
				switch($packet->button){
					case 2://Middle click
						echo "middle\n";
					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 4:
				switch($packet->button){
					case 0://Drop key
						if($packet->slot !== -999){//Drop key
							$accepted = true;

							$item = clone $this->getItemAndSlot($packet->windowId, $packet->slot);
							$dropItem = $item->pop();
							$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_WORLD, 0, NetworkInventoryAction::ACTION_MAGIC_SLOT_DROP_ITEM, Item::get(Item::AIR, 0, 0), $dropItem);
						}else{//Left click outside inventory holding nothing
							//unused?
						}
					break;
					case 1:
						if($packet->slot !== -999){//Ctrl + Drop key
							$accepted = true;

							$dropItem = clone $this->getItemAndSlot($packet->windowId, $packet->slot);
							$item = Item::get(Item::AIR, 0, 0);
							$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_WORLD, 0, NetworkInventoryAction::ACTION_MAGIC_SLOT_DROP_ITEM, Item::get(Item::AIR, 0, 0), $dropItem);
						}else{//Right click outside inventory holding nothing
							//unused?
						}
					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 5:
				switch($packet->button){
					case 0://Starting left mouse drag

					break;
					case 1://Add slot for left-mouse drag

					break;
					case 2://Ending left mouse drag

					break;
					case 4://Starting right mouse drag
						echo "start\n";
					break;
					case 5://Add slot for right-mouse drag
						echo "add slot\n";
					break;
					case 6://Ending right mouse drag
						echo "end\n";
					break;
					case 8://Starting middle mouse drag

					break;
					case 9://Add slot for middle-mouse drag

					break;
					case 10://Ending middle mouse drag

					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 6:
				switch($packet->button){
					case 0://Double click

					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			default:
				echo "[InventoryUtils] ClickWindowPacket: ".$packet->mode."\n";
			break;
		}

		if($packet->windowId === 0){
			if($packet->slot === 45){//Offhand
				$accepted = false;
				$this->playerHeldItem = $heldItem;

				$this->player->sendMessage("Not yet implemented!");
			}
		}

		if($packet->windowId === 0 or $packet->windowId === 127){//Crafting
			$minCraftingSlot = 1;
			if($packet->windowId === 0){
				$saveInventoryData = &$this->playerCraftSlot;
				$maxCraftingSlot = 4;
			}else{
				$saveInventoryData = &$this->playerCraftTableSlot;
				$maxCraftingSlot = 9;
			}

			if($packet->slot >= $minCraftingSlot and $packet->slot <= $maxCraftingSlot){//Crafting Slot
				$accepted = false;//not send packet
				$this->playerHeldItem = $heldItem;

				$this->player->sendMessage("Not yet implemented!");
			}elseif($packet->slot === 0){//Crafting Result
				$accepted = false;//not send packet
				$this->playerHeldItem = $heldItem;

				$this->player->sendMessage("Not yet implemented!");
			}

			/*if($packet->slot >= $minCraftingSlot and $packet->slot <= $maxCraftingSlot){//Crafting Slot
				$isContainer = false;
				$oldItem = clone $saveInventoryData[$packet->slot];
				$saveInventoryData[$packet->slot] = $item;
				$inventorySlot = $packet->slot - 1;

				var_dump(["inventorySlot" => $inventorySlot]);

				if($heldItem->equals($item, true, true)){//TODO: more check item?
					if($oldItem->getId() === Item::AIR){
						$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_TODO, NetworkInventoryAction::SOURCE_TYPE_CRAFTING_ADD_INGREDIENT, $inventorySlot, $oldItem, $item);
					}else{
						$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_TODO, NetworkInventoryAction::SOURCE_TYPE_CRAFTING_REMOVE_INGREDIENT, $inventorySlot, $oldItem, $item);
					}
				}else{
					$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_TODO, NetworkInventoryAction::SOURCE_TYPE_CRAFTING_REMOVE_INGREDIENT, $inventorySlot, $oldItem, $item);
				}

				$this->onCraft($packet->windowId);
			}elseif($packet->slot === 0){//Crafting Result
				$isContainer = false;
				$resultItem = $saveInventoryData[0];

				$accepted = false;

				var_dump(["resultItem" => $resultItem, "item" => $item, "oldHeldItem" => $heldItem, "heldItem" => $this->playerHeldItem]);

				//$resultItem ===> $this->playerHeldItem
				//$heldItem ===> $item

				//var_dump($packet);
				/*if($heldItem->equals($item, true, true)){//TODO: more check item?
					if($resultItem->getId() === Item::AIR){
						$accepted = false;//not send packet

						$this->playerHeldItem = $heldItem;
					}else{
						//$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_TODO, NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT, 0, $resultItem, Item::get(Item::AIR, 0, 0));
					}
				}else{
					foreach($saveInventoryData as $craftingSlot => $inventoryItem){//TODO: must send slot?
						if($craftingSlot === 0){
							$saveInventoryData[$craftingSlot] = Item::get(Item::AIR, 0, 0);
						}else{
							if($inventoryItem->getCount() > 1){
								$saveInventorySlot[$craftingSlot] = $newInventoryItem = $inventoryItem->setCount($inventoryItem->getCount() - 1);
							}else{
								$saveInventoryData[$craftingSlot] = $newInventoryItem = Item::get(Item::AIR, 0, 0);
							}
							$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_TODO, NetworkInventoryAction::SOURCE_TYPE_CRAFTING_USE_INGREDIENT, $craftingSlot, $inventoryItem, $newInventoryItem);//don't use?
						}
					}



					$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_TODO, NetworkInventoryAction::SOURCE_TYPE_CONTAINER_DROP_CONTENTS, 0, $resultItem, Item::get(Item::AIR, 0, 0));

					$otherAction[] = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_TODO, NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT, 0, $resultItem, Item::get(Item::AIR, 0, 0));
				}

				$this->onCraft($packet->windowId);
			}*/
		}

		if(isset($this->windowInfo[$packet->windowId]["type"])){
			switch($this->windowInfo[$packet->windowId]["type"]){
				case WindowTypes::FURNACE:
					if($packet->slot === 2){
						if($heldItem->equals($item, true, true)){//TODO: more check item?
							$accepted = false;

							$this->playerHeldItem = $heldItem;
						}
					}
				break;
				//TODO: add more?
			}
		}

		$pk = null;
		if($accepted){
			/** @var NetworkInventoryAction[] $actions */
			$actions = [];

			if($isContainer){
				$ref = &$this->getItemAndSlot($packet->windowId, $packet->slot, $windowId, $saveInventorySlot);
				$oldItem = clone $ref;

				if($packet->windowId !== 127){
					$ref = $item;
				}

				$action = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_CONTAINER, $windowId, $saveInventorySlot, $oldItem, $item);
				$actions[] = $action;
			}

			foreach($otherAction as $action){
				$actions[] = $action;
			}

			if(!$heldItem->equalsExact($this->playerHeldItem)){
				$action = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_CONTAINER, ContainerIds::UI, 0, $heldItem, $this->playerHeldItem);
				$actions[] = $action;
			}
			$pk = new InventoryTransactionPacket();
			$pk->requestChangedSlots = [];
			$pk->requestId = InventoryTransactionPacket::TYPE_NORMAL;
			$pk->trData = NormalTransactionData::new($actions);
		}

		$accepted_pk = new WindowConfirmationPacket();
		$accepted_pk->windowId = $packet->windowId;
		$accepted_pk->actionNumber = $packet->actionNumber;
		$accepted_pk->accepted = $accepted;
		$this->player->putRawPacket($accepted_pk);

		if($accepted){
			$this->checkInventoryTransactionPacket($pk);

			return $pk;
		}else{
			$this->player->getInventory()->sendContents($this->player);
			$this->player->getArmorInventory()->sendContents($this->player);
			$this->player->getInventory()->sendHeldItem($this->player);
			$this->sendHeldItem();
		}
		return null;
	}

	/**
	 * @param CreativeInventoryActionPacket $packet
	 * @return DataPacket|null
	 */
	public function onCreativeInventoryAction(CreativeInventoryActionPacket $packet) : ?DataPacket{
		$trace = debug_backtrace();
		foreach($trace as $line) {
			error_log("{$line["file"]}: line {$line["line"]}");
		}
		if($packet->slot === 65535){ //...?
			$dropItem = Item::get(Item::AIR, 0, 0);

			foreach($this->player->getInventory()->getContents() as $slot => $item){
				if($item->equalsExact($packet->clickedItem)){
					if(!$item->isNull()){
						$dropItem = $item->pop();
						$this->player->getInventory()->setItem($slot, $item);
					}
					break;
				}
			}

			$this->player->getInventory()->sendHeldItem($this->player->getViewers());
			if(!$dropItem->isNull()){
				$this->player->dropItem($dropItem);
			}

			return null;
		}else{
			//$newItem = ItemFactory::get(0);
			//$oldItem = ItemFactory::get(0);


			if($packet->slot === -1){//DropItem //...? //...?
				$this->player->dropItem($packet->clickedItem);

				return null;
			}elseif($packet->slot > 4 and $packet->slot < 9){//Armor
				$inventorySlot = $packet->slot - 5;
				$oldItem = $this->playerArmorSlot[$inventorySlot];
				$newItem = $packet->clickedItem;
				$this->playerArmorSlot[$inventorySlot] = $newItem;

				$action = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_CONTAINER, ContainerIds::ARMOR, $inventorySlot, $oldItem, $newItem);
			}elseif($packet->slot === 45){//Offhand
				$pk = new SetSlotPacket();
				$pk->windowId = 0;
				$pk->slotData = Item::get(Item::AIR, 0, 0);
				$pk->slot = 45;//offhand slot
				$this->player->putRawPacket($pk);

				return null;
			}else{//Inventory
				$newItem = $packet->clickedItem;

				if($packet->slot > 35 and $packet->slot < 45){//hotBar
					$saveInventorySlot = $packet->slot - 36;
					$inventorySlot = $saveInventorySlot;

					$oldItem = $this->playerHotBarSlot[$inventorySlot];
					$this->playerHotBarSlot[$inventorySlot] = $newItem;
				}else{
					$saveInventorySlot = $packet->slot;
					$inventorySlot = $packet->slot - 9;

					$oldItem = $this->playerInventorySlot[$inventorySlot];
					$this->playerInventorySlot[$inventorySlot] = $newItem;
				}

				$action = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_CONTAINER, ContainerIds::INVENTORY, $saveInventorySlot, $oldItem, $newItem);
			}

			/** @var NetworkInventoryAction[] $actions */
			$actions = [$action];

			if(!$oldItem->isNull() and !$oldItem->equalsExact($newItem)){
				$action = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_CREATIVE, -1, NetworkInventoryAction::ACTION_MAGIC_SLOT_CREATIVE_DELETE_ITEM, Item::get(Item::AIR, 0, 0), $oldItem);

				$actions[] = $action;
			}

			if(!$newItem->isNull() and !$oldItem->equalsExact($newItem)){
				$action = $this->addNetworkInventoryAction(NetworkInventoryAction::SOURCE_CREATIVE, -1, NetworkInventoryAction::ACTION_MAGIC_SLOT_CREATIVE_CREATE_ITEM, $newItem, Item::get(Item::AIR, 0, 0));

				$actions[] = $action;
			}

			$pk = new InventoryTransactionPacket();
			$pk->requestChangedSlots = [];
			$pk->requestId = InventoryTransactionPacket::TYPE_NORMAL;
			$pk->trData = NormalTransactionData::new($actions);

			$this->checkInventoryTransactionPacket($pk);

			return $pk;
		}
	}

	/**
	 * @param TakeItemActorPacket $packet
	 * @return OutboundPacket|null
	 */
	public function onTakeItemEntity(TakeItemActorPacket $packet) : ?OutboundPacket{
		$itemCount = 1;
		$entity = $this->player->getLevel()->getEntity($packet->target);//TODO: support fake entity
		if($entity instanceof ItemEntity){
			$itemCount = $entity->getItem()->getCount();
		}

		$pk = new CollectItemPacket();
		$pk->collectorEntityId = $packet->eid;
		$pk->collectedEntityId = $packet->target;
		$pk->pickUpItemCount = $itemCount;

		return $pk;
	}

	/**
	 * @param MobArmorEquipmentPacket $packet
	 * @return OutboundPacket[]|array
	 */
	public function onMobArmorEquipment(MobArmorEquipmentPacket $packet) : array{
		$packets = [];

		$pk = new EntityEquipmentPacket();
		$pk->entityId = $packet->entityRuntimeId;
		$pk->slot = 5;
		$pk->item = $packet->head->getItemStack();
		$packets[] = $pk;
		$this->playerArmorSlot[0] = $pk->item;

		$pk = new EntityEquipmentPacket();
		$pk->entityId = $packet->entityRuntimeId;
		$pk->slot = 4;
		$pk->item = $packet->chest->getItemStack();
		$packets[] = $pk;
		$this->playerArmorSlot[1] = $pk->item;

		$pk = new EntityEquipmentPacket();
		$pk->entityId = $packet->entityRuntimeId;
		$pk->slot = 3;
		$pk->item = $packet->legs->getItemStack();
		$packets[] = $pk;
		$this->playerArmorSlot[2] = $pk->item;

		$pk = new EntityEquipmentPacket();
		$pk->entityId = $packet->entityRuntimeId;
		$pk->slot = 2;
		$pk->item = $packet->feet->getItemStack();
		$packets[] = $pk;
		$this->playerArmorSlot[3] = $pk->item;

		return $packets;
	}

	/**
	 * @param int $windowId
	 */
	public function onCraft(int $windowId) : void{
		if($windowId !== 0 and $windowId !== 127){
			echo "[InventoryUtils][Debug] called onCraft\n";
			return;
		}

		$saveInventoryData = null;
		$gridSize = 0;
		$inputSlotMap = [];
		$outputSlotMap = array_fill(0, 2, array_fill(0, 2, Item::get(Item::AIR, 0, 0)));//TODO: extraOutput
		if($windowId === 0){
			$gridSize = 2;
			$saveInventoryData = &$this->playerCraftSlot;
		}elseif($windowId === 127){
			$gridSize = 3;
			$saveInventoryData = &$this->playerCraftTableSlot;
		}

		if(!is_null($saveInventoryData)){
			foreach($saveInventoryData as $slot => $item){
				if($slot === 0){
					continue;
				}

				$gridOffset = $slot - 1;
				$y = (int) ($gridOffset / $gridSize);
				$x = $gridOffset % $gridSize;
				$gridItem = clone $item;
				$inputSlotMap[$y][$x] = $gridItem->setCount(1);//blame pmmp
			}
		}

		$resultRecipe = null;
		foreach($this->shapedRecipes as $jsonResult => $jsonSlotData){
			foreach($jsonSlotData as $jsonSlotMap => $recipe){
				if($recipe->matchItems($inputSlotMap, $outputSlotMap)){
					$resultRecipe = $recipe;
					break;
				}
			}
		}

		if(is_null($resultRecipe)){
			foreach($this->shapelessRecipes as $jsonResult => $jsonSlotData){
				foreach($jsonSlotData as $jsonSlotMap => $recipe){
					if($recipe->matchItems($inputSlotMap, $outputSlotMap)){
						$resultRecipe = $recipe;
						break;
					}
				}
			}
		}

		if(!is_null($resultRecipe)){
			$resultItem = $resultRecipe->getResult();
		}else{
			$resultItem = Item::get(Item::AIR, 0, 0);
		}
		$saveInventoryData[0] = $resultItem;

		$pk = new SetSlotPacket();
		$pk->windowId = $windowId;
		$pk->slotData = $resultItem;
		$pk->slot = 0;//result slot
		$this->player->putRawPacket($pk);
		var_dump(["resultItem" => $resultItem]);
	}

	/**
	 * @param int  $sourceType
	 * @param int  $windowId
	 * @param int  $inventorySlot
	 * @param Item $oldItem
	 * @param Item $newItem
	 * @return NetworkInventoryAction
	 */
	public function addNetworkInventoryAction(int $sourceType, int $windowId, int $inventorySlot, Item $oldItem, Item $newItem) : NetworkInventoryAction{
		$action = new NetworkInventoryAction();
		$action->sourceType = $sourceType;
		$action->windowId = $windowId;
		$action->inventorySlot = $inventorySlot;
		$action->oldItem = ItemStackWrapper::legacy($oldItem);
		$action->newItem = ItemStackWrapper::legacy($newItem);

		return $action;
	}

	/**
	 * @param InventoryTransactionPacket  $packet
	 * @return bool
	 * @throws UnexpectedValueException|ReflectionException
	 */
	public function checkInventoryTransactionPacket(InventoryTransactionPacket $packet) : bool{
		$errors = 0;
		$actions = [];
		foreach($packet->trData->getActions() as $actionNumber => $networkInventoryAction){
			$action = $networkInventoryAction->createInventoryAction($this->player);

			if($action === null){
				$errors++;
				if(DEBUG > 3){
					echo "[Action Number #".$actionNumber."] error action!\n";
				}
				continue;
			}

			if(DEBUG > 3){
				echo "[Action Number #".$actionNumber."] error nothing!\n";
			}

			$actions[] = $action;
		}

		foreach($actions as $actionNumber => $action){
			if($action instanceof SlotChangeAction){
				$windowName = (new ReflectionClass($action->getInventory()))->getShortName();
			}else{
				$windowName = "CreativeInventoryAction";
			}

			if($action->isValid($this->player)){
				if(DEBUG > 3){
					echo "[Action Number #".$actionNumber."][Window Name: ".$windowName."] error nothing!\n";
				}
			}else{
				if(DEBUG > 3){
					echo "[Action Number #".$actionNumber."][Window Name: ".$windowName."] invalid Item!\n";
					if($action instanceof SlotChangeAction){
						$checkItem = $action->getInventory()->getItem($action->getSlot());
						var_dump(["checkItem" => $checkItem, "sourceItem" => $action->getSourceItem()]);//json_encode
					}elseif($action instanceof CreativeInventoryAction){
						var_dump([
							$this->player->isCreative(true),
							($action->getActionType() === CreativeInventoryAction::TYPE_DELETE_ITEM or Item::getCreativeItemIndex($action->getSourceItem()) !== -1)
						]);
					}
				}
				$errors++;
				/*
				$check->equalsExact($action->sourceItem);*/
			}
		}

		if($errors > 0){
			return false;
		}
		return true;
	}

}
