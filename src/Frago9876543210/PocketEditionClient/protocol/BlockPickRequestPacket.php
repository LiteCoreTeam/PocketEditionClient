<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class BlockPickRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::BLOCK_PICK_REQUEST_PACKET;

	public $tileX;
	public $tileY;
	public $tileZ;
	public $hotbarSlot;

	protected function decodePayload() : void{
		$this->getSignedBlockPosition($this->tileX, $this->tileY, $this->tileZ);
		$this->hotbarSlot = $this->getByte();
	}

	protected function encodePayload() : void{
		$this->putSignedBlockPosition($this->tileX, $this->tileY, $this->tileZ);
		$this->putByte($this->hotbarSlot);
	}
}