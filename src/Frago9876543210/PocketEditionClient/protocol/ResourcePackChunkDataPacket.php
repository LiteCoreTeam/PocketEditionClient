<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class ResourcePackChunkDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_CHUNK_DATA_PACKET;

	public $packId;
	public $chunkIndex;
	public $progress;
	public $data;

	protected function decodePayload() : void{
		$this->packId = $this->getString();
		$this->chunkIndex = $this->getLInt();
		$this->progress = $this->getLLong();
		$this->data = $this->get($this->getLInt());
	}

	protected function encodePayload() : void{
		$this->putString($this->packId);
		$this->putLInt($this->chunkIndex);
		$this->putLLong($this->progress);
		$this->putLInt(strlen($this->data));
		$this->put($this->data);
	}
}