<?php

//Script should be executed on the command line.

include 'restcord/vendor/autoload.php';
use RestCord\DiscordClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
$log = new Logger('name');
$log->pushHandler(new StreamHandler('/www/web/queue-process.log', Logger::WARNING));

try{
	$GLOBALS['mysqli'] = new mysqli('localhost', "cam", "hello", "test");
	if(!$GLOBALS['mysqli']) throw new Exception('Unable to connect to database');

	//Check for any queued actions
	$sql = "SELECT * FROM test.queue";
	//echo $sql.'<br>';
	if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
	if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
	if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
	$rs = $stmt->get_result();
	//echo 'Found: '.$rs->num_rows.'<br>';
	if($rs->num_rows == 0){
		//We haven't inserted this guild before.
		echo 'Nothing Queued.'.PHP_EOL;
	}else{
		while($Queue = $rs->fetch_array(MYSQLI_ASSOC)){
			echo (strtolower($Queue['direction'])=='remove' ? 'Removing':'Adding').' '.$Queue['role_id'].' to '.$Queue['user_id'].' in '.$Queue['guild_id'].PHP_EOL;
			if(!isset($discord)) $discord = new DiscordClient(['token' => 'NTM2NzA1OTAwNjA2NjUyNDE3.DyamAw.MZOcn9otIklwPFe10V6YsiUC7Ks']); //, 'logger' => $log]); // Token is required
			switch(strtolower($Queue['direction'])){
				case 'remove':
					//untag.
					$Output = $discord->guild->removeGuildMemberRole(['guild.id' => intval($Queue['guild_id']), 'user.id' => intval($Queue['user_id']), 'role.id' => intval($Queue['role_id'])]);
					break;
				case 'insert':
					//tag.
					$Output = $discord->guild->addGuildMemberRole(['guild.id' => intval($Queue['guild_id']), 'user.id' => intval($Queue['user_id']), 'role.id' => intval($Queue['role_id'])]);
					break;
			}
			
			$sql = "DELETE FROM test.queue WHERE id = ?";
			if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
			if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
			if(!$stmt->bind_param("i", $Queue['id'])) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
			if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
			
			echo print_r($Output, true).PHP_EOL;
			if(isset($Output['retry_after'])){
				echo 'Sleeping for '.($Output['retry_after']/1000 + 1).PHP_EOL;
				sleep(($Output['retry_after']/1000 + 1));	
			}
		}
	}
}catch(Exception $e){
	echo 'Caught Exception ['.$e->getLine().']: '.$e->getMessage().PHP_EOL;
}


?>