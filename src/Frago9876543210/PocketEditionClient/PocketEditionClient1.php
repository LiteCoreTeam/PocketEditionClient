<?php namespace Frago9876543210\PocketEditionClient;

use raklib\protocol\UnconnectedPing;
use raklib\protocol\Packet;
use raklib\server\UDPServerSocket;
use Frago9876543210\PocketEditionClient\protocol\PacketPool;
use pocketmine\entity\Attribute;

class PocketEditionClient1 extends UDPServerSocket{
    /** @var Address */
    private $serverAddress;

    public $time;

    public $ans = false;

    public $aa;

    public function __construct(Address $bindAddress, Address $serverAddress){
        parent::__construct($bindAddress);
        $this->serverAddress = $serverAddress;
        
    }

    public function check(){ //Thank, @Frago9876543210
        $ping = new UnconnectedPing();
        $ping->pingID = 00000000;
        $this->sendRakNetPacket($ping);
        $this->time = time();
        $this->aa = $this->time + 10;
        $d = date("H:i:s");
        echo "\n[$d] Запрос на сервер отправлен, ожидайте.";
    }

    protected function sendRakNetPacket(Packet $packet) : void{
        $packet->encode();
        $this->writePacket($packet->buffer, $this->serverAddress->ip, $this->serverAddress->port);
    }

    public function tick() : void{
    	$d = date("H:i:s");
    	if ($this->ans == false and $this->aa < time()) {
    		echo "\n[$d] Сервер отключён или Ваш айпи на нём заблокирован! \n[$d] Следующий запрос будет отправлен через 30-ть секунд!";
            sleep(30);
            $this->check();
    	}
        if($this->readPacket($buffer, $this->serverAddress->ip, $this->serverAddress->port) !== false){
            if(($packet = RakNetPool::getPacket($buffer)) !== null){
            	$this->ans = true;
                echo "\n[$d] Сервер жив, готов принимать ислам..";
                $crash = 'crash.txt';
                file_put_contents($crash, 1);
                require_once "vendor/autoload.php";
                ini_set("memory_limit", "-1");
                RakNetPool::init();
                PacketPool::init();
                Attribute::init();
                $client = new PocketEditionClient(new Address("0.0.0.0", mt_rand(10000,60000)), new Address($this->serverAddress->ip, $this->serverAddress->port));
                $client->sendOpenConnectionRequest1();
                while(true){
                    $client->tick();
                }
            }
        }
    }
}