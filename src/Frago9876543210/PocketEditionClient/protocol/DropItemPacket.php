<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


use pocketmine\item\Item;

class DropItemPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::DROP_ITEM_PACKET;

	public $type;
	/** @var Item */
	public $item;

	protected function decodePayload() : void{
		$this->type = $this->getByte();
		$this->item = $this->getSlot();
	}

	protected function encodePayload() : void{
		$this->putByte($this->type);
		$this->putSlot($this->item);
	}
}