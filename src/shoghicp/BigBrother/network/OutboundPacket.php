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

abstract class OutboundPacket extends Packet{

	//Login
	const LOGIN_DISCONNECT_PACKET               = 0x0000;
	const ENCRYPTION_REQUEST_PACKET             = 0x0001;
	const LOGIN_SUCCESS_PACKET                  = 0x0002;

	//Play
	const SPAWN_OBJECT_PACKET                   = 0x0100;
	//TODO SPAWN_EXPERIENCE_ORB_PACKET          = 0x0101;
	//TODO SPAWN_GLOBAL_ENTITY_PACKET           = 0x0102;
	const SPAWN_MOB_PACKET                      = 0x0103;
	//TODO SPAWN_PAINTING_PACKET                = 0x0104;
	const SPAWN_PLAYER_PACKET                   = 0x0105;
	const ANIMATE_PACKET                        = 0x0106;
	const STATISTICS_PACKET                     = 0x0107;
	//TODO BLOCK_BREAK_ANIMATION_PACKET         = 0x0108;
	const UPDATE_BLOCK_ENTITY_PACKET            = 0x0109;
	const BLOCK_ACTION_PACKET                   = 0x010a;
	const BLOCK_CHANGE_PACKET                   = 0x010b;
	const BOSS_BAR_PACKET                       = 0x010c;
	const SERVER_DIFFICULTY_PACKET              = 0x010d;
	const TAB_COMPLETE_PACKET                   = 0x010e;
	const CHAT_PACKET                           = 0x010f;
	const CONFIRM_TRANSACTION_PACKET            = 0x0111;
	const CLOSE_WINDOW_PACKET                   = 0x0112;
	const OPEN_WINDOW_PACKET                    = 0x0113;
	const WINDOW_ITEMS_PACKET                   = 0x0114;
	const WINDOW_PROPERTY_PACKET                = 0x0115;
	const SET_SLOT_PACKET                       = 0x0116;
	//TODO SET_COOLDOWN_PACKET                  = 0x0117;
	const PLUGIN_MESSAGE_PACKET                 = 0x0118;
	const NAMED_SOUND_EFFECT_PACKET             = 0x0119;
	const PLAY_DISCONNECT_PACKET                = 0x011a;
	const ENTITY_STATUS_PACKET                  = 0x011b;
	const EXPLOSION_PACKET                      = 0x011c;
	const UNLOAD_CHUNK_PACKET                   = 0x011d;
	const CHANGE_GAME_STATE_PACKET              = 0x011e;
	const KEEP_ALIVE_PACKET                     = 0x011f;
	const CHUNK_DATA_PACKET                     = 0x0120;
	const EFFECT_PACKET                         = 0x0121;
	const PARTICLE_PACKET                       = 0x0122;
	const JOIN_GAME_PACKET                      = 0x0123;
	//TODO MAP_PACKET                           = 0x0124;
	const ENTITY_PACKET                         = 0x0125;
	//TODO ENTITY_RELATIVE_MOVE_PACKET          = 0x0126;
	//TODO ENTITY_LOOK_AND_RELATIVE_MOVE_PACKET = 0x0127;
	//TODO ENTITY_LOOK_PACKET                   = 0x0128;
	//TODO VEHICLE_MOVE_PACKET                  = 0x0129;
	const OPEN_SIGN_EDITOR_PACKET               = 0x012a;
	//TODO CRAFT_RECIPE_RESPONSE_PACKET         = 0x012b;
	const PLAYER_ABILITIES_PACKET               = 0x012c;
	//TODO COMBAT_EVENT_PACKET                  = 0x012d;
	const PLAYER_LIST_PACKET                    = 0x012e;
	const PLAYER_POSITION_AND_LOOK_PACKET       = 0x012f;
	const USE_BED_PACKET                        = 0x0130;
	//TODO UNLOCK_RECIPIES_PACKET               = 0x0131;
	const DESTROY_ENTITIES_PACKET               = 0x0132;
	const REMOVE_ENTITY_EFFECT_PACKET           = 0x0133;
	//TODO RESOURCE_PACK_SEND_PACKET            = 0x0134;
	const RESPAWN_PACKET                        = 0x0135;
	const ENTITY_HEAD_LOOK_PACKET               = 0x0136;
	const SELECT_ADVANCEMENT_TAB_PACKET         = 0x0137;
	//TODO WORLD_BORDER_PACKET                  = 0x0138;
	//TODO CAMERA_PACKET                        = 0x0139;
	const HELD_ITEM_CHANGE_PACKET               = 0x013a;
	//TODO DISPLAY_SCOREBOARD_PACKET            = 0x013b;
	const ENTITY_METADATA_PACKET                = 0x013c;
	//TODO ATTACH_ENTITY_PACKET                 = 0x013d;
	const ENTITY_VELOCITY_PACKET                = 0x013e;
	const ENTITY_EQUIPMENT_PACKET               = 0x013f;
	const SET_EXPERIENCE_PACKET                 = 0x0140;
	const UPDATE_HEALTH_PACKET                  = 0x0141;
	//TODO SCOREBOARD_OBJECTIVE_PACKET          = 0x0142;
	//TODO SET_PASSENGERS_PACKET                = 0x0143;
	//TODO TEAMS_PACKET                         = 0x0144;
	//TODO UPDATE_SCORE_PACKET                  = 0x0145;
	const SPAWN_POSITION_PACKET                 = 0x0146;
	const TIME_UPDATE_PACKET                    = 0x0147;
	const TITLE_PACKET                          = 0x0148;
	const SOUND_EFFECT_PACKET                   = 0x0149;
	//TODO PLAYER_LIST_HEADER_AND_FOOTER_PACKET = 0x014a;
	const COLLECT_ITEM_PACKET                   = 0x014b;
	const ENTITY_TELEPORT_PACKET                = 0x014c;
	const ADVANCEMENTS_PACKET                   = 0x014d;
	const ENTITY_PROPERTIES_PACKET              = 0x014e;
	const ENTITY_EFFECT_PACKET                  = 0x014f;

	//Status

	/**
	 * @deprecated
	 */
	protected final function decode() : void{
		throw new \ErrorException(get_class($this) . " is subclass of OutboundPacket: don't call decode() method");
	}
}
