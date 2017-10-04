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

abstract class InboundPacket extends Packet{

	//Login
	const LOGIN_START_PACKET                    = 0x0000;
	const ENCRYPTION_RESPONSE_PACKET            = 0x0001;

	//Play
	const TELEPORT_CONFIRM_PACKET               = 0x0100;
	const TAB_COMPLETE_PACKET                   = 0x0101;
	const CHAT_PACKET                           = 0x0102;
	const CLIENT_STATUS_PACKET                  = 0x0103;
	const CLIENT_SETTINGS_PACKET                = 0x0104;
	const CONFIRM_TRANSACTION_PACKET            = 0x0105;
	const ENCHANT_ITEM_PACKET                   = 0x0106;
	const CLICK_WINDOW_PACKET                   = 0x0107;
	const CLOSE_WINDOW_PACKET                   = 0x0108;
	const PLUGIN_MESSAGE_PACKET                 = 0x0109;
	const USE_ENTITY_PACKET                     = 0x010a;
	const KEEP_ALIVE_PACKET                     = 0x010b;
	const PLAYER_PACKET                         = 0x010c;
	const PLAYER_POSITION_PACKET                = 0x010d;
	const PLAYER_POSITION_AND_LOOK_PACKET       = 0x010e;
	const PLAYER_LOOK_PACKET                    = 0x010f;
	//TODO VEHICLE_MOVE_PACKET                  = 0x0110;
	//TODO STEER_BOAT_PACKET                    = 0x0111;
	//TODO CRAFT_RECIPE_REQUEST_PACKET          = 0x0112;
	const PLAYER_ABILITIES_PACKET               = 0x0113;
	const PLAYER_DIGGING_PACKET                 = 0x0114;
	const ENTITY_ACTION_PACKET                  = 0x0115;
	//TODO STEER_VEHICLE_PACKET                 = 0x0116;
	//TODO CRAFTING_BOOK_DATA_PACKET            = 0x0117;
	//TODO RESOURCE_PACK_STATUS_PACKET          = 0x0118;
	const ADVANCEMENT_TAB_PACKET                = 0x0119;
	const HELD_ITEM_CHANGE_PACKET               = 0x011a;
	const CREATIVE_INVENTORY_ACTION_PACKET      = 0x011b;
	const UPDATE_SIGN_PACKET                    = 0x011c;
	const ANIMATE_PACKET                        = 0x011d;
	//TODO SPECTATE_PACKET                      = 0x011e;
	const PLAYER_BLOCK_PLACEMENT_PACKET         = 0x011f;
	const USE_ITEM_PACKET                       = 0x0120;

	//Status

	/**
	 * @deprecated
	 */
	protected final function encode() : void{
		throw new \ErrorException(get_class($this) . " is subclass of InboundPacket: don't call encode() method");
	}
}
