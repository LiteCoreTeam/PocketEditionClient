<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class SetSpawnPositionPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_SPAWN_POSITION_PACKET;

	public const TYPE_PLAYER_SPAWN = 0;
	public const TYPE_WORLD_SPAWN = 1;

	public $spawnType;
	public $x;
	public $y;
	public $z;
	public $spawnForced;

	protected function decodePayload() : void{
		$this->spawnType = $this->getVarInt();
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->spawnForced = $this->getBool();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->spawnType);
		$this->putBlockPosition($this->x, $this->y, $this->z);
		$this->putBool($this->spawnForced);
	}
}