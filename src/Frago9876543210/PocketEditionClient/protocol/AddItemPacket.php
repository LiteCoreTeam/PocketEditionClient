<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


use pocketmine\item\Item;

class AddItemPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_ITEM_PACKET;

	/** @var Item */
	public $item;

	protected function decodePayload() : void{
		$this->item = $this->getSlot();
	}

	protected function encodePayload() : void{
		$this->putSlot($this->item);
	}
}