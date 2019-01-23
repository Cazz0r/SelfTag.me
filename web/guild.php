<?php

include 'restcord/vendor/autoload.php';
use RestCord\DiscordClient;

try{
	//echo __FILE__.'<br>';
	//echo '<pre>'.print_r($_GET, true).'</pre>';
	
	require_once "includes/header.php";

	if(!isset($_GET['1'])) throw new Exception('Missing Guild ID');
	if(!isset($_GET['2'])) throw new Exception('Missing Guild Secret');

	$GLOBALS['mysqli'] = new mysqli('localhost', "cam", "hello", "test");
	if(!$GLOBALS['mysqli']) throw new Exception('Unable to connect to database');

	//Check for the guild
	$sql = "SELECT * FROM test.guild WHERE guild_id = ?";
	//echo $sql.'<br>';
	if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
	if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
	if(!$stmt->bind_param("s", $_GET['1'])) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
	if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
	$rs = $stmt->get_result();
	//echo 'Found: '.$rs->num_rows.'<br>';
	if($rs->num_rows == 0){
		//We haven't inserted this guild before.
		throw new Exception('Please execute the "configure" command on the discord server you wanted to configure.');
	}else{
		$Guild = $rs->fetch_array(MYSQLI_ASSOC);
		if($Guild['secret'] == $_GET['2']){
			//We are authenticated.
			
			if(!empty($_POST)){
				//echo '<pre>'.print_r($_POST, true).'</pre>';
				
				//Delete existing roles.
				$sql = "DELETE FROM test.role WHERE guild_id = ?";
				if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
				if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
				if(!$stmt->bind_param("s", $Guild['guild_id'])) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
				if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
				
				//Add the roles we just saved.
				if(isset($_POST['role'])) if(count($_POST['role'])>0){
					foreach($_POST['role'] as $Role){
						$sql = "INSERT INTO test.role (guild_id, role_id) VALUES(?, ?)";
						if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
						if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
						if(!$stmt->bind_param("ss", $Guild['guild_id'], $Role)) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
						if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					}
				}
				
				echo '<div class="alert alert-success mt-3" role="alert">Your changes have been saved successfully.</div>';
			}
			
			//Get the guild details from Discord.
			$discord = new DiscordClient(['token' => 'NTM2NzA1OTAwNjA2NjUyNDE3.DyamAw.MZOcn9otIklwPFe10V6YsiUC7Ks']); // Token is required
			$GuildData = $discord->guild->getGuild(['guild.id' => intval($Guild['guild_id'])]);
			//echo '<pre>'.print_r($GuildData, true).'</pre>';
			
			$Bot = $discord->guild->getGuildMember(['guild.id' => intval($Guild['guild_id']), 'user.id' => intval(536705900606652417)]);
			//echo '<pre>'.print_r($Bot, true).'</pre>';
			
			//Find the bot role.
			$BotRole = 0;
			$BotRoleName = 'Role Bot';
			$BotRolePosition = 0;
			$BotRoleColor = 0;
			foreach($GuildData->roles as $Role){
				if($Role->managed != '1') continue;
				//echo 'Managed Role: '.$Role->name.'<br>';
				if(in_array($Role->id, $Bot->roles)){
					//echo '+ Bot Has it.<br>';
					$BotRole = $Role->id;
					$BotRoleName = $Role->name;
					$BotRolePosition = $Role->position;
					$BotRoleColor = str_pad(dechex($Role->color), 6, "0", STR_PAD_LEFT);
				}				
			}
			
			$Roles = array();
			foreach($GuildData->roles as $Role){
				if($Role->name == '@everyone') continue;
				if($Role->managed=='1') continue;
				if($Role->position >= $BotRolePosition) continue;
				//echo '<pre>'.print_r($Role, true).'</pre>';
				$Roles[$Role->id] = array('Position' => $Role->position, 'Name' => $Role->name, 'Color' => str_pad(dechex($Role->color), 6, "0", STR_PAD_LEFT));
			}
			if(!function_exists('RoleSortByPosition')){
				function RoleSortByPosition($a, $b) {
					return $a['Position']==$b['Position'] ? 0 : ($a['Position'] < $b['Position'] ? 1 : -1);
				}
			}
			uasort($Roles, 'RoleSortByPosition');
			//echo '<pre>'.print_r($Roles, true).'</pre>';
			
			echo '<div class="row"><div class="col"><h1>Configure Discord Server: '.$GuildData->name.'</h1></div></div>';
			
			if(count($Roles)==0){
				echo '
				<div class="alert alert-danger" role="alert">
					<i class="fas fa-exclamation-triangle"></i> No eligible roles setup on your Discord server!
					<div><i class="fas fa-info-circle"></i> Remember that the <span style="border-color: #'.$BotRoleColor.'; color: #'.$BotRoleColor.'">'.$BotRoleName.'</span> role needs to be <strong>Above</strong> any roles you want to make self-serve.</div>
				</div>';	
			}else{
				echo '
				<form method="post">
					
					<strong>Select which roles you\'d like to allow as self-serve:</strong>';
				foreach($Roles as $RoleID => $Role){
					
					//Let's check if this role is already part of our options.
					$sql = "SELECT * FROM test.role WHERE guild_id = ? AND role_id = ?";
					if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
					if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
					if(!$stmt->bind_param("ss", $Guild['guild_id'], $RoleID)) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					$rs = $stmt->get_result();
					//echo 'Found: '.$rs->num_rows.'<br>';
					if($rs->num_rows == 0){
						//Not present!
						$Selected = false;
					}else{
						//Present!
						$Selected = true;
					}
					
					if(false){ //Standard
						echo '
					<div class="form-check">
						<input class="form-check-input" type="checkbox" id="Role-'.$RoleID.'" name="role[]" value="'.$RoleID.'"'.($Selected ? ' checked="checked"' : '').'>
						<label class="form-check-label tag" for="Role-'.$RoleID.'" style="border-color: #'.$Role['Color'].';color: #'.$Role['Color'].'">'.$Role['Name'].'</label>
					</div>';
					}
					if(false){ //Custom
						echo '
					<div class="custom-control custom-checkbox mr-sm-2">
						<input type="checkbox" class="custom-control-input" id="Role-'.$RoleID.'" name="role[]" value="'.$RoleID.'"'.($Selected ? ' checked="checked"' : '').'>
						<label class="custom-control-label tag" for="Role-'.$RoleID.'" style="border-color: #'.$Role['Color'].';color: #'.$Role['Color'].'">'.$Role['Name'].'</label>
					</div>';
					}
					if(true){ //Slider
						echo '
					<div class="custom-control custom-switch">
						<input type="checkbox" class="custom-control-input" id="Role-'.$RoleID.'" name="role[]" value="'.$RoleID.'"'.($Selected ? ' checked="checked"' : '').'>
						<label class="custom-control-label tag" for="Role-'.$RoleID.'" style="border-color: #'.$Role['Color'].';color: #'.$Role['Color'].'">'.$Role['Name'].'</label>
					</div>';
					}
				}
				echo '
					<div class="mt-3"><i class="fas fa-info-circle"></i> Roles Missing? Remember that the <span style="border-color: #'.$BotRoleColor.'; color: #'.$BotRoleColor.'">'.$BotRoleName.'</span> role needs to be <strong>Above</strong> any roles you want to make self-serve.</div>
					<input type="hidden" name="guild" value="'.$Guild['guild_id'].'">
					<button type="submit" class="btn btn-outline-primary mt-3">Save</button>
				</form>';
			}
			
			
		}else{
			throw new Exception('Please execute the "configure" command on the discord server you wanted to configure.');
		}
		
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