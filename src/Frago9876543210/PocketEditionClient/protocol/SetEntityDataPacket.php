<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class SetEntityDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_ENTITY_DATA_PACKET;

	public $entityRuntimeId;
	public $metadata;

	protected function decodePayload() : void{
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->metadata = $this->getEntityMetadata();
	}

	protected function encodePayload() : void{
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putEntityMetadata($this->metadata);
	}
}