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

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\SourceInterface;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\MainLogger;
use shoghicp\BigBrother\BigBrother;
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\utils\Binary;

class ProtocolInterface implements SourceInterface{

	/** @var BigBrother */
	protected $plugin;
	/** @var Server */
	protected $server;
	/** @var Translator */
	protected $translator;
	/** @var ServerThread */
	protected $thread;

	/** @var \SplObjectStorage<DesktopPlayer> */
	protected $sessions;

	/** @var DesktopPlayer[] */
	protected $sessionsPlayers = [];

	/** @var DesktopPlayer[] */
	protected $identifiers = [];

	/** @var int */
	protected $identifier = 0;

	/** @var int */
	private $threshold;

	/**
	 * @param BigBrother $plugin
	 * @param Server     $server
	 * @param Translator $translator
	 * @param int        $threshold
	 */
	public function __construct(BigBrother $plugin, Server $server, Translator $translator, int $threshold){
		$this->plugin = $plugin;
		$this->server = $server;
		$this->translator = $translator;
		$this->threshold = $threshold;
		$this->thread = new ServerThread($server->getLogger(), $server->getLoader(), $plugin->getPort(), $plugin->getIp(), $plugin->getMotd(), $plugin->getDataFolder()."server-icon.png", false);
		$this->sessions = new \SplObjectStorage();
	}

	/**
	 * @override
	 */
	public function start(){
		$this->thread->start();
	}

	/**
	 * @override
	 */
	public function emergencyShutdown(){
		$this->thread->pushMainToThreadPacket(chr(ServerManager::PACKET_EMERGENCY_SHUTDOWN));
	}

	/**
	 * @override
	 */
	public function shutdown(){
		$this->thread->pushMainToThreadPacket(chr(ServerManager::PACKET_SHUTDOWN));
	}

	/**
	 * @param string $name
	 * @override
	 */
	public function setName(string $name){
		$info = $this->plugin->getServer()->getQueryInformation();
		$value = [
			"MaxPlayers" => $info->getMaxPlayerCount(),
			"OnlinePlayers" => $info->getPlayerCount(),
		];
		$buffer = chr(ServerManager::PACKET_SET_OPTION).chr(strlen("name"))."name".json_encode($value);
		$this->thread->pushMainToThreadPacket($buffer);
	}

	/**
	 * @param int $identifier
	 */
	public function closeSession(int $identifier){
		if(isset($this->sessionsPlayers[$identifier])){
			$player = $this->sessionsPlayers[$identifier];
			unset($this->sessionsPlayers[$identifier]);
			$player->close($player->getLeaveMessage(), "Connection closed");
		}
	}

	/**
	 * @param Player $player
	 * @param string $reason
	 * @override
	 */
	public function close(Player $player, string $reason = "unknown reason"){
		if(isset($this->sessions[$player])){
			$identifier = $this->sessions[$player];
			$this->sessions->detach($player);
			unset($this->identifiers[$identifier]);
			$this->thread->pushMainToThreadPacket(chr(ServerManager::PACKET_CLOSE_SESSION) . Binary::writeInt($identifier));
		}else{
			return;
		}
	}

	/**
	 * @param int    $target
	 * @param Packet $packet
	 */
	protected function sendPacket(int $target, Packet $packet){
		if(\pocketmine\DEBUG > 4){
			if($packet->canonical() === OutboundPacket::KEEP_ALIVE_PACKET){
				$this->plugin->getLogger()->debug(sprintf("[Send][Interface] %s", $packet));
			}
		}

		$data = chr(ServerManager::PACKET_SEND_PACKET) . Binary::writeInt($target) . $packet->write();
		$this->thread->pushMainToThreadPacket($data);
	}

	/**
	 * @param DesktopPlayer $player
	 */
	public function setCompression(DesktopPlayer $player){
		if(isset($this->sessions[$player])){
			$target = $this->sessions[$player];
			$data = chr(ServerManager::PACKET_SET_COMPRESSION) . Binary::writeInt($target) . Binary::writeInt($this->threshold);
			$this->thread->pushMainToThreadPacket($data);
		}
	}

	/**
	 * @param DesktopPlayer $player
	 * @param string        $secret
	 */
	public function enableEncryption(DesktopPlayer $player, string $secret){
		if(isset($this->sessions[$player])){
			$target = $this->sessions[$player];
			$data = chr(ServerManager::PACKET_ENABLE_ENCRYPTION) . Binary::writeInt($target) . $secret;
			$this->thread->pushMainToThreadPacket($data);
		}
	}

	/**
	 * @param DesktopPlayer $player
	 * @param Packet        $packet
	 */
	public function putRawPacket(DesktopPlayer $player, Packet $packet){
		if(isset($this->sessions[$player])){
			$target = $this->sessions[$player];
			$this->sendPacket($target, $packet);
		}
	}

