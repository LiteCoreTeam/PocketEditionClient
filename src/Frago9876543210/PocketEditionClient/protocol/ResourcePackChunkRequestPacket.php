<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class ResourcePackChunkRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_CHUNK_REQUEST_PACKET;

	public $packId;
	public $chunkIndex;

	protected function decodePayload() : void{
		$this->packId = $this->getString();
		$this->chunkIndex = $this->getLInt();
	}

	protected function encodePayload() : void{
		$this->putString($this->packId);
		$this->putLInt($this->chunkIndex);
	}
}