<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class ContainerClosePacke extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CONTAINER_CLOSE_PACKET;

	public $windowid;
	public $windowidd;

	protected function decodePayload() : void{
		//$this->windowid = $this->getByte();
		$this->windowidd = $this->getByte();
	}

	protected function encodePayload() : void{
		//$this->putByte($this->windowid);
		$this->putByte($this->windowidd);
	}
}