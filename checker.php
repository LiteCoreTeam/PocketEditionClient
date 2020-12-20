<?php

declare(strict_types=1);

use parallel\{Channel, Runtime};

class ParallelPool{
	/** @var Channel */
	private $channel;
	/** @var Runtime[] */
	private $workers = [];

	public function __construct(int $workers, string $bootstrap){
		$this->channel = new Channel();

		for($i = 0; $i < $workers; $i++){
			$runtime = new Runtime($bootstrap);
			$this->workers[$i] = $runtime;

			$runtime->run(function(Channel $channel) : void{
				while(($job = $channel->recv())){
					list($task, $args) = $job;
					($task)(...$args);
				}
			}, [$this->channel]);
		}
	}

	public function submitTask(Closure $task, array $argv) : void{
		$this->channel->send([$task, $argv]);
	}

	public function __destruct(){
		foreach($this->workers as $worker){
			$this->channel->send(false);
		}

		foreach($this->workers as $worker){
			$worker->close();
		}

		$this->channel->close();
	}
}

$pool = new ParallelPool(8, "vendor/autoload.php");

$start = 19132;
$end = 19132 + 100;

for($i = $start; $i <= $end; ++$i){
	$pool->submitTask(function(string $hostname, int $port){
		$socket = @fsockopen("udp://$hostname", $port);
		if($socket !== false){
			stream_set_blocking($socket, true);
			stream_set_timeout($socket, 10);
		}

		fwrite($socket, "\x01" . "\0\0\0\0\0\0\0\0" . "\x00\xff\xff\x00\xfe\xfe\xfe\xfe\xfd\xfd\xfd\xfd\x12\x34\x56\x78");
		if(!empty($data = fread($socket, 1)) && ord($data[0]) === 0x1c){
			echo $port . ", ";
			//echo "$hostname:$port" . PHP_EOL;
		}
	}, ["hypego.ru", $i]);
}
