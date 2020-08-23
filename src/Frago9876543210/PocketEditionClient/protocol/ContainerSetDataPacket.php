<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class ContainerSetDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CONTAINER_SET_DATA_PACKET;

	public $windowid;
	public $property;
	public $value;

	protected function decodePayload() : void{
		$this->windowid = $this->getByte();
		$this->property = $this->getVarInt();
		$this->value = $this->getVarInt();
	}

	protected function encodePayload() : void{
		$this->putByte($this->windowid);
		$this->putVarInt($this->property);
		$this->putVarInt($this->value);
	}
}