<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class SimpleEventPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SIMPLE_EVENT_PACKET;

	public $unknownShort1;

	protected function decodePayload() : void{
		$this->unknownShort1 = $this->getLShort();
	}

	protected function encodePayload() : void{
		$this->putLShort($this->unknownShort1);
	}
}