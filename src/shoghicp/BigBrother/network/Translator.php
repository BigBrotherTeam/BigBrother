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

namespace shoghicp\BigBrother\network;

use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\utils\BinaryStream;
use shoghicp\BigBrother\network\protocol\Play\Client\AdvancementTabPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\ClientSettingsPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\ClientStatusPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\EntityActionPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\PlayerBlockPlacementPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\PlayerDiggingPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\PlayerRotationPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\PlayerMovementPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\PlayerPositionPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\UpdateSignPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\UpdateLightPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\UpdateViewDistancePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\UpdateViewPositionPacket;
use UnexpectedValueException;
use const pocketmine\DEBUG;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\particle\Particle;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddPaintingPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\BookEditPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo as Info;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\SetDifficultyPacket;
use pocketmine\network\mcpe\protocol\SetHealthPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\SetTimePacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use /** @noinspection PhpInternalEntityUsedInspection */
	pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\Player;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;

use shoghicp\BigBrother\BigBrother;
use shoghicp\BigBrother\DesktopChunk;
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\entity\ItemFrameBlockEntity;
use shoghicp\BigBrother\network\protocol\Login\LoginDisconnectPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\ClickWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\CloseWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\CreativeInventoryActionPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\InteractEntityPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityAnimationPacket as STCAnimatePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\BlockActionPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\BlockChangePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\BossBarPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ChangeGameStatePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ChatMessagePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ChunkDataPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\DestroyEntitiesPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EffectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityEffectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityEquipmentPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityHeadLookPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityRotationPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityMetadataPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityPropertiesPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityStatusPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityTeleportPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityVelocityPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\HeldItemChangePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\MapPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\JoinGamePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\KeepAlivePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\NamedSoundEffectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ParticlePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayDisconnectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayerAbilitiesPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayerInfoPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayerPositionAndLookPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PluginMessagePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\RemoveEntityEffectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\RespawnPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SelectAdvancementTabPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ServerDifficultyPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SetExperiencePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SpawnExperienceOrbPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SpawnLivingEntityPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SpawnEntityPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SpawnPaintingPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SpawnPlayerPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SpawnPositionPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\StatisticsPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\TimeUpdatePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\TitlePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\BlockEntityDataPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\UpdateHealthPacket;
use shoghicp\BigBrother\utils\ConvertUtils;
use shoghicp\BigBrother\utils\ColorUtils;
use stdClass;

class Translator{

