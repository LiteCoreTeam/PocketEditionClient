<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


use pocketmine\math\Vector3;

class SetEntityMotionPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_ENTITY_MOTION_PACKET;

	/** @var int */
	public $entityRuntimeId;
	/** @var Vector3 */
	public $motion;

	protected function decodePayload() : void{
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->motion = $this->getVector3();
	}

	protected function encodePayload() : void{
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putVector3Nullable($this->motion);
	}
}