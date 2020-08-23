<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class CommandStepPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::COMMAND_STEP_PACKET;

	public $command;
	public $overload;
	public $uvarint1;
	public $currentStep;
	public $done;
	public $clientId;
	public $inputJson;
	public $outputJson;

	protected function decodePayload() : void{
		$this->command = $this->getString();
		$this->overload = $this->getString();
		$this->uvarint1 = $this->getString(); // getString
		$this->currentStep = $this->getString(); // getString
		$this->done = $this->getBool();
		$this->clientId = $this->getUnsignedVarLong();
		$this->inputJson = json_decode($this->getString());
		$this->outputJson = json_decode($this->getString());

		$this->getRemaining(); //TODO: read command origin data
	}

	protected function encodePayload() : void{
		$this->putString($this->command);
		$this->putString($this->overload);
		$this->putString($this->uvarint1); // putString
		$this->putUnsignedVarLong($this->currentStep); // putString
		$this->putBool($this->done);
		$this->putUnsignedVarLong($this->clientId);
		$this->putString($this->inputJson);
		$this->putString($this->outputJson);

		$this->put("\x00\x00\x00"); //TODO: command origin data
	}
}