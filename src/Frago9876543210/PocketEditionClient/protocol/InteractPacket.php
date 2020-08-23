<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class InteractPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::INTERACT_PACKET;

	public const ACTION_RIGHT_CLICK = 1;
	public const ACTION_LEFT_CLICK = 2;
	public const ACTION_LEAVE_VEHICLE = 3;
	public const ACTION_MOUSEOVER = 4;

	public const ACTION_OPEN_INVENTORY = 6;

	public $action;
	public $target;

	protected function decodePayload() : void{
		$this->action = $this->getByte();
		$this->target = $this->getEntityRuntimeId();
	}

	protected function encodePayload() : void{
		$this->putByte($this->action);
		$this->putEntityRuntimeId($this->target);
	}
}