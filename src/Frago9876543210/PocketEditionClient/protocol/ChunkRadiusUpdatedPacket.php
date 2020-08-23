<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class ChunkRadiusUpdatedPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CHUNK_RADIUS_UPDATED_PACKET;

	public $radius;

	public $rradius;

	protected function decodePayload() : void{
		$this->radius = $this->getVarInt();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->radius);
	}
}