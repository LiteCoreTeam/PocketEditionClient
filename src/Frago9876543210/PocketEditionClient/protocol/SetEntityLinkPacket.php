<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class SetEntityLinkPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_ENTITY_LINK_PACKET;

	public $from;
	public $to;
	public $type;

	protected function decodePayload() : void{
		$this->from = $this->getEntityUniqueId();
		$this->to = $this->getEntityUniqueId();
		$this->type = $this->getByte();
	}

	protected function encodePayload() : void{
		$this->putEntityUniqueId($this->from);
		$this->putEntityUniqueId($this->to);
		$this->putByte($this->type);
	}
}