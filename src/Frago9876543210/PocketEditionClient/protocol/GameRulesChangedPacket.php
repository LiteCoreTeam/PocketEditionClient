<?php

declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient\protocol;


class GameRulesChangedPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::GAME_RULES_CHANGED_PACKET;

	/** @var array */
	public $gameRules = [];

	protected function decodePayload() : void{
		$this->gameRules = $this->getGameRules();
	}

	protected function encodePayload() : void{
		$this->putGameRules($this->gameRules);
	}
}