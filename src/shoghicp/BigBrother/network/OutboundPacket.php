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

abstract class OutboundPacket extends Packet{

	//Play
	const SPAWN_ENTITY_PACKET = 0x00;
	const SPAWN_EXPERIENCE_ORB_PACKET = 0x01;
	const SPAWN_LIVING_ENTITY_PACKET = 0x02;
	const SPAWN_PAINTING_PACKET = 0x03;
	const SPAWN_PLAYER_PACKET = 0x04;
	const ENTITY_ANIMATION_PACKET = 0x05;
	const STATISTICS_PACKET = 0x06;
	//TODO ACKNOWLEDGE_PLAYER_DIGGING_PACKET = 0x07;
	const BLOCK_BREAK_ANIMATION_PACKET = 0x08;
	const BLOCK_ENTITY_DATA_PACKET = 0x09;
	const BLOCK_ACTION_PACKET = 0x0a;
	const BLOCK_CHANGE_PACKET = 0x0b;
	const BOSS_BAR_PACKET = 0x0c;
	const SERVER_DIFFICULTY_PACKET = 0x0d;
	const CHAT_MESSAGE_PACKET = 0x0e;
	const TAB_COMPLETE_PACKET = 0x0f;
	//TODO DECLARE_COMMANDS_PACKET = 0x10;
	const WINDOW_CONFIRMATION_PACKET = 0x11;
	const CLOSE_WINDOW_PACKET = 0x12;
	const WINDOW_ITEMS_PACKET = 0x13;
	const WINDOW_PROPERTY_PACKET = 0x14;
	const SET_SLOT_PACKET = 0x15;
	//TODO SET_COOLDOWN_PACKET = 0x16;
	const PLUGIN_MESSAGE_PACKET = 0x17;
	const NAMED_SOUND_EFFECT_PACKET = 0x18;
	const PLAY_DISCONNECT_PACKET = 0x19;
	const ENTITY_STATUS_PACKET = 0x1a;
	const EXPLOSION_PACKET = 0x1b;
	const UNLOAD_CHUNK_PACKET = 0x1c;
	const CHANGE_GAME_STATE_PACKET = 0x1d;
	//TODO OPEN_HORSE_WINDOW_PACKET = 0x1e;
	const KEEP_ALIVE_PACKET = 0x1f;
	const CHUNK_DATA_PACKET = 0x20;
	const EFFECT_PACKET = 0x21;
	const PARTICLE_PACKET = 0x22;
	const UPDATE_LIGHT_PACKET = 0x23;
	const JOIN_GAME_PACKET = 0x24;
	const MAP_PACKET = 0x25;
	//TODO TRADE_LIST_PACKET = 0x26;
	//TODO ENTITY_POSITION_PACKET = 0x27;
	//TODO ENTITY_POSITION_AND_ROTATION_PACKET = 0x28;
	const ENTITY_ROTATION_PACKET = 0x29;
	const ENTITY_MOVEMENT_PACKET = 0x2a;
	//const VEHICLE_MOVE_PACKET = 0x2b;//TODO VEHICLE_MOVE_PACKET = 0x2b;
	//TODO OPEN_BOOK_PACKET = 0x2c;
	const OPEN_WINDOW_PACKET = 0x2d;
	const OPEN_SIGN_EDITOR_PACKET = 0x2e;
	const CRAFT_RECIPE_RESPONSE_PACKET = 0x2f;
	const PLAYER_ABILITIES_PACKET = 0x30;
	//TODO COMBAT_EVENT_PACKET = 0x31;
	const PLAYER_INFO_PACKET = 0x32;
	//TODO FACE_PLAYER_PACKET = 0x33;
	const PLAYER_POSITION_AND_LOOK_PACKET = 0x34;
	const UNLOCK_RECIPES_PACKET = 0x35;
	const DESTROY_ENTITIES_PACKET = 0x36;
	const REMOVE_ENTITY_EFFECT_PACKET = 0x37;
	//TODO RESOURCE_PACK_SEND_PACKET = 0x38;
	const RESPAWN_PACKET = 0x39;
	const ENTITY_HEAD_LOOK_PACKET = 0x3a;
	//TODO MULTI_BLOCK_CHANGE_PACKET = 0x3b:
	const SELECT_ADVANCEMENT_TAB_PACKET = 0x3c;
	//TODO WORLD_BORDER_PACKET = 0x3d;
	//TODO CAMERA_PACKET = 0x3e;
	const HELD_ITEM_CHANGE_PACKET = 0x3f;
	const UPDATE_VIEW_POSITION_PACKET = 0x40;
	const UPDATE_VIEW_DISTANCE_PACKET = 0x41;
	const SPAWN_POSITION_PACKET = 0x42;
	//TODO DISPLAY_SCOREBOARD_PACKET = 0x43;
	const ENTITY_METADATA_PACKET = 0x44;
	//TODO ATTACH_ENTITY_PACKET = 0x45;
	const ENTITY_VELOCITY_PACKET = 0x46;
	const ENTITY_EQUIPMENT_PACKET = 0x47;
	const SET_EXPERIENCE_PACKET = 0x48;
	const UPDATE_HEALTH_PACKET = 0x49;
	//TODO SCOREBOARD_OBJECTIVE_PACKET = 0x4a;
	//TODO SET_PASSENGERS_PACKET = 0x4b;
	//TODO TEAMS_PACKET = 0x4c;
	//TODO UPDATE_SCORE_PACKET = 0x4d;
	const TIME_UPDATE_PACKET = 0x4e;
	const TITLE_PACKET = 0x4f;
	//TODO ENTITY_SOUND_EFFECT_PACKET = 0x50;
	const SOUND_EFFECT_PACKET = 0x51;
	//TODO STOP_SOUND_PACKET = 0x52;
	//TODO PLAYER_LIST_HEADER_AND_FOOTER_PACKET = 0x53;
	//TODO NBT_QUERY_RESPONSE_PACKET = 0x54;
	const COLLECT_ITEM_PACKET = 0x55;
	const ENTITY_TELEPORT_PACKET = 0x56;
	const ADVANCEMENTS_PACKET = 0x57;
	const ENTITY_PROPERTIES_PACKET = 0x58;
	const ENTITY_EFFECT_PACKET = 0x59;
	//TODO DECLARE_RECIPES_PACKET = 0x5a;
	//TODO TAGS_PACKET = 0x5b;

	//Status

	//Login
	const LOGIN_DISCONNECT_PACKET = 0x00;
	const ENCRYPTION_REQUEST_PACKET = 0x01;
	const LOGIN_SUCCESS_PACKET = 0x02;

	/**
	 * @deprecated
	 * @throws ErrorException
	 */
	protected final function decode() : void{
		throw new ErrorException(get_class($this) . " is subclass of OutboundPacket: don't call decode() method");
	}

}
