<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


use pocketmine\item\Item;

class ReplaceItemInSlotPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::REPLACE_ITEM_IN_SLOT_PACKET;

	/** @var Item */
	public $item;

	protected function decodePayload() : void{
		$this->item = $this->getSlot();
	}

	protected function encodePayload() : void{
		$this->putSlot($this->item);
	}
}