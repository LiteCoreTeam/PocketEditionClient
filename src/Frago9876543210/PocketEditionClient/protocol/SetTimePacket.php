<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class SetTimePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_TIME_PACKET;

	/** @var int */
	public $time;

	public function decodePayload() : void{
		$this->time = $this->getVarInt();
	}

	public function encodePayload() : void{
		$this->putVarInt($this->time);
	}
}