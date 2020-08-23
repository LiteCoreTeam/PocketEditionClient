<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class SetCommandsEnabledPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_COMMANDS_ENABLED_PACKET;

	public $enabled;

	protected function decodePayload() : void{
		$this->enabled = $this->getBool();
	}

	protected function encodePayload() : void{
		$this->putBool($this->enabled);
	}
}