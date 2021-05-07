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

use ErrorException;

abstract class InboundPacket extends Packet{
	//Play
	const TELEPORT_CONFIRM_PACKET = 0x00;
	//TODO QUERY_BLOCK_NBT_PACKET = 0x01;
	//TODO SET_DIFFICULTY_PACKET = 0x02;
	const CHAT_MESSAGE_PACKET = 0x03;
	const CLIENT_STATUS_PACKET = 0x04;
	const CLIENT_SETTINGS_PACKET = 0x05;
	const TAB_COMPLETE_PACKET = 0x06;
	const WINDOW_CONFIRMATION_PACKET = 0x07;
	//TODO CLICK_WINDOW_BUTTON_PACKET = 0x08;
	const CLICK_WINDOW_PACKET = 0x09;
	const CLOSE_WINDOW_PACKET = 0x0a;
	const PLUGIN_MESSAGE_PACKET = 0x0b;
	//TODO EDIT_BOOK_PACKET = 0x0c;
	//TODO QUERY_ENTITY_NBT_PACKET = 0x0d;
	const INTERACT_ENTITY_PACKET = 0x0e;
	//TODO GENERATE_STRUCTURE_PACKET = 0x0f;
	const KEEP_ALIVE_PACKET = 0x10;
	//TODO LOCK_DIFFICULTY_PACKET = 0x11;
	const PLAYER_POSITION_PACKET = 0x12;
	const PLAYER_POSITION_AND_ROTATION_PACKET = 0x13;
	const PLAYER_ROTATION_PACKET = 0x14;
	const PLAYER_MOVEMENT_PACKET = 0x15;
	//TODO STEER_BOAT_PACKET = 0x17;
	//TODO PICK_ITEM_PACKET = 0x18;
	const CRAFT_RECIPE_REQUEST_PACKET = 0x19;
	const PLAYER_ABILITIES_PACKET = 0x1a;
	const PLAYER_DIGGING_PACKET = 0x1b;
	const ENTITY_ACTION_PACKET = 0x1c;
	//TODO STEER_VEHICLE_PACKET = 0x1d;
	//TODO SET_DISPLAYED_RECIPE_PACKET = 0x1e;
	//TODO SET_RECIPE_BOOK_STATE_PACKET = 0x1f;
	//TODO NAME_ITEM_PACKET = 0x20;
	//TODO RESOURCE_PACK_STATUS_PACKET = 0x21;
	const ADVANCEMENT_TAB_PACKET = 0x22;
	//TODO SELECT_TRADE_PACKET = 0x23;
	//TODO SET_BEACON_EFFECT_PACKET = 0x24;
	const HELD_ITEM_CHANGE_PACKET = 0x25;
	//TODO UPDATE COMMAND_BLOCK_PACKET = 0x26;
	//TODO UPDATE COMMAND_BLOCK_MINECRAFT_PACKET = 0x27;
	//TODO UPDATE_JIGSAW_BLOCK_PACKET = 0x28;
	const CREATIVE_INVENTORY_ACTION_PACKET = 0x28;
	//29...?
	//TODO UPDATE_STRUCTURE_BLOCK_PACKET = 0x2a;
	const UPDATE_SIGN_PACKET = 0x2b;
	const ANIMATION_PACKET = 0x2c;
	//TODO SPECTATE_PACKET = 0x2d;
	const PLAYER_BLOCK_PLACEMENT_PACKET = 0x2e;
	const USE_ITEM_PACKET = 0x2f;

	//Status

	//Login
	const LOGIN_START_PACKET = 0x00;
	const ENCRYPTION_RESPONSE_PACKET = 0x01;

	/**
	 * @deprecated
	 * @throws ErrorException
	 */
	protected final function encode() : void{
		throw new ErrorException(get_class($this) . " is subclass of InboundPacket: don't call encode() method");
	}

}
