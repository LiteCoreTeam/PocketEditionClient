<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class ItemFrameDropItemPacket extends DataPacket{

	public const NETWORK_ID = ProtocolInfo::ITEM_FRAME_DROP_ITEM_PACKET;

	public $x;
	public $y;
	public $z;

	protected function decodePayload() : void{
		$this->getBlockPosition($this->x, $this->y, $this->z);
	}

	protected function encodePayload() : void{
		$this->putBlockPosition($this->x, $this->y, $this->z);
	}
}