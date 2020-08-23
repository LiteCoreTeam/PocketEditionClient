<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class RequestChunkRadiusPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::REQUEST_CHUNK_RADIUS_PACKET;

	public $radius;

	protected function decodePayload() : void{
		$this->radius = $this->getVarInt();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->radius);
	}
}