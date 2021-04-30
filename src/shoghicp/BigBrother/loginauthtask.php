<?php
declare(strict_types=1);

namespace shoghicp\BigBrother;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;
use pocketmine\level\Level;
use pocketmine\utils\Internet;

    class loginauthtask extends AsyncTask{

				/** @var string */
				private $username;
				/** @var string */
				private $hash;

				/**
				 * @param DesktopPlayer $player
				 * @param string $username
				 * @param string $hash
				 */
				public function __construct(DesktopPlayer $player, string $username, string $hash){
					self::storeLocal($player);
					$this->username = $username;
					$this->hash = $hash;
				}

				/**
				 * @override
				 */
				public function onRun(){
					$result = null;

					$query = http_build_query([
						"username" => $this->username,
						"serverId" => $this->hash
					]);

					$response = Internet::getURL("https://sessionserver.mojang.com/session/minecraft/hasJoined?".$query, 5, [], $err, $header, $status);
					if($response === false || $status !== 200){
						$this->publishProgress("InternetException: failed to fetch session data for '$this->username'; status=$status; err=$err; response_header=".json_encode($header));
						$this->setResult(false);
						return;
					}

					$this->setResult(json_decode($response, true));
				}

				/**
				 * @override
				 * @param Server $server
				 * @param mixed $progress
				 */
				public function onProgressUpdate(Server $server, $progress){
					$server->getLogger()->error($progress);
				}

				/**
				 * @override
				 * @param $server
				 */
				public function onCompletion(Server $server){
					$result = $this->getResult();
					/** @var DesktopPlayer $player */
					$player = self::fetchLocal();
					if(is_array($result) and isset($result["id"])){
						$player->bigBrother_authenticate($result["id"], $result["properties"]);
					}else{
						$player->close("", "User not premium");
					}
				}
    }