	/**
	 * @param DesktopPlayer $player
	 * @param Packet        $packet
	 * @return DataPacket|array<DataPacket>|null
	 */
	public function interfaceToServer(DesktopPlayer $player, Packet $packet){
		switch($packet->pid()){
			case InboundPacket::TELEPORT_CONFIRM_PACKET://Teleport Confirm
			case InboundPacket::WINDOW_CONFIRMATION_PACKET://Transaction Confirm
			case InboundPacket::TAB_COMPLETE_PACKET:
				return null;

			case InboundPacket::CHAT_MESSAGE_PACKET:
				/** @var protocol\Play\Client\ChatMessagePacket $packet */
				$pk = new TextPacket();
				$pk->type = 1;//Chat Type
				$pk->sourceName = "";
				$pk->message = $packet->message;
				return $pk;

			case InboundPacket::CLIENT_STATUS_PACKET:
				/** @var ClientStatusPacket $packet */
				switch($packet->actionId){
					case 0:
						$pk = new PlayerActionPacket();
						$pk->entityRuntimeId = $player->getId();
						$pk->action = PlayerActionPacket::ACTION_RESPAWN;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;

						return $pk;
					case 1:
						//TODO: stat https://gist.github.com/Alvin-LB/8d0d13db00b3c00fd0e822a562025eff
						$statistic = [];

						$pk = new StatisticsPacket();
						$pk->count = count($statistic);
						$pk->statistic = $statistic;
						$player->putRawPacket($pk);
					break;
					default:
						echo "ClientStatusPacket: ".$packet->actionId."\n";
					break;
				}
				return null;

			case InboundPacket::CLIENT_SETTINGS_PACKET:
				/** @var ClientSettingsPacket $packet */
				$player->bigBrother_setClientSetting([
					"ChatMode" => $packet->chatMode,
					"ChatColor" => $packet->chatColors,
					"SkinSettings" => $packet->displayedSkinParts,
				]);

				$locale = $packet->lang[0].$packet->lang[1];
				if(isset($packet->lang[2])){
					$locale .= $packet->lang[2].strtoupper($packet->lang[3].$packet->lang[4]);
				}
				$player->setLocale($locale);

				$pk = new EntityMetadataPacket();
				$pk->entityId = $player->getId();
				$pk->metadata = [//Enable Display Skin Parts
					16 => [0, $packet->displayedSkinParts],
					"convert" => true,
				];
				$loggedInPlayers = $player->getServer()->getLoggedInPlayers();
				foreach($loggedInPlayers as $playerData){
					if($playerData instanceof DesktopPlayer){
						$playerData->putRawPacket($pk);
					}
				}

				$pk = new RequestChunkRadiusPacket();
				$pk->radius = $packet->viewDistance;

				return $pk;

			case InboundPacket::CLICK_WINDOW_PACKET:
				/** @var ClickWindowPacket $packet */
				$pk = $player->getInventoryUtils()->onWindowClick($packet);

				return $pk;

			case InboundPacket::CLOSE_WINDOW_PACKET:
				/** @var CloseWindowPacket $packet */
				$pk = $player->getInventoryUtils()->onWindowCloseFromPCtoPE($packet);

				return $pk;

			case InboundPacket::PLUGIN_MESSAGE_PACKET:
				/** @var PluginMessagePacket $packet */
				switch($packet->channel){
					case "minecraft:brand":
						//TODO: brand
					break;
					/*case "MC|BEdit":
						$packets = [];
						/** @var Item $item *//*
						$item = clone $packet->data[0];

						if(!is_null(($pages = $item->getNamedTagEntry("pages")))){
							foreach($pages as $pageNumber => $pageTags){
								if($pageTags instanceof CompoundTag){
									foreach($pageTags as $name => $tag){
										if($tag instanceof StringTag){
											if($tag->getName() === "text"){
												$pk = new BookEditPacket();
												$pk->type = BookEditPacket::TYPE_REPLACE_PAGE;
												$pk->inventorySlot = $player->getInventory()->getHeldItemIndex() + 9;
												$pk->pageNumber = (int) $pageNumber;
												$pk->text = $tag->getValue();
												$pk->photoName = "";//Not implement

												$packets[] = $pk;
											}
										}
									}
								}
							}
						}

						return $packets;
					case "MC|BSign":
						$packets = [];
						/** @var Item $item *//*
						$item = clone $packet->data[0];

						if(!is_null(($pages = $item->getNamedTagEntry("pages")))){
							foreach($pages as $pageNumber => $pageTags){
								if($pageTags instanceof CompoundTag){
									foreach($pageTags as $name => $tag){
										if($tag instanceof StringTag){
											if($tag->getName() === "text"){
												$pk = new BookEditPacket();
												$pk->type = BookEditPacket::TYPE_REPLACE_PAGE;
												$pk->inventorySlot = $player->getInventory()->getHeldItemIndex() + 9;
												$pk->pageNumber = (int) $pageNumber;
												$pk->text = $tag->getValue();
												$pk->photoName = "";//Not implement

												$packets[] = $pk;
											}
										}
									}
								}
							}
						}

						$pk = new BookEditPacket();
						$pk->type = BookEditPacket::TYPE_SIGN_BOOK;
						$pk->inventorySlot = $player->getInventory()->getHeldItemIndex();
						$pk->title = $item->getNamedTagEntry("title")->getValue();
						$pk->author = $item->getNamedTagEntry("author")->getValue();

						$packets[] = $pk;

						return $packets;
					break;*/
				}
				return null;

			case InboundPacket::INTERACT_ENTITY_PACKET:
				/** @var InteractEntityPacket $packet */
				$frame = ItemFrameBlockEntity::getItemFrameById($player->getLevel(), $packet->target);
				if($frame !== null){
					switch($packet->type){
						case InteractEntityPacket::TYPE_INTERACT:
							$pk = new InventoryTransactionPacket();
							$pk->requestChangedSlots = [];
							$pk->requestId = InventoryTransactionPacket::TYPE_USE_ITEM;
							$pk->trData = UseItemTransactionData::new(
								[],
								UseItemTransactionData::ACTION_CLICK_BLOCK,
								new Vector3($frame->x, $frame->y, $frame->z),
								$frame->getFacing(),
								$player->getInventory()->getHeldItemIndex(),
								ItemStackWrapper::legacy($player->getInventory()->getItemInHand()),
								$player->asVector3(),
								$frame->asVector3(),
								RuntimeBlockMapping::toStaticRuntimeId($player->getLevel()->getBlockIdAt($frame->x, $frame->y, $frame->z),$player->getLevel()->getBlockdataAt($frame->x, $frame->y, $frame->z))
							);

							return $pk;
						case InteractEntityPacket::TYPE_ATTACK:
							if($frame->hasItem()){
								$pk = new ItemFrameDropItemPacket();
								$pk->x = $frame->x;
								$pk->y = $frame->y;
								$pk->z = $frame->z;

								return $pk;
							}else{
								$pk = new InventoryTransactionPacket();
								$pk->requestChangedSlots = [];
								$pk->requestId = InventoryTransactionPacket::TYPE_USE_ITEM;
								$pk->trData = UseItemTransactionData::new(
									[],
									UseItemTransactionData::ACTION_CLICK_BLOCK,
									new Vector3($frame->x, $frame->y, $frame->z),
									$frame->getFacing(),
									$player->getInventory()->getHeldItemIndex(),
									ItemStackWrapper::legacy($player->getInventory()->getItemInHand()),
									$player->asVector3(),
									$frame->asVector3(),
									RuntimeBlockMapping::toStaticRuntimeId($player->getLevel()->getBlockIdAt($frame->x, $frame->y, $frame->z),$player->getLevel()->getBlockdataAt($frame->x, $frame->y, $frame->z))
								);

								return $pk;
							}
					}

					return null;
				}

				if($packet->type === InteractEntityPacket::TYPE_INTERACT_AT){
					$pk = new InteractPacket();
					$pk->target = $packet->target;
					$pk->action = InteractPacket::ACTION_MOUSEOVER;
					$pk->x = 0;
					$pk->y = 0;
					$pk->z = 0;
				}else{
					$actionType = null;
					switch($packet->type){
						case InteractEntityPacket::TYPE_INTERACT:
							$actionType = UseItemOnEntityTransactionData::ACTION_INTERACT;
						break;
						case InteractEntityPacket::TYPE_ATTACK:
							$actionType = UseItemOnEntityTransactionData::ACTION_ATTACK;
						break;
						default:
							echo "[Translator] UseItemPacket\n";
							return null;
					}

					$pk = new InventoryTransactionPacket();
					$pk->requestChangedSlots = [];
					$pk->requestId = InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY;
					$pk->trData = UseItemOnEntityTransactionData::new(
						[],
						$packet->target,
						$actionType,
						$player->getInventory()->getHeldItemIndex(),
						ItemStackWrapper::legacy($player->getInventory()->getItemInHand()),
						new Vector3(0, 0, 0),
						new Vector3(0, 0, 0)
					);
				}

				return $pk;

			case InboundPacket::KEEP_ALIVE_PACKET:
				$pk = new KeepAlivePacket();
				$pk->keepAliveId = mt_rand();
				$player->putRawPacket($pk);

				return null;

			case InboundPacket::PLAYER_MOVEMENT_PACKET:
				/** @var PlayerMovementPacket $packet */
				$player->onGround = $packet->onGround;

				return null;

			case InboundPacket::PLAYER_POSITION_PACKET:
				/** @var PlayerPositionPacket $packet */
				if($player->isImmobile()){
					$pk = new PlayerPositionAndLookPacket();
					$pk->x = $player->x;
					$pk->y = $player->y;
					$pk->z = $player->z;
					$pk->yaw = $player->yaw;
					$pk->pitch = $player->pitch;
					$pk->onGround = $player->isOnGround();
					$player->putRawPacket($pk);

					return null;
				}

				$packets = [];

				$pk = new MovePlayerPacket();
				$pk->position = new Vector3($packet->x, $packet->feetY + $player->getEyeHeight(), $packet->z);
				$pk->yaw = $player->yaw;
				$pk->headYaw = $player->yaw;
				$pk->pitch = $player->pitch;
				$packets[] = $pk;

				if($player->isOnGround() and !$packet->onGround){
					$pk = new PlayerActionPacket();
					$pk->entityRuntimeId = $player->getId();
					$pk->action = PlayerActionPacket::ACTION_JUMP;
					$pk->x = (int) $packet->x;
					$pk->y = (int) $packet->feetY;
					$pk->z = (int) $packet->z;
					$pk->face = 0;
					$packets[] = $pk;
				}

				return $packets;

			case InboundPacket::PLAYER_POSITION_AND_ROTATION_PACKET:
				/** @var protocol\Play\Client\PlayerPositionAndRotationPacket $packet */
				if($player->isImmobile()){
					$pk = new PlayerPositionAndLookPacket();
					$pk->x = $player->x;
					$pk->y = $player->y;
					$pk->z = $player->z;
					$pk->yaw = $player->yaw;
					$pk->pitch = $player->pitch;
					$pk->onGround = $player->isOnGround();
					$player->putRawPacket($pk);

					return null;
				}

				$packets = [];

				$pk = new MovePlayerPacket();
				$pk->position = new Vector3($packet->x, $packet->feetY + $player->getEyeHeight(), $packet->z);
				$pk->yaw = $packet->yaw;
				$pk->headYaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

				if($player->isOnGround() and !$packet->onGround){
					$pk = new PlayerActionPacket();
					$pk->entityRuntimeId = $player->getId();
					$pk->action = PlayerActionPacket::ACTION_JUMP;
					$pk->x = (int) $packet->x;
					$pk->y = (int) $packet->feetY;
					$pk->z = (int) $packet->z;
					$pk->face = 0;
					$packets[] = $pk;
				}

				return $packets;

			case InboundPacket::PLAYER_ROTATION_PACKET:
				/** @var PlayerRotationPacket $packet */
				if($player->isImmobile()){
					$pk = new PlayerPositionAndLookPacket();
					$pk->x = $player->x;
					$pk->y = $player->y;
					$pk->z = $player->z;
					$pk->yaw = $player->yaw;
					$pk->pitch = $player->pitch;
					$pk->onGround = $player->isOnGround();
					$player->putRawPacket($pk);

					return null;
				}

				$pk = new MovePlayerPacket();
				$pk->position = new Vector3($player->x, $player->y + $player->getEyeHeight(), $player->z);
				$pk->yaw = $packet->yaw;
				$pk->headYaw = $packet->yaw;
				$pk->pitch = $packet->pitch;

				return $pk;

			case InboundPacket::PLAYER_ABILITIES_PACKET:
				/** @var PlayerAbilitiesPacket $packet */
				$pk = new AdventureSettingsPacket();
				$pk->entityUniqueId = $player->getId();
				$pk->setFlag(AdventureSettingsPacket::FLYING, $packet->isFlying);

				return $pk;

			case InboundPacket::PLAYER_DIGGING_PACKET:
				/** @var PlayerDiggingPacket $packet */
				switch($packet->status){
					case 0:
						if($player->getGamemode() === 1){
							$pk = new InventoryTransactionPacket();
							$pk->requestChangedSlots = [];
							$pk->requestId = InventoryTransactionPacket::TYPE_USE_ITEM;
							$pk->trData = UseItemTransactionData::new(
								[],
								UseItemTransactionData::ACTION_BREAK_BLOCK,
								new Vector3($packet->x, $packet->y, $packet->z),
								$packet->face,
								$player->getInventory()->getHeldItemIndex(),
								ItemStackWrapper::legacy($player->getInventory()->getItemInHand()),
								new Vector3($player->getX(), $player->getY(), $player->getZ()),
								new Vector3($packet->x, $packet->y, $packet->z),
								RuntimeBlockMapping::toStaticRuntimeId($player->getLevel()->getBlockIdAt($packet->x, $packet->y, $packet->z),$player->getLevel()->getBlockdataAt($packet->x, $packet->y, $packet->z))
							);

							return $pk;
						}else{
							$player->bigBrother_setBreakPosition([new Vector3($packet->x, $packet->y, $packet->z), $packet->face]);

							$packets = [];

							$pk = new PlayerActionPacket();
							$pk->entityRuntimeId = $player->getId();
							$pk->action = PlayerActionPacket::ACTION_START_BREAK;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->face = $packet->face;
							$packets[] = $pk;

							$block = $player->getLevel()->getBlock(new Vector3($packet->x, $packet->y, $packet->z));
							if($block->getHardness() === (float) 0){
								$pk = new PlayerActionPacket();
								$pk->entityRuntimeId = $player->getId();
								$pk->action = PlayerActionPacket::ACTION_STOP_BREAK;
								$pk->x = $packet->x;
								$pk->y = $packet->y;
								$pk->z = $packet->z;
								$pk->face = $packet->face;
								$packets[] = $pk;

								$pk = new InventoryTransactionPacket();
								$pk->requestChangedSlots = [];
								$pk->requestId = InventoryTransactionPacket::TYPE_USE_ITEM;
								$pk->trData = UseItemTransactionData::new(
									[],
									UseItemTransactionData::ACTION_BREAK_BLOCK,
									new Vector3($packet->x, $packet->y, $packet->z),
									$packet->face,
									$player->getInventory()->getHeldItemIndex(),
									ItemStackWrapper::legacy($player->getInventory()->getItemInHand()),
									new Vector3($player->getX(), $player->getY(), $player->getZ()),
									new Vector3($packet->x, $packet->y, $packet->z),
									RuntimeBlockMapping::toStaticRuntimeId($player->getLevel()->getBlockIdAt($packet->x, $packet->y, $packet->z),$player->getLevel()->getBlockdataAt($packet->x, $packet->y, $packet->z))
								);
								$packets[] = $pk;

								$pk = new PlayerActionPacket();
								$pk->entityRuntimeId = $player->getId();
								$pk->action = PlayerActionPacket::ACTION_ABORT_BREAK;
								$pk->x = $packet->x;
								$pk->y = $packet->y;
								$pk->z = $packet->z;
								$pk->face = $packet->face;
								$packets[] = $pk;
							}

							return $packets;
						}
					case 1:
						$player->bigBrother_setBreakPosition([new Vector3(0, 0, 0), 0]);

						$pk = new PlayerActionPacket();
						$pk->entityRuntimeId = $player->getId();
						$pk->action = PlayerActionPacket::ACTION_ABORT_BREAK;
						$pk->x = $packet->x;
						$pk->y = $packet->y;
						$pk->z = $packet->z;
						$pk->face = $packet->face;

						return $pk;
					case 2:
						if($player->getGamemode() !== 1){
							$player->bigBrother_setBreakPosition([new Vector3(0, 0, 0), 0]);

							$packets = [];

							$pk = new PlayerActionPacket();
							$pk->entityRuntimeId = $player->getId();
							$pk->action = PlayerActionPacket::ACTION_STOP_BREAK;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->face = $packet->face;
							$packets[] = $pk;

							$pk = new InventoryTransactionPacket();
							$pk->requestChangedSlots = [];
							$pk->requestId = InventoryTransactionPacket::TYPE_USE_ITEM;
							$pk->trData = UseItemTransactionData::new(
								[],
								UseItemTransactionData::ACTION_BREAK_BLOCK,
								new Vector3($packet->x, $packet->y, $packet->z),
								$packet->face,
								$player->getInventory()->getHeldItemIndex(),
								ItemStackWrapper::legacy($player->getInventory()->getItemInHand()),
								new Vector3($player->getX(), $player->getY(), $player->getZ()),
								new Vector3($packet->x, $packet->y, $packet->z),
								RuntimeBlockMapping::toStaticRuntimeId($player->getLevel()->getBlockIdAt($packet->x, $packet->y, $packet->z),$player->getLevel()->getBlockdataAt($packet->x, $packet->y, $packet->z))
							);
							$packets[] = $pk;

							$pk = new PlayerActionPacket();
							$pk->entityRuntimeId = $player->getId();
							$pk->action = PlayerActionPacket::ACTION_ABORT_BREAK;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->face = $packet->face;
							$packets[] = $pk;

							return $packets;
						}else{
							echo "PlayerDiggingPacket: ".$packet->status."\n";
						}
					break;
					case 3:
					case 4:
						$newItem = clone $player->getInventory()->getItemInHand();
						$oldItem = clone $newItem;

						if(!$newItem->isNull()){
							if($packet->status === 4){
								$dropItem = $newItem->pop();
							}else{
								$dropItem = clone $newItem;
								$newItem = Item::get(Item::AIR, 0, 0);
							}

							$actions = [];
							$action = new NetworkInventoryAction();
							$action->sourceType = NetworkInventoryAction::SOURCE_WORLD;
							$action->sourceFlags = 0;
							$action->inventorySlot = 0;
							$action->oldItem = ItemStackWrapper::legacy(Item::get(Item::AIR, 0, 0));
							$action->newItem = ItemStackWrapper::legacy($dropItem);
							$actions[] = $action;

							$action = new NetworkInventoryAction();
							$action->sourceType = NetworkInventoryAction::SOURCE_CONTAINER;
							$action->windowId = ContainerIds::INVENTORY;
							$action->inventorySlot = $player->getInventory()->getHeldItemIndex();
							$action->oldItem = ItemStackWrapper::legacy($oldItem);
							$action->newItem = ItemStackWrapper::legacy($newItem);
							$actions[] = $action;

							/** @var InventoryTransactionPacket[] $packets */
							$packets = [];
							$pk = new InventoryTransactionPacket();
							$pk->requestChangedSlots = [];
							$pk->requestId = InventoryTransactionPacket::TYPE_NORMAL;
							$pk->trData = NormalTransactionData::new($actions);
							$packets[] = $pk;

							$pk = new InventoryTransactionPacket();
							$pk->requestChangedSlots = [];
							$pk->requestId = InventoryTransactionPacket::TYPE_MISMATCH;//inventory refresh.
							$pk->trData = MismatchTransactionData::new();
							$packets[] = $pk;

							return $packets;
						}

						return null;
					case 5:
						$item = $player->getInventory()->getItemInHand();

						$actionType = null;
						if($item->getId() === Item::BOW){//Shoot Arrow
							$actionType = ReleaseItemTransactionData::ACTION_RELEASE;
						}else{//Eating
							$actionType = ReleaseItemTransactionData::ACTION_CONSUME;
						}

						$pk = new InventoryTransactionPacket();
						$pk->requestChangedSlots = [];
						$pk->requestId = InventoryTransactionPacket::TYPE_RELEASE_ITEM;
						$pk->trData = ReleaseItemTransactionData::new(
							[],
							$actionType,
							$player->getInventory()->getHeldItemIndex(),
							ItemStackWrapper::legacy($item),
							new Vector3($packet->x, $packet->y, $packet->z)
						);

						return $pk;
					default:
						echo "PlayerDiggingPacket: ".$packet->status."\n";
					break;
				}

				return null;

			case InboundPacket::ENTITY_ACTION_PACKET:
				/** @var EntityActionPacket $packet */
				$pk = new PlayerActionPacket();
				$pk->entityRuntimeId = $player->getId();
				$pk->x = 0;
				$pk->y = 0;
				$pk->z = 0;
				$pk->face = 0;

				switch($packet->actionId){
					case 0://Start sneaking
						$pk->action = PlayerActionPacket::ACTION_START_SNEAK;

						return $pk;
					case 1://Stop sneaking
						$pk->action = PlayerActionPacket::ACTION_STOP_SNEAK;

						return $pk;
					case 2://leave bed
						$pk->action = PlayerActionPacket::ACTION_STOP_SLEEPING;

						return $pk;
					case 3://Start sprinting
						$pk->action = PlayerActionPacket::ACTION_START_SPRINT;

						return $pk;
					case 4://Stop sprinting
						$pk->action = PlayerActionPacket::ACTION_STOP_SPRINT;

						return $pk;
					default:
						echo "EntityActionPacket: ".$packet->actionId."\n";
					break;
				}

				return null;

			case InboundPacket::ADVANCEMENT_TAB_PACKET:
				/** @var AdvancementTabPacket $packet */
				if($packet->status === 0){
					$pk = new SelectAdvancementTabPacket();
					$pk->hasId = true;
					$pk->identifier = $packet->tabId;
					$player->putRawPacket($pk);
				}

				return null;

			case InboundPacket::HELD_ITEM_CHANGE_PACKET:
				/** @var HeldItemChangePacket $packet */
				$pk = new MobEquipmentPacket();
				$pk->entityRuntimeId = $player->getId();
				$pk->item = ItemStackWrapper::legacy($player->getInventory()->getHotbarSlotItem($packet->slot));
				$pk->inventorySlot = $packet->slot;
				$pk->hotbarSlot = $packet->slot;

				return $pk;

			case InboundPacket::CREATIVE_INVENTORY_ACTION_PACKET:
				/** @var CreativeInventoryActionPacket $packet */
				var_dump($packet);
				$pk = $player->getInventoryUtils()->onCreativeInventoryAction($packet);

				return $pk;

			case InboundPacket::UPDATE_SIGN_PACKET:
				/** @var UpdateSignPacket $packet */
				$tags = new CompoundTag("", [
					new StringTag("id", Tile::SIGN),
					new StringTag("Text1", $packet->line1),
					new StringTag("Text2", $packet->line2),
					new StringTag("Text3", $packet->line3),
					new StringTag("Text4", $packet->line4),
					new IntTag("x", (int) $packet->x),
					new IntTag("y", (int) $packet->y),
					new IntTag("z", (int) $packet->z)
				]);

				$nbt = new NetworkLittleEndianNBTStream();

				$pk = new BlockActorDataPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->namedtag = $nbt->write($tags);

				return $pk;

			case InboundPacket::ANIMATION_PACKET:
				$pk = new AnimatePacket();
				$pk->action = 1;
				$pk->entityRuntimeId = $player->getId();

				$pos = $player->bigBrother_getBreakPosition();
				/**
				 * @var Vector3[] $pos
				 * @phpstan-var array{Vector3, int} $pos
				 */
				if(!$pos[0]->equals(new Vector3(0, 0, 0))){
					$packets = [$pk];

					$pk = new PlayerActionPacket();
					$pk->entityRuntimeId = $player->getId();
					$pk->action = PlayerActionPacket::ACTION_CRACK_BREAK;
					$pk->x = $pos[0]->x;
					$pk->y = $pos[0]->y;
					$pk->z = $pos[0]->z;
					$pk->face = $pos[1];
					$packets[] = $pk;

					return $packets;
				}

				return $pk;

			case InboundPacket::PLAYER_BLOCK_PLACEMENT_PACKET:
				/** @var PlayerBlockPlacementPacket $packet */
				$blockClicked = $player->getLevel()->getBlock(new Vector3($packet->x, $packet->y, $packet->z));
				$blockReplace = $blockClicked->getSide($packet->face);

				if(ItemFrameBlockEntity::exists($player->getLevel(), $blockReplace->getX(), $blockReplace->getY(), $blockReplace->getZ())){
					$pk = new BlockChangePacket();//Cancel place block
					$pk->x = $blockReplace->getX();
					$pk->y = $blockReplace->getY();
					$pk->z = $blockReplace->getZ();
					$pk->blockId = Block::AIR;
					$pk->blockMeta = 0;
					$player->putRawPacket($pk);
					//TODO: convert block State Id

					return null;
				}

				$pk = new InventoryTransactionPacket();
				$pk->requestChangedSlots = [];
				$pk->requestId = InventoryTransactionPacket::TYPE_USE_ITEM;
				$pk->trData = UseItemTransactionData::new(
					[],
					UseItemTransactionData::ACTION_CLICK_BLOCK,
					new Vector3($packet->x, $packet->y, $packet->z),
					$packet->face,
					$player->getInventory()->getHeldItemIndex(),
					ItemStackWrapper::legacy($player->getInventory()->getItemInHand()),
					new Vector3($player->getX(), $player->getY(), $player->getZ()),
					new Vector3($packet->x, $packet->y, $packet->z),
					RuntimeBlockMapping::toStaticRuntimeId($player->getLevel()->getBlockIdAt($packet->x, $packet->y, $packet->z),$player->getLevel()->getBlockdataAt($packet->x, $packet->y, $packet->z))
				);

				return $pk;

			case InboundPacket::USE_ITEM_PACKET:
				if($player->getInventory()->getItemInHand()->getId() === Item::WRITTEN_BOOK){
					$pk = new PluginMessagePacket();
					$pk->channel = "MC|BOpen";
					$pk->data[] = 0;//main hand

					$player->putRawPacket($pk);
					return null;
				}

				$pk = new InventoryTransactionPacket();
				$pk->requestChangedSlots = [];
				$pk->requestId = InventoryTransactionPacket::TYPE_USE_ITEM;
				$pk->trData = UseItemTransactionData::new(
					[],
					UseItemTransactionData::ACTION_CLICK_AIR,
					new Vector3(0, 0, 0),
					-1,
					$player->getInventory()->getHeldItemIndex(),
					ItemStackWrapper::legacy($player->getInventory()->getItemInHand()),
					new Vector3($player->getX(), $player->getY(), $player->getZ()),
					new Vector3(0, 0, 0),
					RuntimeBlockMapping::toStaticRuntimeId(0)//check this
				);

				return $pk;

			default:
				if(DEBUG > 4){
					echo "[Receive][Translator] 0x".bin2hex(chr($packet->pid()))." Not implemented\n";
				}
				return null;
		}
	}

