<?php
declare(strict_types=1);

namespace shoghicp\BigBrother;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;
use pocketmine\level\Level;
use pocketmine\utils\Internet;

class handleauthtask extends AsyncTask{

						/** @var string */
						private $username;

						/**
						 * @param BigBrother $plugin
						 * @param DesktopPlayer $player
						 * @param string $username
						 */
						public function __construct(BigBrother $plugin, DesktopPlayer $player, string $username){
							self::storeLocal([$plugin, $player]);
							$this->username = $username;
						}

						/**
						 * @override
						 */
						public function onRun(){
							$profile = null;
							$info = null;

							$response = Internet::getURL("https://api.mojang.com/users/profiles/minecraft/".$this->username, 10, [], $err, $header, $status);
							if($status === 204){
								$this->publishProgress("UserNotFound: failed to fetch profile for '$this->username'; status=$status; err=$err; response_header=".json_encode($header));
								$this->setResult([
									"id" => str_replace("-", "", UUID::fromRandom()->toString()),
									"name" => $this->username,
									"properties" => []
								]);
								return;
							}

							if($response === false || $status !== 200){
								$this->publishProgress("InternetException: failed to fetch profile for '$this->username'; status=$status; err=$err; response_header=".json_encode($header));
								$this->setResult(false);
								return;
							}

							$profile = json_decode($response, true);
							if(!is_array($profile)){
								$this->publishProgress("UnknownError: failed to parse profile for '$this->username'; status=$status; response=$response; response_header=".json_encode($header));
								$this->setResult(false);
								return;
							}

							$uuid = $profile["id"];
							$response = Internet::getURL("https://sessionserver.mojang.com/session/minecraft/profile/".$uuid, 3, [], $err, $header, $status);
							if($response === false || $status !== 200){
								$this->publishProgress("InternetException: failed to fetch profile info for '$this->username'; status=$status; err=$err; response_header=".json_encode($header));
								$this->setResult(false);
								return;
							}

							$info = json_decode($response, true);
							if($info === null or !isset($info["id"])){
								$this->publishProgress("UnknownError: failed to parse profile info for '$this->username'; status=$status; response=$response; response_header=".json_encode($header));
								$this->setResult(false);
								return;
							}

							$this->setResult($info);
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
						 * @param Server $server
						 */
						public function onCompletion(Server $server){
							$info = $this->getResult();
							if(is_array($info)){
								list($plugin, $player) = self::fetchLocal();

								/** @var BigBrother $plugin */
								$plugin->setProfileCache($this->username, $info);

								/** @var DesktopPlayer $player */
								$player->bigBrother_authenticate($info["id"], $info["properties"]);
							}
						}

					}
