<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class MapInfoRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::MAP_INFO_REQUEST_PACKET;

	public $mapId;

	protected function decodePayload() : void{
		$this->mapId = $this->getEntityUniqueId();
	}

	protected function encodePayload() : void{
		$this->putEntityUniqueId($this->mapId);
	}
}