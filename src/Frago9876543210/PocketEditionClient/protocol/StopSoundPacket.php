<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class StopSoundPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::STOP_SOUND_PACKET;

	public $soundName;
	public $stopAll;

	protected function decodePayload() : void{
		$this->soundName = $this->getString();
		$this->stopAll = $this->getBool();
	}

	protected function encodePayload() : void{
		$this->putString($this->soundName);
		$this->putBool($this->stopAll);
	}
}