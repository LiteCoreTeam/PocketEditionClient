<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class SetHealthPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_HEALTH_PACKET;

	public $health;

	protected function decodePayload() : void{
		$this->health = $this->getVarInt();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->health);
	}
}