<?php

include 'restcord/vendor/autoload.php';
use RestCord\DiscordClient;

try{
	//echo __FILE__.'<br>';
	//echo '<pre>'.print_r($_GET, true).'</pre>';

	if(!isset($_GET['1'])) throw new Exception('Missing Guild ID + User ID');
	if(!isset($_GET['2'])) throw new Exception('Missing User Secret');
	
	if(substr_count($_GET['1'], '/')==0) throw new Exception('Invalid Guild ID');
	$GuildID = substr($_GET['1'], 0, strpos($_GET['1'], '/'));
	$UserID = substr($_GET['1'], strpos($_GET['1'], '/')+1);
	
	//echo 'GuildID: "'.$GuildID.'"<br>UserID: "'.$UserID.'"<br>';

	$GLOBALS['mysqli'] = new mysqli('localhost', "cam", "hello", "test");
	if(!$GLOBALS['mysqli']) throw new Exception('Unable to connect to database');

	//Check for the user
	$sql = "SELECT * FROM test.user WHERE guild_id = ? AND user_id = ?";
	//echo $sql.'<br>';
	if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
	if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
	if(!$stmt->bind_param("ss", $GuildID, $UserID)) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
	if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
	$rs = $stmt->get_result();
	//echo 'Found: '.$rs->num_rows.'<br>';
	if($rs->num_rows == 0){
		//We haven't inserted this guild before.
		throw new Exception('Please execute the "tagme" command on the discord server you wish to receive tags/roles on.');
	}else{
		$User = $rs->fetch_array(MYSQLI_ASSOC);
		//echo '<pre>'.print_r($User, true).'</pre>';
		if($User['secret'] == $_GET['2']){
			//We are authenticated.
			
			
			
			//Get the guild details from Discord.
			$discord = new DiscordClient(['token' => 'NTM2NzA1OTAwNjA2NjUyNDE3.DyamAw.MZOcn9otIklwPFe10V6YsiUC7Ks']); // Token is required
			$GuildData = $discord->guild->getGuild(['guild.id' => intval($User['guild_id'])]);
			//echo '<pre>'.print_r($GuildData, true).'</pre>';
			$Member = $discord->guild->getGuildMember(['guild.id' => intval($User['guild_id']), 'user.id' => intval($User['user_id'])]);
			//echo '<pre>'.print_r($Member, true).'</pre>';
			$MemberRoles = $Member->roles;
			
			$Roles = array();
			foreach($GuildData->roles as $Role){
				if($Role->name == '@everyone') continue;
				if($Role->managed || $Role->managed=='1') continue;
			
				//Check if this role is in our DB
				$sql = "SELECT * FROM test.role WHERE guild_id = ? AND role_id = ?";
				if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
				if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
				if(!$stmt->bind_param("ss", $User['guild_id'], $Role->id)) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
				if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
				$rs = $stmt->get_result();
				//echo 'Found: '.$rs->num_rows.'<br>';
				if($rs->num_rows == 0){
					continue;
				}else{
					$Roles[$Role->id] = array('Position' => $Role->position, 'Name' => $Role->name, 'Color' => str_pad(dechex($Role->color), 6, "0", STR_PAD_LEFT));
				}
				
			}
			if(!function_exists('RoleSortByPosition')){
				function RoleSortByPosition($a, $b) {
					return $a['Position']==$b['Position'] ? 0 : ($a['Position'] < $b['Position'] ? 1 : -1);
				}
			}
			uasort($Roles, 'RoleSortByPosition');
			
			
			if(!empty($_POST)){
				//echo '<pre>'.print_r($_POST, true).'</pre>';
				
				if(count($Member->roles)>0) foreach($Member->roles as $Role){
					//echo 'Currently Has: '.$Role.'<br>';
					if(!array_key_exists($Role, $Roles)){
						//echo 'Ignore.<br>';
						continue;
					}
					if(isset($_POST['role'])){
						if(in_array($Role, $_POST['role'])){
							//Do nothing.
							//echo 'Wants to keep it.<br>';
							continue;
						}else{
							//Untag.
							//echo 'Remove it.<br>';
							$Direction = 'Remove';
							unset($MemberRoles[$Role]);
						}
					}else{
						//Untag.
						//echo 'Remove it!<br>';
						$Direction = 'Remove';
						unset($MemberRoles[$Role]);
					}
					
					$sql = "REPLACE INTO test.queue (guild_id, user_id, role_id, direction) VALUES(?, ?, ?, ?)";
					if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
					if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
					if(!$stmt->bind_param("ssss", $User['guild_id'], $User['user_id'], $Role, $Direction)) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
				}
				
				if(isset($_POST['role'])) if(count($_POST['role'])>0){
					foreach($_POST['role'] as $Role){
						//We could tag in this script, but chances are we'll probably hit a request limit, so let's throw them into the queue and let another script handle request limits.
						echo 'Wants to add '.$Role.'<br>';
						$Direction = 'Insert';
						$MemberRoles[] = $Role;
						
						$sql = "REPLACE INTO test.queue (guild_id, user_id, role_id, direction) VALUES(?, ?, ?, ?)";
						if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
						if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
						if(!$stmt->bind_param("ssss", $User['guild_id'], $User['user_id'], $Role, $Direction)) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
						if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					}
				}
				
				echo 'Your changes have been saved successfully.<br>';
			}
			
			
			//echo '<pre>'.print_r($Roles, true).'</pre>';
			
			echo '<h1>Self-Serve Roles for '.$GuildData->name.'</h1>';
			
			if(count($Roles)==0){
				echo 'No roles setup on the Discord server.';	
			}else{
				echo '
				<form method="post">
				<strong>Select which roles you\'d like to have (if you un-select a role, you will be un-tagged):</strong><ul>';
				foreach($Roles as $RoleID => $Role){
					$Selected = false;
					if(in_array($RoleID, $MemberRoles)) $Selected = true;
					
					//Check if we have a change queued.
					$sql = "SELECT * FROM test.queue WHERE guild_id = ? AND user_id = ? AND role_id = ?";
					if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
					if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
					if(!$stmt->bind_param("sss", $User['guild_id'], $User['user_id'], $RoleID)) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					$rs = $stmt->get_result();
					//echo 'Found: '.$rs->num_rows.'<br>';
					if($rs->num_rows == 0){
						$Queue = false;
						$Post = '';
					}else{
						$Queue = true;
						$Queued = $rs->fetch_array(MYSQLI_ASSOC);
						$Post = 'Queued '.($Queued['direction']=='Remove' ? 'Removal' : 'Addition');
					}
					
					echo '<li><input type="checkbox" name="role[]" value="'.$RoleID.'"'.($Selected ? ' checked="checked"' : '').'> <span style="color: #'.$Role['Color'].'">'.$Role['Name'].'</span>'.($Queue ? ' ('.$Post.')' : '').'</li>';
				}
				echo '</ul>
				<input type="hidden" name="guild" value="'.$Guild['guild_id'].'">
				<button type="submit">Submit</button>
				</form>';
			}
			
			
		}else{
			throw new Exception('Please execute the "tagme" command on the discord server you wish to receive tags/roles on.');
		}
		
	}

}catch(Exception $e){
	echo 'Caught Exception ['.$e->getLine().']: '.$e->getMessage().'<br>';
}


?>