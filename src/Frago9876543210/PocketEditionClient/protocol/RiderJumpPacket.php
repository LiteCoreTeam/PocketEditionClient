<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class RiderJumpPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RIDER_JUMP_PACKET;

	/** @var int */
	public $jumpStrength; //percentage

	protected function decodePayload() : void{
		$this->jumpStrength = $this->getVarInt();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->jumpStrength);
	}
}
