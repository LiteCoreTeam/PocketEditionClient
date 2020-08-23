<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


use pocketmine\network\mcpe\handler\SessionHandler;

class DataPacket extends \pocketmine\network\mcpe\protocol\DataPacket{

	protected function decodeHeader() : void{
		$pid = $this->getByte();
		assert($pid === static::NETWORK_ID);
	}

	protected function encodeHeader() : void{
		$this->putByte(static::NETWORK_ID);
	}

	public function handle(SessionHandler $handler) : bool{
		return false;
	}
}