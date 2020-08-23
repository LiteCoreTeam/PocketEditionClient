<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class CameraPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CAMERA_PACKET;

	/** @var int */
	public $cameraUniqueId;
	/** @var int */
	public $playerUniqueId;

	protected function decodePayload() : void{
		$this->cameraUniqueId = $this->getEntityUniqueId();
		$this->playerUniqueId = $this->getEntityUniqueId();
	}

	protected function encodePayload() : void{
		$this->putEntityUniqueId($this->cameraUniqueId);
		$this->putEntityUniqueId($this->playerUniqueId);
	}
}