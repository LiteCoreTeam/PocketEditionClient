<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class ContainerClosePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CONTAINER_CLOSE_PACKET;

	public $windowid;

	protected function decodePayload() : void{
		$this->windowid = $this->getByte();
	}

	protected function encodePayload() : void{
		$this->putByte($this->windowid);
	}
}