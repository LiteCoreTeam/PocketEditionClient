<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class ShowStoreOfferPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SHOW_STORE_OFFER_PACKET;

	public $offerId;

	protected function decodePayload() : void{
		$this->offerId = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putString($this->offerId);
	}
}