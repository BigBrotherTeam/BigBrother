<?php


namespace shoghicp\BigBrother\network\protocol\Play\Server;


use shoghicp\BigBrother\network\OutboundPacket;

class MoveEntityPacket extends OutboundPacket{
	/** @var int $entityid */
	public $entityid;
	/** @var int $xa */
	public $xa;
	/** @var int $ya */
	public $ya;
	/** @var int $za */
	public $za;
	/** @var int $yaw */
	public $yaw;
	/** @var int $pitch */
	public $pitch;
	/** @var bool onGround */
	public $onground = true;
	/** @var bool onGround */
	public $hasyaw = true;
	/** @var bool onGround */
	public $haspitch = true;

	public function pid() : int{
		return 40;//Entity_move_packet
	}

	protected function encode() : void{
		$this->putVarInt($this->entityid);
		$this->putShort($this->xa);

	}
}
