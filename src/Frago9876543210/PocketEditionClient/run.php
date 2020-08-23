<?php

declare(strict_types=1);

namespace Frago9876543210\PocketEditionClient {

	use Frago9876543210\PocketEditionClient\protocol\PacketPool;
	use pocketmine\entity\Attribute;

//	date_default_timezone_set('Asia/Yekaterinburg');
	require_once "vendor/autoload.php";
	ini_set("memory_limit", "-1");
	RakNetPool::init();
	PacketPool::init();
	Attribute::init();

	$crash = 'crash.txt';
	echo "[!] Введите IP: ";
	$ip = substr(fgets(STDIN),0,-1);
	echo "\n[!] Введите PORT: ";
	$port = (int) fgets(STDIN);
	echo "\n[!] 0 - нет, 1 - да, 2 - создать сессию, 3 - проверить раклиб\n";
	echo "\n[!] Выберите функцию: ";
	$crash_1 = (int) fgets(STDIN);
	file_put_contents($crash, $crash_1);

	if ($crash_1 == "3") {
		$client = new PocketEditionClient1(new Address("0.0.0.0", mt_rand(10000,60000)), new Address($ip, $port));
	    while(true){
		    $client->tick();
	    }
	}else{
		$client = new PocketEditionClient(new Address("0.0.0.0", mt_rand(10000,60000)), new Address($ip, $port));
        $client->sendOpenConnectionRequest1();
	    while(true){
		    $client->tick();
	    }
	}
}