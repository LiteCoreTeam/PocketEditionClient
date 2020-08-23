<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class SetPlayerGameTypePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_PLAYER_GAME_TYPE_PACKET;

	public $gamemode;

	protected function decodePayload() : void{
		$this->gamemode = $this->getVarInt();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->gamemode);
	}
}