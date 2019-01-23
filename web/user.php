<?php

include 'restcord/vendor/autoload.php';
use RestCord\DiscordClient;

//Check if we need to handle an ajax request.

if(!empty($_POST)){
	try{
		if(!isset($_POST['action'])) throw new Exception('No action defined.');
		
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
		if($rs->num_rows == 0) throw new Exception('Please execute the "tagme" command on the discord server you wish to receive tags/roles on.');
		$User = $rs->fetch_array(MYSQLI_ASSOC);
		if($User['secret'] != $_GET['2']) throw new Exception('Please execute the "tagme" command on the discord server you wish to receive tags/roles on.');
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

		switch(strtolower($_POST['action'])){
			case 'save':				
				if(true){
					//Add the roles into the queue.
					if(count($Member->roles)>0) foreach($Member->roles as $Role){
						//echo 'Currently Has: '.$Role.', ';
						if(!array_key_exists($Role, $Roles)){
							//echo 'Not a self-serve role.<br>';
							continue; //Not a configurable role.
						}

						if(isset($_POST['role'])){
							if(in_array($Role, $_POST['role'])){
								//echo 'Wants to keep it.<br>';
								continue; //Has the role and the role was selected, no action.
							}

							$Direction = 'Remove';
						}else{
							$Direction = 'Remove';
						}
						
						//echo 'Removing!<br>';

						$sql = "REPLACE INTO test.queue (guild_id, user_id, role_id, direction) VALUES(?, ?, ?, ?)";
						if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
						if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
						if(!$stmt->bind_param("ssss", $User['guild_id'], $User['user_id'], $Role, $Direction)) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
						if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					}

					if(isset($_POST['role'])) if(count($_POST['role'])>0){
						foreach($_POST['role'] as $Role){
							
							foreach($Member->roles as $R){
								if($R == $Role) continue 2;
							}
							
							//echo 'Wants '.$Role.'<br>';
							$Direction = 'Insert';

							$sql = "REPLACE INTO test.queue (guild_id, user_id, role_id, direction) VALUES(?, ?, ?, ?)";
							if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
							if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
							if(!$stmt->bind_param("ssss", $User['guild_id'], $User['user_id'], $Role, $Direction)) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
							if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
						}
					}

					$Return = array(
						'title' => 'Updating Roles', 
						'body' => 'We\'re busy updating your roles, please wait...', 
						'footer' => '', 
						'repeat' => true,
						'closable' => false
					);
				}
				break;
			case 'check':
				if(true){
					
					$Return = array(
						'title' => 'Updating Roles', 
						'body' => 'Done! Your roles have been updated!', 
						'footer' => '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>',
						'repeat' => true,
						'roles' => array(),
						'closable' => false
					);
					
					$sql = "SELECT * FROM test.queue WHERE guild_id = ? AND user_id = ?";
					if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
					if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
					if(!$stmt->bind_param("ss", $User['guild_id'], $User['user_id'])) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					$rs = $stmt->get_result();
					//echo 'Found: '.$rs->num_rows.'<br>';
					if($rs->num_rows == 0){
						unset($Return['repeat']);
						$Return['closable'] = true;
					}else{
						$Return['body'] = '<i class="fas fa-spinner fa-spin"></i> '.$rs->num_rows.' Role'.($rs->num_rows > 1 ? 's' : '').' to go, please wait... ';
						$Return['footer'] = '<span class="text-muted">We\'ll keep checking for you, no need to refresh.</span>';
					}
										
					foreach($Roles as $RoleID => $Role){
						$Selected = false;
						if(in_array($RoleID, $MemberRoles)) $Selected = true;
						$Return['roles'][$RoleID] = $Selected;
					}
				}
				break;
			default:
				throw new Exception('Action not defined.');			
		}
	}catch(Exception $e){
		$Return = array(
			'title' => 'An Error Occured!',
			'body' => $e->getMessage(),
			'footer' => '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>'
		);
	}
	echo json_encode($Return);
	exit;
}


try{
	//echo __FILE__.'<br>';
	//echo '<pre>'.print_r($_GET, true).'</pre>';
	require_once "includes/header.php";

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
	if($rs->num_rows == 0) throw new Exception('Please execute the "tagme" command on the discord server you wish to receive tags/roles on.');
	
	$User = $rs->fetch_array(MYSQLI_ASSOC);
	//echo '<pre>'.print_r($User, true).'</pre>';
	if($User['secret'] != $_GET['2']) throw new Exception('Please execute the "tagme" command on the discord server you wish to receive tags/roles on.');
	
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
	//echo '<pre>'.print_r($Roles, true).'</pre>';

	echo '<div class="row"><div class="col"><h1>Self-Serve Roles for '.$GuildData->name.'</h1></div></div>';

	if(count($Roles)==0){
		throw new Exception('No self-selve roles configured.');	
	}else{
		echo '
		<form method="post">
			<strong>Select which roles you\'d like to have (if you un-select a role, you will be un-tagged):</strong>';
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

			
				echo '
			<div class="custom-control custom-switch">
				<input type="checkbox" class="custom-control-input" id="Role-'.$RoleID.'" name="role[]" value="'.$RoleID.'"'.($Selected ? ' checked="checked"' : '').($Queue ? ' disabled':'').'>
				<label class="custom-control-label" for="Role-'.$RoleID.'">
					<span class="tag" style="border-color: #'.$Role['Color'].';color: #'.$Role['Color'].'">'.$Role['Name'].'</span>
					'.($Queue ? ' ('.$Post.')' : '').'
				</label>
			</div>';
			
		}
		echo '
			<input type="hidden" name="guild" id="guild" value="'.$User['guild_id'].'">
			<input type="hidden" name="user" id="user" value="'.$User['user_id'].'">
			<input type="hidden" name="secret" id="secret" value="'.$User['secret'].'">
			<input type="hidden" name="action" value="save">
			<button type="button" class="btn btn-outline-primary mt-3" id="User-Save">Save</button>
		</form>';
	}


	

}catch(Exception $e){
	echo '<div class="alert alert-danger" role="alert" data-line="'.$e->getLine().'">'.$e->getMessage().'</div>';
}
?>
<style>
.tag {
	padding:2px;
	margin:2px;
	display:inline-block;
	font-weight: bold;
	border: 1px solid #000000;
}
</style>
<?php	
require_once "includes/footer.php";
?>