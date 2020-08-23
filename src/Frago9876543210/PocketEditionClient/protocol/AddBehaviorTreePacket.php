<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class AddBehaviorTreePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_BEHAVIOR_TREE_PACKET;

	/** @var string */
	public $unknownString1;

	protected function decodePayload() : void{
		$this->unknownString1 = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putString($this->unknownString1);
	}
}