	/**
	 * @param DesktopPlayer $player
	 * @param DataPacket    $packet
	 * @return Packet|array<Packet>|null
	 * @throws UnexpectedValueException
	 */
	public function serverToInterface(DesktopPlayer $player, DataPacket $packet){
		switch($packet->pid()){
			case Info::PLAY_STATUS_PACKET:
				/** @var PlayStatusPacket $packet */
				if($packet->status === PlayStatusPacket::PLAYER_SPAWN){
					$pk = new PlayerPositionAndLookPacket();//for loading screen
					$pk->x = $player->getX();
					$pk->y = $player->getY();
					$pk->z = $player->getZ();
					$pk->yaw = 0;
					$pk->pitch = 0;
					$pk->flags = 0;

					return $pk;
				}

				return null;

			case Info::DISCONNECT_PACKET:
				/** @var DisconnectPacket $packet */
				if($player->bigBrother_getStatus() === 0){
					$pk = new LoginDisconnectPacket();
					$pk->reason = BigBrother::toJSON($packet->message);
				}else{
					$pk = new PlayDisconnectPacket();
					$pk->reason = BigBrother::toJSON($packet->message);
				}

				return $pk;

			case Info::TEXT_PACKET:
				/** @var TextPacket $packet */
				if($packet->message === "chat.type.achievement"){
					$packet->message = "chat.type.advancement.task";
				}

				$pk = new ChatMessagePacket();
				$pk->message = BigBrother::toJSON($packet->message, $packet->type, $packet->parameters);
				switch($packet->type){
					case TextPacket::TYPE_CHAT:
					case TextPacket::TYPE_TRANSLATION:
					case TextPacket::TYPE_WHISPER:
					case TextPacket::TYPE_RAW:
						$pk->position = 0;
						break;
					case TextPacket::TYPE_SYSTEM:
						$pk->position = 1;
						break;
					case TextPacket::TYPE_POPUP:
					case TextPacket::TYPE_TIP:
						$pk->position = 2;
						break;
				}
				$pk->sender = str_repeat("\x00", 16);//Setting both longs to 0 will always display the message regardless of the setting.

				return $pk;

			case Info::SET_TIME_PACKET:
				/** @var SetTimePacket $packet */
				$pk = new TimeUpdatePacket();
				$pk->worldAge = $packet->time;
				$pk->dayTime = $packet->time;
				return $pk;

			case Info::START_GAME_PACKET:
				/** @var StartGamePacket $packet */
				$packets = [];

				$pk = new JoinGamePacket();
				$pk->isHardcore = $player->getServer()->isHardcore();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->gamemode = $packet->playerGamemode;
				$pk->previousGamemode = -1;
				$pk->worldNames = ["minecraft:overworld", "minecraft:the_nether", "minecraft:the_end"];
				$pk->dimensionCodec = $player->getDimensionCodec();
				$pk->dimension = $player->getDimension();

				//$player->bigBrother_getDimensionPEToPC($packet->generator);
				$pk->worldName = "minecraft:overworld";//TODO: dimensionとセットなのでここを更新するときはそれ用のdimension.datを手に入れる必要がある
				$pk->hashedSeed = 0;
				$pk->maxPlayers = $player->getServer()->getMaxPlayers();
				$pk->viewDistance = 4;//default view Distance is 2 * 2.
				$pk->enableRespawnScreen = true;
				$packets[] = $pk;

				$pk = new PluginMessagePacket();
				$pk->channel = "minecraft:brand";
				$pk->data[] = "BigBrother";//displayed "BigBrother" server on debug mode
				$packets[] = $pk;

				$pk = new ServerDifficultyPacket();
				$pk->difficulty = $packet->difficulty;
				$packets[] = $pk;

				$pk = new SpawnPositionPacket();
				$pk->x = $packet->spawnX;
				$pk->y = $packet->spawnY;
				$pk->z = $packet->spawnZ;
				$packets[] = $pk;

				$pk = new UpdateViewPositionPacket();
				$pk->chunkX = $player->getX() >> 4;
				$pk->chunkZ = $player->getZ() >> 4;
				$packets[] = $pk;

				$pk = new PlayerAbilitiesPacket();
				$pk->flyingSpeed = 0.05;
				$pk->viewModifierField = 0.1;
				$pk->canFly = ($player->getGamemode() & 0x01) > 0;
				$pk->damageDisabled = ($player->getGamemode() & 0x01) > 0;
				$pk->isFlying = false;
				$pk->isCreative = ($player->getGamemode() & 0x01) > 0;
				$packets[] = $pk;

				return $packets;

			case Info::ADD_PLAYER_PACKET:
				/** @var AddPlayerPacket $packet */
				$packets = [];

				$pk = new SpawnPlayerPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->uuid = $packet->uuid->toBinary();
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

				$pk = new EntityMetadataPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->metadata = $packet->metadata;
				$packets[] = $pk;

				$pk = new EntityTeleportPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

				$pk = new EntityEquipmentPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->slot = 0;//main hand
				$pk->item = $packet->item->getItemStack();
				$packets[] = $pk;

				$pk = new EntityHeadLookPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->yaw = $packet->yaw;
				$packets[] = $pk;

				$playerData = null;
				$loggedInPlayers = $player->getServer()->getLoggedInPlayers();
				if(isset($loggedInPlayers[$packet->uuid->toBinary()])){
					$playerData = $loggedInPlayers[$packet->uuid->toBinary()];
				}

				$skinFlags = 0x7f;//enabled all flags
				if($playerData instanceof DesktopPlayer){
					if(isset($playerData->bigBrother_getClientSetting()["SkinSettings"])){
						$skinFlags = $playerData->bigBrother_getClientSetting()["SkinSettings"];
					}
				}

				$pk = new EntityMetadataPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->metadata = [//Enable Display Skin Parts
					16 => [0, $skinFlags],
					"convert" => true,
				];
				$packets[] = $pk;

				$player->bigBrother_addEntityList($packet->entityRuntimeId, "player");
				if(isset($packet->metadata[Entity::DATA_NAMETAG])){
					$player->bigBrother_setBossBarData("nameTag", $packet->metadata[Entity::DATA_NAMETAG]);
				}

				return $packets;

			case Info::ADD_ACTOR_PACKET:
				/** @var AddActorPacket $packet */
				return null;
				$packets = [];

				$isObject = false;
				$type = "generic";
				$data = 1;

				switch($packet->type){
					case 10://Chicken
						$type = "chicken";
						$packet->type = 93;
					break;
					case 11://Cow
						$type = "cow";
						$packet->type = 92;
					break;
					case 12://Pig
						$type = "pig";
						$packet->type = 90;
					break;
					case 13://Sheep
						$type = "sheep";
						$packet->type = 91;
					break;
					case 14://Wolf
						$type = "wolf";
						$packet->type = 95;
					break;
					case 15://Villager
						$type = "villager";
						$packet->type = 120;
					break;
					case 16://Moosh room
						$type = "cow";
						$packet->type = 96;
					break;
					case 17://Squid
						$type = "squid";
						$packet->type = 94;
					break;
					case 18://Rabbit
						$type = "rabbit";
						$packet->type = 101;
					break;
					case 19://Bat
						$type = "bat";
						$packet->type = 65;
					break;
					case 20://Iron Golem
						$type = "iron_golem";
						$packet->type = 99;
					break;
					case 21://Snow Golem (Snowman)
						$type = "snowman";
						$packet->type = 97;
					break;
					case 22://Ocelot
						$type = "cat";
						$packet->type = 98;
					break;
					case 23://Horse
						$type = "horse";
						$packet->type = 100;
					break;
					case 28://PolarBear
						$type = "polar_bear";
						$packet->type = 102;
					break;
					case 32://Zombie
						$type = "zombie";
						$packet->type = 54;
					break;
					case 33://Creeper
						$type = "creeper";
						$packet->type = 50;
					break;
					case 34://Skeleton
						$type = "skeleton";
						$packet->type = 51;
					break;
					case 35://Spider
						$type = "spider";
						$packet->type = 52;
					break;
					case 36://PigZombie
						$type = "zombie_pigman";
						$packet->type = 57;
					break;
					case 37://Slime
						$type = "slime";
						$packet->type = 55;
					break;
					case 38://Enderman
						$type = "enderman";
						$packet->type = 58;
					break;
					case 39://Silverfish
						$type = "silverfish";
						$packet->type = 60;
					break;
					case 40://CaveSpider
						$type = "spider";
						$packet->type = 59;
					break;
					case 41://Ghast
						$type = "ghast";
						$packet->type = 56;
					break;
					case 42://Lava Slime
						$type = "magmacube";
						$packet->type = 62;
					break;
					case 43://Blaze
						$type = "blaze";
						$packet->type = 61;
					break;
					case 44://ZombieVillager
						$type = "zombie_village";
						$packet->type = 27;
					break;
					case 45://Witch
						$type = "witch";
						$packet->type = 66;
					break;
					case 46://Stray
						$type = "stray";
						$packet->type = 6;
					break;
					case 47://Husk
						$type = "husk";
						$packet->type = 23;
					break;
					case 48://WitherSkeleton
						$type = "wither_skeleton";
						$packet->type = 5;
					break;
					case 49://Guardian
						$type = "guardian";
						$packet->type = 68;
					break;
					case 50://ElderGuardian
						$type = "elder_guardian";
						$packet->type = 4;
					break;
					/*case 52://Wither (Skull)
						//Spawn Object
					break;*/
					case 53://EnderDragon
						$type = "enderdragon";
						$packet->type = 63;
					break;
					case 54://Shulker
						$type = "shulker";
						$packet->type = 69;
					break;
					case 61://ArmorStand
						//Spawn Object
						$isObject = true;
						$packet->type = 78;
					break;
					/*case 64://Item
						//Spawn Object
					break;*/
					case 65://PrimedTNT
						//Spawn Object
						$isObject = true;
						$packet->type = 50;
					break;
					case 66://FallingSand
						//Spawn Object
						$isObject = true;
						$packet->type = 70;

						$block = $packet->metadata[2][1];//block data
						$blockId = $block & 0xff;
						$blockDamage = $block >> 8;

						ConvertUtils::convertBlockData(true, $blockId, $blockDamage);

						$data = $blockId | ($blockDamage << 12);
					break;
					case 68://ThrownExpBottle
						$isObject = true;
						$packet->type = 75;
					break;
					case 69://XPOrb
						$entity = $player->getLevel()->getEntity($packet->entityRuntimeId);

						$pk = new SpawnExperienceOrbPacket();
						$pk->entityId = $packet->entityRuntimeId;
						$pk->x = $packet->position->x;
						$pk->y = $packet->position->y;
						$pk->z = $packet->position->z;
						$pk->count = $entity->namedtag["Value"];

						return $pk;
					/*
					case 71://EnderCrystal
						//Spawn Object
					break;
					case 76://ShulkerBullet
						//Spawn Object
					break;*/
					case 77://FishingHook
						//Spawn Object
						$isObject = true;
						$packet->type = 90;
					break;
					/*case 79://DragonFireBall
						//Spawn Object
					break;*/
					case 80://Arrow
						//Spawn Object
						$isObject = true;
						$packet->type = 60;
					break;
					case 81://Snowball
						//Spawn Object
						$isObject = true;
						$packet->type = 61;
					break;
					case 82://Egg
						//Spawn Object
						$isObject = true;
						$packet->type = 62;
					break;
					/*case 83://Painting
						//Spawn Painting
					break;
					case 84://Minecart
						//Spawn Object
					break;
					case 85://GhastFireball
						//Spawn Object
					break;
					case 86://ThrownPotion
						//Spawn Object
					break;
					case 87://EnderPearl
						//Spawn Object
					break;
					case 88://LeashKnot
						//Spawn Object
					break;
					case 89://BlueWitherSkull
						//Spawn Object
					break;*/
					case 90;//Boat
						$packet->type = 1;
					break;
					case 93://Lightning
						$pk = new SpawnGlobalEntityPacket();
						$pk->eid = $packet->entityRuntimeId;
						$pk->type = SpawnGlobalEntityPacket::TYPE_LIGHTNING;
						$pk->x = $packet->position->x;
						$pk->y = $packet->position->y;
						$pk->z = $packet->position->z;
						return $pk;
					/*case 94://BlazeFireball
						//Spawn Object
					break;
					case 96://Minecart Hopper
						//Spawn Object
					break;
					case 97:Minecart TNT
						//Spawn Object
					break;
					case 98://Minecart Chest
						//Spawn Object
					break;*/
					default:
						$packet->type = 57;
						echo "AddEntityPacket: ".$packet->entityRuntimeId."\n";
					break;
				}

				if($isObject){
					$pk = new SpawnEntityPacket();
					$pk->entityId = $packet->entityRuntimeId;
					$pk->type = $packet->type;
					$pk->uuid = UUID::fromRandom()->toBinary();
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y;
					$pk->z = $packet->position->z;
					$pk->yaw = 0;
					$pk->pitch = 0;
					$pk->data = $data;
					if($data > 0){
						$pk->sendVelocity = true;
						$pk->velocityX = 0;
						$pk->velocityY = 0;
						$pk->velocityZ = 0;
					}

					$packets[] = $pk;

					$pk = new EntityMetadataPacket();
					$pk->entityId = $packet->entityRuntimeId;
					$pk->metadata = $packet->metadata;
				}else{
					$pk = new SpawnLivingEntityPacket();
					$pk->entityId = $packet->entityRuntimeId;
					$pk->type = $packet->type;
					$pk->uuid = UUID::fromRandom()->toBinary();
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y;
					$pk->z = $packet->position->z;
					$pk->yaw = $packet->yaw;
					$pk->pitch = $packet->pitch;
					$pk->headPitch = 0;
					$pk->metadata = $packet->metadata;
				}

				$packets[] = $pk;

				$pk = new EntityTeleportPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

				$player->bigBrother_addEntityList($packet->entityRuntimeId, $type);
				if(isset($packet->metadata[Entity::DATA_NAMETAG])){
					$player->bigBrother_setBossBarData("nameTag", $packet->metadata[Entity::DATA_NAMETAG]);
				}

				return $packets;

			case Info::REMOVE_ACTOR_PACKET:
				/** @var RemoveActorPacket $packet */
				$packets = [];

				if($packet->entityUniqueId === $player->bigBrother_getBossBarData("entityRuntimeId")){
					$uuid = $player->bigBrother_getBossBarData("uuid");
					if($uuid === ""){
						return null;
					}
					$pk = new BossBarPacket();
					$pk->uuid = $uuid;
					$pk->actionId = BossBarPacket::TYPE_REMOVE;

					$player->bigBrother_setBossBarData("entityRuntimeId", -1);
					$player->bigBrother_setBossBarData("uuid", "");

					$packets[] = $pk;
				}
				$pk = new DestroyEntitiesPacket();
				$pk->entityIds[] = $packet->entityUniqueId;

				$player->bigBrother_removeEntityList($packet->entityUniqueId);

				$packets[] = $pk;

				return $packets;

			case Info::ADD_ITEM_ACTOR_PACKET:
				/** @var AddItemActorPacket $packet */
				$item = clone $packet->item->getItemStack();
				ConvertUtils::convertItemData(true, $item);
				$metadata = ConvertUtils::convertPEToPCMetadata($packet->metadata);
				$metadata[6] = [7, $item];//6

				$packets = [];

				$pk = new SpawnEntityPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->uuid = UUID::fromRandom()->toBinary();
				$pk->type = SpawnEntityPacket::ITEM_STACK;
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = 0;
				$pk->pitch = 0;
				$pk->data = 1;
				$pk->sendVelocity = true;
				$pk->velocityX = $packet->motion->x;
				$pk->velocityY = $packet->motion->y;
				$pk->velocityZ = $packet->motion->z;
				$packets[] = $pk;

				$pk = new EntityMetadataPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->metadata = $metadata;
				$packets[] = $pk;

				return $packets;

			case Info::TAKE_ITEM_ACTOR_PACKET:
				/** @var TakeItemActorPacket $packet */
				$pk = $player->getInventoryUtils()->onTakeItemEntity($packet);

				return $pk;

			case Info::MOVE_ACTOR_ABSOLUTE_PACKET:
				/** @var MoveActorAbsolutePacket $packet */
				if($packet->entityRuntimeId === $player->getId()){//TODO
					return null;
				}else{
					$baseOffset = 0;
					$isOnGround = true;
					$entity = $player->getLevel()->getEntity($packet->entityRuntimeId);
					if($entity instanceof Entity){
						switch($entity::NETWORK_ID){
							case -1://Player
								$baseOffset = 1.62;
							break;
							case 64://Item
								$baseOffset = 0.125;
							break;
							case 65://PrimedTNT
							case 66://FallingSand
								$baseOffset = 0.49;
							break;
						}

						$isOnGround = $entity->isOnGround();
					}

					$packets = [];

					$pk = new EntityTeleportPacket();
					$pk->entityId = $packet->entityRuntimeId;
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y - $baseOffset;
					$pk->z = $packet->position->z;
					$pk->yaw = $packet->zRot;
					$pk->pitch = $packet->xRot;
					$packets[] = $pk;

					$pk = new EntityRotationPacket();
					$pk->entityId = $packet->entityRuntimeId;
					$pk->yaw = $packet->yRot;
					$pk->pitch = $packet->xRot;
					$pk->onGround = $isOnGround;
					$packets[] = $pk;

					$pk = new EntityHeadLookPacket();
					$pk->entityId = $packet->entityRuntimeId;
					$pk->yaw = $packet->yRot;
					$packets[] = $pk;

					return $packets;
				}

			case Info::MOVE_PLAYER_PACKET:
				/** @var MovePlayerPacket $packet */
				if($packet->entityRuntimeId === $player->getId()){
					if($player->spawned){//for Loading Chunks
						$pk = new PlayerPositionAndLookPacket();//
						$pk->x = $packet->position->x;
						$pk->y = $packet->position->y - $player->getEyeHeight();
						$pk->z = $packet->position->z;
						$pk->yaw = $packet->yaw;
						$pk->pitch = $packet->pitch;
						$pk->onGround = $player->isOnGround();

						return $pk;
					}
				}else{
					$packets = [];

					$pk = new EntityTeleportPacket();
					$pk->entityId = $packet->entityRuntimeId;
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y - $player->getEyeHeight();
					$pk->z = $packet->position->z;
					$pk->yaw = $packet->yaw;
					$pk->pitch = $packet->pitch;
					$packets[] = $pk;

					$pk = new EntityRotationPacket();
					$pk->entityId = $packet->entityRuntimeId;
					$pk->yaw = $packet->headYaw;
					$pk->pitch = $packet->pitch;
					$pk->onGround = $packet->onGround;
					$packets[] = $pk;

					$pk = new EntityHeadLookPacket();
					$pk->entityId = $packet->entityRuntimeId;
					$pk->yaw = $packet->headYaw;
					$packets[] = $pk;

					return $packets;
				}

				return null;

			case Info::UPDATE_BLOCK_PACKET:
				/** @var UpdateBlockPacket $packet */
				/** @noinspection PhpInternalEntityUsedInspection */
				$block = RuntimeBlockMapping::fromStaticRuntimeId($packet->blockRuntimeId);

				if(($entity = ItemFrameBlockEntity::getItemFrame($player->getLevel(), $packet->x, $packet->y, $packet->z)) !== null){
					if($block[0] !== Block::FRAME_BLOCK){
						$entity->despawnFrom($player);

						ItemFrameBlockEntity::removeItemFrame($entity);
					}else{
						if(($packet->flags & UpdateBlockPacket::FLAG_NEIGHBORS) == 0){
							$entity->spawnTo($player);
						}

						return null;
					}
				}else{
					if($block[0] === Block::FRAME_BLOCK){
						$entity = ItemFrameBlockEntity::getItemFrame($player->getLevel(), $packet->x, $packet->y, $packet->z, $block[1], true);
						$entity->spawnTo($player);

						return null;
					}
				}

				ConvertUtils::convertBlockData(true, $block[0], $block[1]);

				$pk = new BlockChangePacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->blockId = $block[0];
				$pk->blockMeta = $block[1];
				//TODO: convert block State Id

				return $pk;

			case Info::ADD_PAINTING_PACKET:
				/** @var AddPaintingPacket $packet */
				$spawnPaintingPos = (new Vector3($packet->position->x, $packet->position->y, $packet->position->z))->floor();
				$motives = ["Plant" => 5];

				echo $packet->title."\n";

				$pk = new SpawnPaintingPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->uuid = UUID::fromRandom()->toBinary();
				$pk->x = $spawnPaintingPos->x;
				$pk->y = $spawnPaintingPos->y;
				$pk->z = $spawnPaintingPos->z;
				$pk->motive = $motives[$packet->title];
				$pk->direction = $packet->direction;

				return $pk;

			case Info::CHANGE_DIMENSION_PACKET:
				/** @var ChangeDimensionPacket $packet */
				$pk = new RespawnPacket();
				$pk->dimension = $player->getDimension();
				$pk->worldName = "minecraft:overworld";
				$pk->hashedSeed = 0;
				$pk->gamemode = $player->getGamemode();
				$pk->previousGamemode = -1;

				$player->bigBrother_respawn();

				return $pk;

			case Info::PLAY_SOUND_PACKET:
				/** @var PlaySoundPacket $packet */
				$pk = new NamedSoundEffectPacket();
				$pk->soundCategory = 0;
				$pk->effectPositionX = (int) $packet->x;
				$pk->effectPositionY = (int) $packet->y;
				$pk->effectPositionZ = (int) $packet->z;
				$pk->volume = $packet->volume * 0.25;
				$pk->pitch = $packet->pitch;
				$pk->soundName = $packet->soundName;

				return $pk;

			case Info::LEVEL_SOUND_EVENT_PACKET:
				/** @var LevelSoundEventPacket $packet */
				$volume = 1;
				$pitch = $packet->extraData;

				switch($packet->sound){
					case LevelSoundEventPacket::SOUND_EXPLODE:
						$isSoundEffect = true;
						$category = 0;

						$name = "entity.generic.explode";
					break;
					case LevelSoundEventPacket::SOUND_CHEST_OPEN:
						$isSoundEffect = true;
						$category = 1;

						$blockId = $player->getLevel()->getBlock($packet->position)->getId();
						if($blockId === Block::ENDER_CHEST){
							$name = "block.enderchest.open";
						}else{
							$name = "block.chest.open";
						}
					break;
					case LevelSoundEventPacket::SOUND_CHEST_CLOSED:
						$isSoundEffect = true;
						$category = 1;

						$blockId = $player->getLevel()->getBlock($packet->position)->getId();
						if($blockId === Block::ENDER_CHEST){
							$name = "block.enderchest.close";
						}else{
							$name = "block.chest.close";
						}
					break;
					case LevelSoundEventPacket::SOUND_NOTE:
						$isSoundEffect = true;
						$category = 2;
						$volume = 3;
						$name = "block.note.harp";//TODO

						$pitch /= 2.0;
					break;
					case LevelSoundEventPacket::SOUND_PLACE://unused
						return null;
					default:
						if(DEBUG > 3){
							echo "LevelSoundEventPacket: ".$packet->sound."\n";
						}
						return null;
				}

				if($isSoundEffect){
					$pk = new NamedSoundEffectPacket();
					$pk->soundCategory = $category;
					$pk->effectPositionX = (int) $packet->position->x;
					$pk->effectPositionY = (int) $packet->position->y;
					$pk->effectPositionZ = (int) $packet->position->z;
					$pk->volume = $volume;
					$pk->pitch = $pitch;
					$pk->soundName = $name;

					return $pk;
				}

				return null;

			case Info::LEVEL_EVENT_PACKET://TODO
				/** @var LevelEventPacket $packet */
				$isSoundEffect = false;
				$isParticle = false;
				$addData = [];
				$category = 0;
				$name = "";
				$id = 0;

				switch($packet->evid){
					case LevelEventPacket::EVENT_SOUND_IGNITE:
						$isSoundEffect = true;
						$name = "entity.tnt.primed";
					break;
					case LevelEventPacket::EVENT_SOUND_SHOOT:
						$isSoundEffect = true;

						switch(($id = $player->getInventory()->getItemInHand()->getId())){
							case Item::SNOWBALL:
								$name = "entity.snowball.throw";
							break;
							case Item::EGG:
								$name = "entity.egg.throw";
							break;
							case Item::BOTTLE_O_ENCHANTING:
								$name = "entity.experience_bottle.throw";
							break;
							case Item::SPLASH_POTION:
								$name = "entity.splash_potion.throw";
							break;
							case Item::BOW:
								$name = "entity.arrow.shoot";
							break;
							case 368:
								$name = "entity.enderpearl.throw";
							break;
							default:
								$name = "entity.snowball.throw";

								if(DEBUG > 3){
									echo "LevelEventPacket: ".$id."\n";
								}
							break;
						}
					break;
					case LevelEventPacket::EVENT_SOUND_DOOR:
						$isSoundEffect = true;

						$block = $player->getLevel()->getBlock($packet->position);

						switch($block->getId()){
							case Block::WOODEN_DOOR_BLOCK:
							case Block::SPRUCE_DOOR_BLOCK:
							case Block::BIRCH_DOOR_BLOCK:
							case Block::JUNGLE_DOOR_BLOCK:
							case Block::ACACIA_DOOR_BLOCK:
							case Block::DARK_OAK_DOOR_BLOCK:
								if(($block->getDamage() & 0x04) === 0x04){
									$name = "block.wooden_door.open";
								}else{
									$name = "block.wooden_door.close";
								}
							break;
							case Block::IRON_DOOR_BLOCK:
								if(($block->getDamage() & 0x04) === 0x04){
									$name = "block.iron_door.open";
								}else{
									$name = "block.iron_door.close";
								}
							break;
							case Block::TRAPDOOR:
								if(($block->getDamage() & 0x08) === 0x08){
									$name = "block.wooden_trapdoor.open";
								}else{
									$name = "block.wooden_trapdoor.close";
								}
							break;
							case Block::IRON_TRAPDOOR:
								if(($block->getDamage() & 0x08) === 0x08){
									$name = "block.iron_trapdoor.open";
								}else{
									$name = "block.iron_trapdoor.close";
								}
							break;
							case Block::OAK_FENCE_GATE:
							case Block::SPRUCE_FENCE_GATE:
							case Block::BIRCH_FENCE_GATE:
							case Block::JUNGLE_FENCE_GATE:
							case Block::DARK_OAK_FENCE_GATE:
							case Block::ACACIA_FENCE_GATE:
								if(($block->getDamage() & 0x04) === 0x04){
									$name = "block.fence_gate.open";
								}else{
									$name = "block.fence_gate.close";
								}
							break;
							default:
								echo "[LevelEventPacket] Unknown DoorSound\n";
								return null;
						}
					break;
					case LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_CRITICAL:
						$isParticle = true;
						$id = 9;
					break;
					case LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_HUGE_EXPLODE_SEED:
						$isParticle = true;
						$id = 2;
					break;
					case LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_TERRAIN:
						$isParticle = true;

						/** @noinspection PhpInternalEntityUsedInspection */
						$block = RuntimeBlockMapping::fromStaticRuntimeId($packet->data);//block data
						ConvertUtils::convertBlockData(true, $block[0], $block[1]);

						$packet->data = $block[0] | ($block[1] << 12);

						$id = 37;
						$addData = [
							$packet->data
						];
					break;
					case LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_DUST:
						$isParticle = true;
						$id = 46;
						$addData = [
							$packet->data//TODO: RGBA
						];
					break;
					/*case LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_INK:
					break;*/
					case LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_SNOWBALL_POOF:
						$isParticle = true;
						$id = 31;
					break;
					case LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_ITEM_BREAK:
						//TODO
					break;
					case LevelEventPacket::EVENT_PARTICLE_DESTROY:
						/** @noinspection PhpInternalEntityUsedInspection */
						$block = RuntimeBlockMapping::fromStaticRuntimeId($packet->data);//block data
						ConvertUtils::convertBlockData(true, $block[0], $block[1]);

						$packet->data = $block[0] | ($block[1] << 12);
					break;
					case LevelEventPacket::EVENT_PARTICLE_PUNCH_BLOCK:
						//TODO: BreakAnimation
						return null;
					case LevelEventPacket::EVENT_BLOCK_START_BREAK:
						//TODO: set BreakTime
						return null;
					case LevelEventPacket::EVENT_BLOCK_STOP_BREAK:
						//TODO: remove BreakTime

						return null;
					default:
						if(($packet->evid & LevelEventPacket::EVENT_ADD_PARTICLE_MASK) === LevelEventPacket::EVENT_ADD_PARTICLE_MASK){
							$packet->evid ^= LevelEventPacket::EVENT_ADD_PARTICLE_MASK;
						}

						echo "LevelEventPacket: ".$packet->evid."\n";
						return null;
				}

				if($isSoundEffect){
					$pk = new NamedSoundEffectPacket();
					$pk->soundCategory = $category;
					$pk->effectPositionX = (int) $packet->position->x;
					$pk->effectPositionY = (int) $packet->position->y;
					$pk->effectPositionZ = (int) $packet->position->z;
					$pk->volume = 0.5;
					$pk->pitch = 1.0;
					$pk->soundName = $name;
				}elseif($isParticle){
					$pk = new ParticlePacket();
					$pk->particleId = $id;
					$pk->longDistance = false;
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y;
					$pk->z = $packet->position->z;
					$pk->offsetX = 0;
					$pk->offsetY = 0;
					$pk->offsetZ = 0;
					$pk->particleData = $packet->data;
					$pk->particleCount = 1;
					$pk->data = $addData;//!!!!!!!!!!!!!!!!!!!!!!!!!!!
				}else{
					$pk = new EffectPacket();
					$pk->effectId = $packet->evid;
					$pk->x = (int) $packet->position->x;
					$pk->y = (int) $packet->position->y;
					$pk->z = (int) $packet->position->z;
					$pk->data = $packet->data;
					$pk->disableRelativeVolume = false;
				}

				return $pk;

			case Info::BLOCK_EVENT_PACKET:
				/** @var BlockEventPacket $packet */
				$pk = new BlockActionPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->actionId = $packet->eventType;
				$pk->actionParam = $packet->eventData;
				$pk->blockType = $blockId = $player->getLevel()->getBlock(new Vector3($packet->x, $packet->y, $packet->z))->getId();

				return $pk;

			case Info::SET_TITLE_PACKET:
				/** @var SetTitlePacket $packet */
				switch($packet->type){
					case SetTitlePacket::TYPE_CLEAR_TITLE:
						$pk = new TitlePacket();
						$pk->actionId = TitlePacket::TYPE_HIDE;

						return $pk;
					case SetTitlePacket::TYPE_RESET_TITLE:
						$pk = new TitlePacket();
						$pk->actionId = TitlePacket::TYPE_RESET;

						return $pk;
					case SetTitlePacket::TYPE_SET_TITLE:
						$pk = new TitlePacket();
						$pk->actionId = TitlePacket::TYPE_SET_TITLE;
						$pk->data = BigBrother::toJSON($packet->text);

						return $pk;
					case SetTitlePacket::TYPE_SET_SUBTITLE:
						$pk = new TitlePacket();
						$pk->actionId = TitlePacket::TYPE_SET_SUB_TITLE;
						$pk->data = BigBrother::toJSON($packet->text);

						return $pk;
					case SetTitlePacket::TYPE_SET_ACTIONBAR_MESSAGE:
						$pk = new TitlePacket();
						$pk->actionId = TitlePacket::TYPE_SET_ACTION_BAR;
						$pk->data = BigBrother::toJSON($packet->text);

						return $pk;
					case SetTitlePacket::TYPE_SET_ANIMATION_TIMES:
						$pk = new TitlePacket();
						$pk->actionId = TitlePacket::TYPE_SET_SETTINGS;
						$pk->data = [];
						$pk->data[0] = $packet->fadeInTime;
						$pk->data[1] = $packet->stayTime;
						$pk->data[2] = $packet->fadeOutTime;

						return $pk;
					default:
						echo "SetTitlePacket: ".$packet->type."\n";
					break;
				}

				return null;

			case Info::ACTOR_EVENT_PACKET:
				/** @var ActorEventPacket $packet */
				switch($packet->event){
					case ActorEventPacket::HURT_ANIMATION:
						$type = $player->bigBrother_getEntityList($packet->entityRuntimeId);

						$packets = [];

						$pk = new EntityStatusPacket();
						$pk->entityStatus = 2;
						$pk->entityId = $packet->entityRuntimeId;
						$packets[] = $pk;

						$pk = new NamedSoundEffectPacket();
						$pk->soundCategory = 0;
						$pk->effectPositionX = (int) $player->getX();
						$pk->effectPositionY = (int) $player->getY();
						$pk->effectPositionZ = (int) $player->getZ();
						$pk->volume = 0.5;
						$pk->pitch = 1.0;
						$pk->soundName = "entity.".$type.".hurt";
						$packets[] = $pk;

						return $packets;
					case ActorEventPacket::DEATH_ANIMATION:
						$type = $player->bigBrother_getEntityList($packet->entityRuntimeId);

						$packets = [];

						$pk = new EntityStatusPacket();
						$pk->entityStatus = 3;
						$pk->entityId = $packet->entityRuntimeId;
						$packets[] = $pk;

						$pk = new NamedSoundEffectPacket();
						$pk->soundCategory = 0;
						$pk->effectPositionX = (int) $player->getX();
						$pk->effectPositionY = (int) $player->getY();
						$pk->effectPositionZ = (int) $player->getZ();
						$pk->volume = 0.5;
						$pk->pitch = 1.0;
						$pk->soundName = "entity.".$type.".death";
						$packets[] = $pk;

						return $packets;
					case ActorEventPacket::RESPAWN:
						//unused
					break;
					default:
						if(DEBUG > 3){
							echo "EntityEventPacket: ".$packet->event."\n";
						}
					break;
				}

				return null;

			case Info::MOB_EFFECT_PACKET:
				/** @var MobEffectPacket $packet */
				switch($packet->eventId){
					case MobEffectPacket::EVENT_ADD:
					case MobEffectPacket::EVENT_MODIFY:
						$flags = 0;
						if($packet->particles){
							$flags |= 0x02;
						}

						$pk = new EntityEffectPacket();
						$pk->entityId = $packet->entityRuntimeId;
						$pk->effectId = $packet->effectId;
						$pk->amplifier = $packet->amplifier;
						$pk->duration = $packet->duration;
						$pk->flags = $flags;

						return $pk;
					case MobEffectPacket::EVENT_REMOVE:
						$pk = new RemoveEntityEffectPacket();
						$pk->entityId = $packet->entityRuntimeId;
						$pk->effectId = $packet->effectId;

						return $pk;
					default:
						echo "MobEffectPacket: ".$packet->eventId."\n";
					break;
				}

				return null;

			case Info::UPDATE_ATTRIBUTES_PACKET:
				/** @var UpdateAttributesPacket $packet */
				$packets = [];
				$entries = [];

				foreach($packet->entries as $entry){
					switch($entry->getName()){
						case "minecraft:player.saturation": //TODO
						case "minecraft:player.exhaustion": //TODO
						case "minecraft:absorption": //TODO
						break;
						case "minecraft:player.hunger": //move to minecraft:health
						break;
						case "minecraft:health":
							if($packet->entityRuntimeId === $player->getId()){
								$pk = new UpdateHealthPacket();
								$pk->health = $entry->getValue();//TODO: Default Value
								$pk->food = (int) $player->getFood();//TODO: Default Value
								$pk->foodSaturation = $player->getSaturation();//TODO: Default Value
							}else{
								$pk = new EntityMetadataPacket();
								$pk->entityId = $packet->entityRuntimeId;
								$pk->metadata = [
									8 => [2, $entry->getValue()],
									"convert" => true,
								];
							}

							$packets[] = $pk;
						break;
						case "minecraft:movement":
							$entries[] = [
								"generic.movement_speed",
								$entry->getValue()//TODO: Default Value
							];
						break;
						case "minecraft:player.level": //move to minecraft:player.experience
						break;
						case "minecraft:player.experience":
							if($packet->entityRuntimeId === $player->getId()){
								$pk = new SetExperiencePacket();
								$pk->experienceBar = $entry->getValue();//TODO: Default Value
								$pk->level = $player->getXpLevel();//TODO: Default Value
								$pk->totalExperience = $player->getLifetimeTotalXp();//TODO: Default Value

								$packets[] = $pk;
							}
						break;
						case "minecraft:attack_damage":
							$entries[] = [
								"generic.attack_damage",
								$entry->getValue()//TODO: Default Value
							];
						break;
						case "minecraft:knockback_resistance":
							$entries[] = [
								"generic.knockback_resistance",
								$entry->getValue()//TODO: Default Value
							];
						break;
						case "minecraft:follow_range":
							$entries[] = [
								"generic.follow_range",
								$entry->getValue()//TODO: Default Value
							];
						break;
						default:
							echo "UpdateAtteributesPacket: ".$entry->getName()."\n";
						break;
					}
				}

				if(count($entries) > 0){
					$pk = new EntityPropertiesPacket();
					$pk->entityId = $packet->entityRuntimeId;
					$pk->entries = $entries;
					$packets[] = $pk;
				}

				return $packets;

			case Info::MOB_EQUIPMENT_PACKET:
				/** @var MobEquipmentPacket $packet */
				$packets = [];

				if($packet->entityRuntimeId === $player->getId()){
					$pk = new HeldItemChangePacket();
					$pk->slot = $packet->hotbarSlot;
					$packets[] = $pk;
				}

				$pk = new EntityEquipmentPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->slot = 0;//main hand
				$pk->item = $packet->item->getItemStack();

				if(count($packets) > 0){
					$packets[] = $pk;

					return $packets;
				}

				return $pk;

			case Info::MOB_ARMOR_EQUIPMENT_PACKET:
				/** @var MobArmorEquipmentPacket $packet */
				return $player->getInventoryUtils()->onMobArmorEquipment($packet);

			case Info::SET_ACTOR_DATA_PACKET:
				/** @var SetActorDataPacket $packet */
				$packets = [];

				if(isset($packet->metadata[Player::DATA_PLAYER_BED_POSITION])){
					$bedXYZ = $packet->metadata[Player::DATA_PLAYER_BED_POSITION][1];
					if($bedXYZ !== null){
						/** @var Vector3 $bedXYZ */

						/*$pk = new UseBedPacket();
						$pk->entityId = $packet->entityRuntimeId;
						$pk->bedX = $bedXYZ->getX();
						$pk->bedY = $bedXYZ->getY();
						$pk->bedZ = $bedXYZ->getZ();

						$packets[] = $pk;*/
					}
				}

				$pk = new EntityMetadataPacket();
				$pk->entityId = $packet->entityRuntimeId;
				var_dump($packet->metadata);
				$pk->metadata = $packet->metadata;
				$packets[] = $pk;

				return $packets;

			case Info::SET_ACTOR_MOTION_PACKET:
				/** @var SetActorMotionPacket $packet */
				$pk = new EntityVelocityPacket();
				$pk->entityId = $packet->entityRuntimeId;
				$pk->velocityX = $packet->motion->x;
				$pk->velocityY = $packet->motion->y;
				$pk->velocityZ = $packet->motion->z;
				return $pk;

			case Info::SET_HEALTH_PACKET:
				/** @var SetHealthPacket $packet */
				$pk = new UpdateHealthPacket();
				$pk->health = $packet->health;//TODO: Default Value
				$pk->food = (int) $player->getFood();//TODO: Default Value
				$pk->foodSaturation = $player->getSaturation();//TODO: Default Value
				return $pk;

			case Info::SET_SPAWN_POSITION_PACKET:
				/** @var SetSpawnPositionPacket $packet */
				$pk = new SpawnPositionPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				return $pk;

			case Info::ANIMATE_PACKET:
				/** @var AnimatePacket $packet */
				switch($packet->action){
					case 1:
						$pk = new STCAnimatePacket();
						$pk->animation = 0;
						$pk->entityId = $packet->entityRuntimeId;
						return $pk;
					case 3: //Leave Bed
						$pk = new STCAnimatePacket();
						$pk->animation = 2;
						$pk->entityId = $packet->entityRuntimeId;
						return $pk;
					default:
						echo "AnimationPacket: ".$packet->action."\n";
					break;
				}
				return null;

			case Info::CONTAINER_OPEN_PACKET:
				/** @var ContainerOpenPacket $packet */
				return $player->getInventoryUtils()->onWindowOpen($packet);

			case Info::CONTAINER_CLOSE_PACKET:
				/** @var ContainerClosePacket $packet */
				return $player->getInventoryUtils()->onWindowCloseFromPEtoPC($packet);

			case Info::INVENTORY_SLOT_PACKET:
				/** @var InventorySlotPacket $packet */
				return $player->getInventoryUtils()->onWindowSetSlot($packet);

			case Info::CONTAINER_SET_DATA_PACKET:
				/** @var ContainerSetDataPacket $packet */
				return $player->getInventoryUtils()->onWindowSetData($packet);

			case Info::CRAFTING_DATA_PACKET:
				/** @var CraftingDataPacket $packet */
				return $player->getRecipeUtils()->onCraftingData($packet);

			case Info::INVENTORY_CONTENT_PACKET:
				/** @var InventoryContentPacket $packet */
				return $player->getInventoryUtils()->onWindowSetContent($packet);

			case Info::BLOCK_ACTOR_DATA_PACKET:
				/** @var BlockActorDataPacket $packet */
				$pk = new BlockEntityDataPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;

				$nbt = new NetworkLittleEndianNBTStream();
				$nbt = $nbt->read($packet->namedtag, true);

				switch($nbt["id"]){
					case Tile::BANNER:
						$pk->actionId = 6;
						$pk->nbtData = $nbt;
					break;
					case Tile::BED:
						$pk->actionId = 11;
						$pk->nbtData = $nbt;
					break;
					case Tile::CHEST:
					case Tile::ENCHANT_TABLE:
					case Tile::ENDER_CHEST:
					case Tile::FURNACE:
						$pk->actionId = 7;
						$pk->nbtData = $nbt;
					break;
					case Tile::FLOWER_POT:
						$pk->actionId = 5;
						/** @var CompoundTag $nbt */
						$pk->nbtData = ConvertUtils::convertBlockEntity(true, $nbt);
					break;
					case Tile::ITEM_FRAME:
						if(($entity = ItemFrameBlockEntity::getItemFrame($player->getLevel(), $packet->x, $packet->y, $packet->z)) !== null){
							$entity->spawnTo($player);//Update Item Frame
						}
						return null;
					case Tile::SIGN:
						$pk->actionId = 9;
						/** @var CompoundTag $nbt */
						$pk->nbtData = ConvertUtils::convertBlockEntity(true, $nbt);
					break;
					case Tile::SKULL:
						$pk->actionId = 4;
						$pk->nbtData = $nbt;
					break;
					default:
						echo "BlockEntityDataPacket: ".$nbt["id"]."\n";
						return null;
				}

				return $pk;

			case Info::SET_DIFFICULTY_PACKET:
				/** @var SetDifficultyPacket $packet */
				$pk = new ServerDifficultyPacket();
				$pk->difficulty = $packet->difficulty;
				return $pk;

			case Info::SET_PLAYER_GAME_TYPE_PACKET:
				/** @var SetPlayerGameTypePacket $packet */
				$packets = [];

				$pk = new PlayerAbilitiesPacket();
				$pk->flyingSpeed = 0.05;
				$pk->viewModifierField = 0.1;
				$pk->canFly = ($player->getGamemode() & 0x01) > 0;
				$pk->damageDisabled = ($player->getGamemode() & 0x01) > 0;
				$pk->isFlying = false;
				$pk->isCreative = ($player->getGamemode() & 0x01) > 0;
				$packets[] = $pk;

				$pk = new ChangeGameStatePacket();
				$pk->reason = 3;
				$pk->value = $player->getGamemode();
				$packets[] = $pk;

				return $packets;

			case Info::LEVEL_CHUNK_PACKET:
				/** @var LevelChunkPacket $packet */
				$blockEntities = [];
				foreach($player->getLevel()->getChunkTiles($packet->getChunkX(), $packet->getChunkZ()) as $tile){
					if($tile instanceof Spawnable){
						$blockEntities[] = clone $tile->getSpawnCompound();
					}
				}

				$chunk = new DesktopChunk($player, $packet->getChunkX(), $packet->getChunkZ());

				$packets = [];
				$pk = new UpdateLightPacket();
				$pk->chunkX = $packet->getChunkX();
				$pk->chunkZ = $packet->getChunkZ();
				$pk->skyLightMask = $chunk->getSkyLightBitMask();
				$pk->blockLightMask = $chunk->getBlockLightBitMask();
				$pk->emptySkyLightMask = ~$chunk->getSkyLightBitMask();
				$pk->emptyBlockLightMask = ~$chunk->getBlockLightBitMask();
				$pk->skyLight = $chunk->getSkyLight();
				$pk->blockLight = $chunk->getBlockLight();
				$packets[] = $pk;

				$pk = new ChunkDataPacket();
				$pk->chunkX = $packet->getChunkX();
				$pk->chunkZ = $packet->getChunkZ();
				$pk->isFullChunk = $chunk->isFullChunk();
				$pk->primaryBitMask = $chunk->getChunkBitMask();
				$pk->heightMaps = $chunk->getHeightMaps();
				$pk->biomes = $chunk->getBiomes();
				$pk->data = $chunk->getChunkData();
				$pk->blockEntities = $blockEntities;
				$packets[] = $pk;

				return $packets;

			case Info::PLAYER_LIST_PACKET:
				/** @var PlayerListPacket $packet */
				$pk = new PlayerInfoPacket();

				switch($packet->type){
					case 0://Add
						$pk->actionId = PlayerInfoPacket::TYPE_ADD;

						$loggedInPlayers = $player->getServer()->getLoggedInPlayers();
						foreach($packet->entries as $entry){
							$playerData = null;
							$gameMode = 0;
							$displayName = $entry->username;
							if(isset($loggedInPlayers[$entry->uuid->toBinary()])){
								$playerData = $loggedInPlayers[$entry->uuid->toBinary()];
								$gameMode = $playerData->getGamemode();
								$displayName = $playerData->getNameTag();
							}

							if($playerData instanceof DesktopPlayer){
								$properties = $playerData->bigBrother_getProperties();
							}else{
								//TODO: Skin Problem
								$value = [//Dummy Data
									"timestamp" => 0,
									"profileId" => str_replace("-", "", $entry->uuid->toString()),
									"profileName" => TextFormat::clean($entry->username),
									"textures" => [
										"SKIN" => [
											//TODO
										]
									]
								];

								$properties = [
									[
										"name" => "textures",
										"value" => base64_encode(json_encode($value)),
									]
								];
							}

							$pk->players[] = [
								$entry->uuid->toBinary(),
								substr(TextFormat::clean($displayName), 0, 16),
								$properties,
								$gameMode,
								0,
								true,
								BigBrother::toJSON($entry->username)
							];
						}
					break;
					case 1://Remove
						$pk->actionId = PlayerInfoPacket::TYPE_REMOVE;

						foreach($packet->entries as $entry){
							$pk->players[] = [
								$entry->uuid->toBinary(),
							];
						}
					break;
				}

				return $pk;

			case Info::CLIENTBOUND_MAP_ITEM_DATA_PACKET:
				/** @var ClientboundMapItemDataPacket $packet */
				$pk = new MapPacket();

				$pk->mapId = $packet->mapId;
				$pk->scale = $packet->scale;
				$pk->columns = $packet->width;
				$pk->rows = $packet->height;

				// TODO implement tracked entities handling and general map behaviour

				$pk->data = ColorUtils::convertColorsToPC($packet->colors, $packet->width, $packet->height);

				return $pk;

			case Info:: CHUNK_RADIUS_UPDATED_PACKET:
				/** @var ChunkRadiusUpdatedPacket $packet */
				$pk = new UpdateViewDistancePacket();
				$pk->viewDistance = $packet->radius * 2;
				return $pk;

			case Info::BOSS_EVENT_PACKET:
				/** @var BossEventPacket $packet */
				$pk = new BossBarPacket();
				$uuid = $player->bigBrother_getBossBarData("uuid");

				switch($packet->eventType){
					case BossEventPacket::TYPE_REGISTER_PLAYER:
					case BossEventPacket::TYPE_UNREGISTER_PLAYER:
					case BossEventPacket::TYPE_UNKNOWN_6:
					break;
					case BossEventPacket::TYPE_SHOW:
						if($uuid !== ""){
							return null;
						}
						$pk->uuid = UUID::fromRandom()->toBinary();
						$pk->actionId = BossBarPacket::TYPE_ADD;
						if(isset($packet->title) and is_string($packet->title) and strlen($packet->title) > 0){
							$title = $packet->title;
						}else{
							$title = $player->bigBrother_getBossBarData("nameTag")[1];
						}
						$pk->title = BigBrother::toJSON(str_replace(["\r\n", "\r", "\n"], "", $title));
						$health = 1.0;
						if($packet->healthPercent < 100){ //healthPercent is a value between 1 and 100
							$health = $packet->healthPercent / 100;
						}elseif($packet->healthPercent <= 0){
							$health = 0.0;
						}
						$pk->health = $health;

						$player->bigBrother_setBossBarData("entityRuntimeId", $packet->bossEid);
						$player->bigBrother_setBossBarData("uuid", $pk->uuid);

						return $pk;
					case BossEventPacket::TYPE_HIDE:
						if($uuid === ""){
							return null;
						}
						$pk->uuid = $uuid;
						$pk->actionId = BossBarPacket::TYPE_REMOVE;

						$player->bigBrother_setBossBarData("entityRuntimeId", -1);
						$player->bigBrother_setBossBarData("uuid", "");

						return $pk;
					case BossEventPacket::TYPE_TEXTURE:
						if($uuid === ""){
							return null;
						}
						$pk->uuid = $uuid;
						$pk->actionId = BossBarPacket::TYPE_UPDATE_COLOR;
						$pk->color = $packet->color;

						return $pk;
					case BossEventPacket::TYPE_HEALTH_PERCENT:
						if($uuid === ""){
							return null;
						}
						$pk->uuid = $uuid;
						$pk->actionId = BossBarPacket::TYPE_UPDATE_HEALTH;
						$health = 1.0;
						if($packet->healthPercent < 100){ //healthPercent is a value between 1 and 100
							$health = $packet->healthPercent / 100;
						}elseif($packet->healthPercent <= 0){
							$health = 0.0;
						}
						$pk->health = $health;

						return $pk;
					case BossEventPacket::TYPE_TITLE:
						if($uuid === ""){
							return null;
						}
						$pk->uuid = $uuid;
						$pk->actionId = BossBarPacket::TYPE_UPDATE_TITLE;
						$pk->title = BigBrother::toJSON(str_replace(["\r\n", "\r", "\n"], "", $packet->title));

						return $pk;
					default:
						echo "BossEventPacket: ".$packet->eventType."\n";
					break;
				}
				return null;

			case BatchPacket::NETWORK_ID:
				$packets = [];

				/** @var BatchPacket $packet */
				$packet->decode();

				$stream = new NetworkBinaryStream($packet->payload);
				while(!$stream->feof()){
					$buf = $stream->getString();

					if(($pk = PacketPool::getPacket($buf)) !== null){
						if(!$pk->canBeBatched()){
							throw new UnexpectedValueException("Received invalid " . get_class($pk) . " inside BatchPacket");
						}

						$pk->decode();

						if(($desktop = $this->serverToInterface($player, $pk)) !== null){
							if(is_array($desktop)){
								$packets = array_merge($packets, $desktop);
							}else{
								$packets[] = $desktop;
							}
						}
					}
				}

				return $packets;

			case Info::RESOURCE_PACKS_INFO_PACKET:
			case Info::RESPAWN_PACKET:
			case Info::ADVENTURE_SETTINGS_PACKET:
			case Info::AVAILABLE_COMMANDS_PACKET:
			case Info::AVAILABLE_ACTOR_IDENTIFIERS_PACKET:
			case Info::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET:
			case Info::BIOME_DEFINITION_LIST_PACKET:
			case Info::CREATIVE_CONTENT_PACKET:
				return null;

			default:
				if(DEBUG > 4){
					echo "[Send][Translator] 0x".bin2hex(chr($packet->pid()))." Not implemented\n";
				}
				return null;
		}
	}
}