	/**
	 * @param Player     $player
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 * @param bool       $immediate
	 *
	 * @return int|null identifier if $needAck === false else null
	 * @override
	 */
	public function putPacket(Player $player, DataPacket $packet, bool $needACK = false, bool $immediate = true){
		$id = 0;
		if($needACK){
			$id = $this->identifier++;
			$this->identifiers[$id] = $player;
		}
		assert($player instanceof DesktopPlayer);
		$packets = $this->translator->serverToInterface($player, $packet);
		if($packets !== null and $this->sessions->contains($player)){
			$target = $this->sessions[$player];
			if(is_array($packets)){
				foreach($packets as $packet){
					$this->sendPacket($target, $packet);
				}
			}else{
				$this->sendPacket($target, $packets);
			}
		}

		return $id;
	}

	/**
	 * @param DesktopPlayer $player
	 * @param Packet        $packet
	 */
	protected function receivePacket(DesktopPlayer $player, Packet $packet){
		$packets = $this->translator->interfaceToServer($player, $packet);
		if($packets !== null){
			if(is_array($packets)){
				foreach($packets as $packet){
					$player->handleDataPacket($packet);
				}
			}else{
				$player->handleDataPacket($packets);
			}
		}
	}

	/**
	 * @param DesktopPlayer $player
	 * @param string        $payload
	 */
	protected function handlePacket(DesktopPlayer $player, string $payload){
		$pid = ord($payload{0});
		$offset = 1;

		$status = $player->bigBrother_getStatus();
		$pk = Packet::newInboundPacket($pid, $status, $payload, $offset);

		if(\pocketmine\DEBUG > 3){
			if($pk === null){
				$this->plugin->getLogger()->debug(sprintf("[Recv][Interface] status=0x%02x; pid=0x%02x Not implemented", $status, $pid)); //Debug
			}
		}

		if(\pocketmine\DEBUG > 4){
			if($pk !== null and $pk->canonical() === InboundPacket::KEEP_ALIVE_PACKET){
				$this->plugin->getLogger()->debug(sprintf("[Recv][Interface] %s", $pk));
			}
		}

		if($status === 1){
			$this->receivePacket($player, $pk);
		}elseif($status === 0){
			switch($pk->canonical()){
				case InboundPacket::LOGIN_START_PACKET:
					$player->bigBrother_handleAuthentication($this->plugin, $pk->name, $this->plugin->isOnlineMode());
				break;
				case InboundPacket::ENCRYPTION_RESPONSE_PACKET:
					if($this->plugin->isOnlineMode()){
						$player->bigBrother_processAuthentication($this->plugin, $pk);
						break;
					}
				default:
					$player->close($player->getLeaveMessage(), sprintf("Unexpected packet 0x%02x", $pid));
				break;
			}
		}
	}

	/**
	 * @return bool
	 * @override
	 */
	public function process() : bool{
		if(count($this->identifiers) > 0){
			foreach($this->identifiers as $id => $player){
				$player->handleACK($id);
			}
		}

		while(is_string($buffer = $this->thread->readThreadToMainPacket())){
			$offset = 1;
			$pid = ord($buffer{0});

			if($pid === ServerManager::PACKET_SEND_PACKET){
				$id = Binary::readInt(substr($buffer, $offset, 4));
				$offset += 4;
				if(isset($this->sessionsPlayers[$id])){
					$payload = substr($buffer, $offset);
					try{
						$this->handlePacket($this->sessionsPlayers[$id], $payload);
					}catch(\Exception $e){
						if(\pocketmine\DEBUG > 1){
							$logger = $this->server->getLogger();
							if($logger instanceof MainLogger){
								$logger->debug("DesktopPacket 0x" . bin2hex($payload));
								$logger->logException($e);
							}
						}
					}
				}
			}elseif($pid === ServerManager::PACKET_OPEN_SESSION){
				$id = Binary::readInt(substr($buffer, $offset, 4));
				$offset += 4;
				if(isset($this->sessionsPlayers[$id])){
					continue;
				}
				$len = ord($buffer{$offset++});
				$address = substr($buffer, $offset, $len);
				$offset += $len;
				$port = Binary::readShort(substr($buffer, $offset, 2));

				$identifier = "$id:$address:$port";

				$player = new DesktopPlayer($this, $identifier, $address, $port, $this->plugin);
				$this->sessions->attach($player, $id);
				$this->sessionsPlayers[$id] = $player;
				$this->plugin->getServer()->addPlayer($identifier, $player);
			}elseif($pid === ServerManager::PACKET_CLOSE_SESSION){
				$id = Binary::readInt(substr($buffer, $offset, 4));
				$offset += 4;
				$flag = Binary::readInt(substr($buffer, $offset, 4));

				if(isset($this->sessionsPlayers[$id])){
					if($flag === 0){
						$this->close($this->sessionsPlayers[$id]);
					}else{
						$this->closeSession($id);
					}
				}
			}

		}

		return true;
	}
}
