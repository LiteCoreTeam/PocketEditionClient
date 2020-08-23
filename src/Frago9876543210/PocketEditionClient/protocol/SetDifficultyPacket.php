<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class SetDifficultyPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_DIFFICULTY_PACKET;

	public $difficulty;

	protected function decodePayload() : void{
		$this->difficulty = $this->getUnsignedVarInt();
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt($this->difficulty);
	}
}