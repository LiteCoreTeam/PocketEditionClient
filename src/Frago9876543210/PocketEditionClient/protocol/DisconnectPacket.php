<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class DisconnectPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::DISCONNECT_PACKET;

	/** @var bool */
	public $hideDisconnectionScreen = false;
	/** @var string */
	public $message;

	protected function decodePayload() : void{
		$this->hideDisconnectionScreen = $this->getBool();
		$this->message = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putBool($this->hideDisconnectionScreen);
		if(!$this->hideDisconnectionScreen){
			$this->putString($this->message);
		}
	}
}