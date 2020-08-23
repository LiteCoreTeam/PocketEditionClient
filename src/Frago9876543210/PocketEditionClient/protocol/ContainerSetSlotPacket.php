<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


use pocketmine\item\Item;

class ContainerSetSlotPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CONTAINER_SET_SLOT_PACKET;

	public $windowid;
	public $slot;
	public $hotbarSlot = 0;
	/** @var Item */
	public $item;
	public $selectSlot = 0;

	protected function decodePayload() : void{
		$this->windowid = $this->getByte();
		$this->slot = $this->getVarInt();
		$this->hotbarSlot = $this->getVarInt();
		$this->item = $this->getSlot();
		//$this->selectSlot = $this->getByte();
	}

	protected function encodePayload() : void{
		$this->putByte($this->windowid);
		$this->putVarInt($this->slot);
		$this->putVarInt($this->hotbarSlot);
		$this->putSlot($this->item);
		$this->putByte($this->selectSlot);
	}
}