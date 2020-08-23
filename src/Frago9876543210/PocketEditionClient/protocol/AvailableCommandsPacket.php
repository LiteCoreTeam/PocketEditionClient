<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class AvailableCommandsPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::AVAILABLE_COMMANDS_PACKET;

	public $commands; //JSON-encoded command data
	public $unknown = "";

	protected function decodePayload() : void{
		$this->commands = $this->getString();
		//$this->unknown = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putString($this->commands);
		$this->putString($this->unknown);
	}
}