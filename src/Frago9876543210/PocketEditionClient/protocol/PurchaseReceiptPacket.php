<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class PurchaseReceiptPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PURCHASE_RECEIPT_PACKET;

	/** @var string[] */
	public $entries = [];

	protected function decodePayload() : void{
		$count = $this->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			$this->entries[] = $this->getString();
		}
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			$this->putString($entry);
		}
	}
}