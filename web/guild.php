<?php

include 'restcord/vendor/autoload.php';
use RestCord\DiscordClient;

function SetDefaultsForCategories($Categories){
	
	if(count($Categories['roles'])>0) foreach($Categories['roles'] as $RoleID => $Category){
		if(!array_key_exists($RoleID, $Categories['roles'])) $Categories['roles'][$RoleID] = '';	
	}
	
	if(count($Categories['categories'])>0) foreach($Categories['categories'] as $Category => $Data){
		foreach(array(
			'Type' => 'Multi', 
			'PreReq' => '',
			'Roles' => array()
		) as $Key => $Default){
			if(!array_key_exists($Key, $Data)) $Categories['categories'][$Category][$Key] = $Default;	
		}		
	}
	return $Categories;
}

//Check if we need to handle an ajax request.
if(!empty($_POST)){
	try{
		if(!isset($_POST['action'])) throw new Exception('No action defined.');
		
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
		if($rs->num_rows == 0) throw new Exception('Please execute the "configure" command on the discord server you wanted to configure.');
		$Guild = $rs->fetch_array(MYSQLI_ASSOC);
		if($Guild['secret'] != $_GET['2']) throw new Exception('Please execute the "configure" command on the discord server you wanted to configure.');
		$Guild['categories'] = $Guild['categories']!='' ? unserialize($Guild['categories']) : array('roles' => array(), 'categories' => array());
		$Guild['categories'] = SetDefaultsForCategories($Guild['categories']);
		
		//Get the guild details from Discord.
		$discord = new DiscordClient(['token' => 'NTM2NzA1OTAwNjA2NjUyNDE3.DyamAw.MZOcn9otIklwPFe10V6YsiUC7Ks']); // Token is required
		$GuildData = $discord->guild->getGuild(['guild.id' => intval($Guild['guild_id'])]);

		
		//Begin the actual ajax processing.
		switch(strtolower($_POST['action'])){
			case 'insert-category':
				if(true){
					$Return = array(
						'title' => 'Category Maintenance', 
						'body' => 'Hello World', 
						'footer' => '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>',
						'size' => 'modal-lg',
						'categories' => array(),
						'closable' => true
					);
					
					if(array_key_exists($_POST['name'], $Guild['categories']['categories'])) throw new Exception('Category name already exists.');
					if(trim($_POST['name'])=='') throw new Exception('Category name cannot be blank.');
					
					//Add the new category.
					$Guild['categories']['categories'][$_POST['name']] = array('Type' => 'Multi', 'PreReq' => '');
					//Serialize for saving.
					$Data = serialize($Guild['categories']);
					
					$sql = "UPDATE test.guild SET categories = ? WHERE guild_id = ?";
					if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
					if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
					if(!$stmt->bind_param("ss", $Data, $Guild['guild_id'])) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					
					$Return['body'] = 'New Category Added.';
					$Return['footer'] = '<button class="btn btn-outline-primary mr-auto Category-Maintenance" type="button"><i class="fas fa-cogs"></i> Back</button> <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
					
					$Return['categories'] = $Guild['categories']['categories'];
				}
				break;
			case 'remove-category':
				if(true){
					$Return = array(
						'title' => 'Category Maintenance', 
						'body' => 'Hello World', 
						'footer' => '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>',
						'size' => 'modal-lg',
						'categories' => array(),
						'closable' => true
					);
					
					//Remove the category.
					if(array_key_exists($_POST['name'], $Guild['categories']['categories'])) unset($Guild['categories']['categories'][$_POST['name']]);
					//Uncategorize any roles using the old category.
					foreach($Guild['categories']['roles'] as $RoleID => $Category) if($Category==$_POST['name']) unset($Guild['categories']['roles'][$RoleID]);
					//Serialize for saving.
					$Data = serialize($Guild['categories']);
					
					$sql = "UPDATE test.guild SET categories = ? WHERE guild_id = ?";
					if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
					if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
					if(!$stmt->bind_param("ss", $Data, $Guild['guild_id'])) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					
					$Return['body'] = 'Category Removed.';
					$Return['footer'] = '<button class="btn btn-outline-primary mr-auto Category-Maintenance" type="button"><i class="fas fa-cogs"></i> Back</button> <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
					
					$Return['categories'] = $Guild['categories']['categories'];
				}
				break;
			case 'move-category':
				if(true){
					$Return = array(
						'title' => 'Category Maintenance', 
						'body' => 'Hello World', 
						'footer' => '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>',
						'size' => 'modal-lg',
						'categories' => array(),
						'closable' => true
					);
					
					if($_POST['direction']=='up'){
						//Promote the category.	
						$Rebuild = array();
						$Holder = array();
						foreach($Guild['categories']['categories'] as $Name => $Settings){
							if($Name == $_POST['name'] && count($Rebuild)>0){
								end($Rebuild);
								$LastKey = key($Rebuild);
								$Holder = $Rebuild[$LastKey];
								unset($Rebuild[$LastKey]);
								$Rebuild[$Name] = $Settings;
								$Rebuild[$LastKey] = $Holder;
								continue;
							}
							
							$Rebuild[$Name] = $Settings;
						}
					}else{
						//Demote the category.
						$Rebuild = array();
						$Found = false;
						$Holder = array();
						foreach($Guild['categories']['categories'] as $Name => $Settings){
							if($Name == $_POST['name']){
								$Found = true;
								$Holder = $Settings;
								continue;
							}
							
							$Rebuild[$Name] = $Settings;
							
							if($Found){
								$Rebuild[$_POST['name']] = $Holder;
								$Found = false;
							}
						}
						if($Found){
							$Rebuild[$_POST['name']] = $Holder;
						}
					}
					
					$Guild['categories']['categories'] = $Rebuild;
					$Data = serialize($Guild['categories']);
					
					$sql = "UPDATE test.guild SET categories = ? WHERE guild_id = ?";
					if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
					if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
					if(!$stmt->bind_param("ss", $Data, $Guild['guild_id'])) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					
					$Return['body'] = 'Category Moved.';
					$Return['footer'] = '<button class="btn btn-outline-primary mr-auto Category-Maintenance" type="button"><i class="fas fa-cogs"></i> Back</button> <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
					
					$Return['categories'] = $Guild['categories']['categories'];
				}
				break;
			case 'categories':
				if(true){
					$Return = array(
						'title' => 'Category Maintenance', 
						'body' => '<pre>'.print_r($Guild, true).'</pre>', 
						'footer' => '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>',
						'size' => 'modal-lg',
						'categories' => array(),
						'closable' => true
					);
					
					$Return['body'] .= '
					<div class="row">
						<div class="col">
							<form id="category-form">
								<table class="table table-sm">
									<thead>
										<tr>
											<th>Category Name</th>
											<th>Type <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" data-html="true" title="<strong>Multi</strong>: Allows a user to select multiple roles from this category.<br><strong>Singular</strong>: A user can only select a single role from this category."></i></th>
											<th>Prerequisite Role/Category <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" data-html="true" title="Restrict this category to those who already have a role (or a role from another category)."></i></th>
											<th class="text-right">Actions</th>
										</tr>
									</thead>
									<tbody>';
					if(count($Guild['categories']['categories'])==0){
						$Return['body'] .= '
										<tr>
											<td colspan="4" class="text-center">No categories configured.</td>
										</tr>';
					}else{
						$x = 0;
						foreach($Guild['categories']['categories'] as $Name => $Data){
							$x++;
							$Return['body'] .= '
										<tr>
											<td>
												<input type="text" class="form-control" name="category[]" value="'.$Name.'">
												<input type="hidden" name="old-category[]" value="'.$Name.'">
											</td>
											<td>
												<select class="form-control" name="type[]">
													<option value="Multi"'.($Data['Type']=='Multi' ? ' selected="selected"' : '').'>Multi</option>
													<option value="Singular"'.($Data['Type']=='Singular' ? ' selected="selected"' : '').'>Singular</option>
												</select>
											</td>
											<td>
												<select class="form-control" name="prereq[]">
													<option value=""'.($Data['PreReq']=='' ? ' selected="selected"' : '').'>No prerequisite</option>';
							$Roles = array();
							foreach($GuildData->roles as $Role){
								if($Role->name == '@everyone') continue;
								if($Role->managed=='1') continue;
								
								$Roles[$Role->id] = array('Position' => $Role->position, 'Name' => $Role->name, 'Color' => str_pad(dechex($Role->color), 6, "0", STR_PAD_LEFT));
							}
							if(!function_exists('RoleSortByPosition')){
								function RoleSortByPosition($a, $b) {
									return $a['Position']==$b['Position'] ? 0 : ($a['Position'] < $b['Position'] ? 1 : -1);
								}
							}
							uasort($Roles, 'RoleSortByPosition');
							
							if(count($Guild['categories']['categories'])>0){
								$Return['body'] .= '<optgroup label="Categories">';
								foreach($Guild['categories']['categories'] as $Category => $Settings){
									$Return['body'] .='
														<option value="'.$Category.'"'.($Data['PreReq']==$Category ? ' selected="selected"' : '').'>'.$Category.'</option>';
									if(count($Settings['Roles'])>0) foreach($Settings['Roles'] as $RoleID){
										$Return['body'] .= '
														<option value="'.$RoleID.'"'.($Data['PreReq']==$RoleID ? ' selected="selected"' : '').' style="color: #'.$Roles[$RoleID]['Color'].'">- '.$Roles[$RoleID]['Name'].'</option>';
										//if(array_key_exists($RoleID, $Roles)) unset($Roles[$RoleID]);
									}
								}
								$Return['body'] .= '</optgroup>';
							}
							if(count($Roles)>0){
								$Return['body'] .= '<optgroup label="All Roles">';
								foreach($Roles as $RoleID => $Data){
									$Return['body'] .= '
													<option value="'.$RoleID.'"'.($Data['PreReq']==$RoleID ? ' selected="selected"' : '').' style="color: #'.$Data['Color'].'">'.$Data['Name'].'</option>';
								}
								$Return['body'] .= '</optgroup>';
							}
							
							
							
							$Return['body'] .= '
												</select>
												
											</td>
											<td class="text-right">
												<div class="btn-group" role="group" aria-label="Basic example">
													<button type="button" class="btn btn-outline-secondary move-category" data-dir="up" data-name="'.$Name.'"'.($x==1 ? ' disabled':'').'><i class="fas fa-arrow-up"></i></button>
													<button type="button" class="btn btn-outline-secondary move-category" data-dir="down" data-name="'.$Name.'"'.($x==count($Guild['categories']['categories']) ? ' disabled':'').'><i class="fas fa-arrow-down"></i></button>
													<button type="button" class="btn btn-outline-secondary text-danger remove-category" data-name="'.$Name.'"><i class="fas fa-trash"></i></button>
												</div>										
											</td>
										</tr>';
						}
					}
					$Return['body'] .='
									</tbody>
								</table>
							</form>
						</div>
					</div>
					<div class="row mt-3">
						<div class="col">
							<form id="insert-category-form">
								<div class="input-group mb-3">
									<div class="input-group-prepend">
										<span class="input-group-text">Add New Category:</span>
									</div>
									<input type="text" class="form-control" id="insert-category-input" placeholder="New Category Name" aria-label="New Category Name">
									<div class="input-group-append">
										<button class="btn btn-outline-secondary" type="submit" id="insert-category">Go</button>
									</div>
								</div>
							</form>
						</div>
					</div>';
					$Return['footer'] = '<button class="btn btn-outline-primary mr-auto save-categories" type="button"><i class="fas fa-save"></i> Save</button> <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
					$Return['categories'] = $Guild['categories']['categories'];
					
				}
				break;
			case 'save-categories':
				if(true){
					$Return = array(
						'title' => 'Category Changes', 
						'body' => '', 
						'footer' => '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>',
						'size' => 'modal-lg',
						'categories' => array(),
						'closable' => true
					);
					
					$Build = array('categories' => array(), 'roles' => array());
					$x = 0;
					foreach($_POST['category'] as $Name){
						$Build['categories'][$Name] = array();
						
						//Replace any old-name associations
						if($Name != $_POST['old-category'][$x]){
							foreach($Guild['categories']['roles'] as $RoleID => $Category){
								if($Category == $_POST['old-category'][$x]){
									$Guild['categories']['roles'][$RoleID] = $Name;
								}
							}
						}
						
						$x++;
					}
					$Build = SetDefaultsForCategories($Build);
					unset($Build['roles']);
					
					$x = 0;
					foreach($Build['categories'] as $Name => $Settings){
						$Build['categories'][$Name]['Type'] = $_POST['type'][$x];
						$Build['categories'][$Name]['PreReq'] = $_POST['prereq'][$x];
						$x++;
					}
					
					$Guild['categories']['categories'] = $Build['categories'];
					//$Return['body'] .= 'After: <pre>'.print_r($Guild['categories'], true).'</pre>';
					$Data = serialize($Guild['categories']);
					
					$sql = "UPDATE test.guild SET categories = ? WHERE guild_id = ?";
					if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
					if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
					if(!$stmt->bind_param("ss", $Data, $Guild['guild_id'])) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					
					$Return['body'] = 'Changes Saved.';
					$Return['footer'] = '<button class="btn btn-outline-primary mr-auto Category-Maintenance" type="button"><i class="fas fa-cogs"></i> Back</button> <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
					
					$Return['categories'] = $Guild['categories']['categories'];
				}
				break;
			case 'save-guild':				
				if(true){
					$Return = array(
						'title' => 'Guild Changes', 
						'body' => '', 
						'footer' => '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>',
						'size' => '',
						'categories' => array(),
						'closable' => true
					);
					
					//Delete existing roles.
					$sql = "DELETE FROM test.role WHERE guild_id = ?";
					if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
					if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
					if(!$stmt->bind_param("s", $Guild['guild_id'])) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					
					//Build new the $Guild['categories']['roles'] array, and add any switched on into the database.
					$Build = array();
					$RebuildCategories = array();
					
					foreach($_POST['role'] as $RoleID){
						//$Return['body'] .= 'Checking: '.$RoleID.'<br>';
						if(isset($_POST['switch-'.$RoleID])){
							//Role is on.
							$sql = "INSERT INTO test.role (guild_id, role_id) VALUES(?, ?)";
							if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
							if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
							if(!$stmt->bind_param("ss", $Guild['guild_id'], $RoleID)) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
							if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
						}
						if($_POST['category-'.$RoleID]!=''){
							//$Return['body'] .= 'Has Category: '.$_POST['category-'.$RoleID].'<br>';
							$Build[$RoleID] = $_POST['category-'.$RoleID];
							if(!array_key_exists($_POST['category-'.$RoleID], $RebuildCategories)){
								$RebuildCategories[$_POST['category-'.$RoleID]] = $Guild['categories']['categories'][$_POST['category-'.$RoleID]];
								$RebuildCategories[$_POST['category-'.$RoleID]]['Roles'] = array();																			
							}
							$RebuildCategories[$_POST['category-'.$RoleID]]['Roles'][] = $RoleID;
						}
					}
					$Guild['categories'] = array('categories' => $RebuildCategories, 'roles' => $Build);
					$Data = serialize($Guild['categories']);
					
					$sql = "UPDATE test.guild SET categories = ? WHERE guild_id = ?";
					if(!$stmt = $GLOBALS['mysqli']->stmt_init()) throw new Exception("Init Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".$sql."]");
					if(!$stmt = $GLOBALS['mysqli']->prepare($sql)) throw new Exception("Prepare Error: ".$stmt->error." (".$stmt->errno.") [".__LINE__."]");
					if(!$stmt->bind_param("ss", $Data, $Guild['guild_id'])) throw new Exception("Bind Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					if(!$stmt->execute()) throw new Exception("Execute Error: ".$GLOBALS['mysqli']->error." (".$GLOBALS['mysqli']->errno.") [".__LINE__."]");
					
					$Return['body'] = 'Changes saved.';
					$Return['footer'] = '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
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
		
		if(isset($_POST['action'])) if(strtolower($_POST['action'])!='categories'){
			$Return['footer'] = '<button class="btn btn-outline-primary mr-auto Category-Maintenance" type="button"><i class="fas fa-cogs"></i> Back</button> <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';			
		}
	}
	echo json_encode($Return);
	exit;
}

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
	if($rs->num_rows == 0) throw new Exception('Please execute the "configure" command on the discord server you wanted to configure.');
	$Guild = $rs->fetch_array(MYSQLI_ASSOC);
	if($Guild['secret'] != $_GET['2']) throw new Exception('Please execute the "configure" command on the discord server you wanted to configure.');
	$Guild['categories'] = $Guild['categories']!='' ? unserialize($Guild['categories']) : array('roles' => array(), 'categories' => array());
	//We are authenticated.

	if(!empty($_POST)){
		//echo '<pre>'.print_r($_POST, true).'</pre>';

		

		//Add the roles we just saved.
		if(isset($_POST['role'])) if(count($_POST['role'])>0){
			foreach($_POST['role'] as $Role){
				
			}
		}

		echo '<div class="alert alert-success mt-3" role="alert">Your changes have been saved successfully.</div>';
	}

	//Get the guild details from Discord.
	$discord = new DiscordClient(['token' => 'NTM2NzA1OTAwNjA2NjUyNDE3.DyamAw.MZOcn9otIklwPFe10V6YsiUC7Ks']); // Token is required
	$GuildData = $discord->guild->getGuild(['guild.id' => intval($Guild['guild_id'])]);
	echo '<pre>'.print_r($GuildData, true).'</pre>';
	 
	$Bot = $discord->guild->getGuildMember(['guild.id' => intval($Guild['guild_id']), 'user.id' => intval(536705900606652417)]);
	//echo '<pre>'.print_r($Bot, true).'</pre>';

	//Find the bot role.
	$BotRole = 0;
	$BotRoleName = 'SelfTag.me';
	$BotRolePosition = 0;
	$BotRoleColor = 0;
	foreach($GuildData->roles as $Role){
		if($Role->managed != '1') continue;
		echo 'Managed Role: '.$Role->name.'<br>';
		if(in_array($Role->id, $Bot->roles)){
			echo '+ Bot Has it.<br>';
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

	echo '
	<div class="row mb-3">
		<div class="col">
			<h1 class="mb-0">Configure Discord Server: '.$GuildData->name.'</h1>
			On this page you\'ll select which roles are available for self-serve, as well configure any categorization.
		</div>
	</div>';


    
    if($BotRole==0){
		echo '
		<div class="alert alert-danger" role="alert">
			<i class="fas fa-exclamation-triangle"></i> SelfTag.me Role Missing!
			<div><i class="fas fa-info-circle"></i> Please use the invite screen again as well as leave "Manage Roles" checked, here: <a href="https://invite.selftag.me">https://invite.selftag.me</a></div>
		</div>';
    }elseif(count($Roles)==0){
		echo '
		<div class="alert alert-danger" role="alert">
			<i class="fas fa-exclamation-triangle"></i> No eligible roles setup on your Discord server!
			<div><i class="fas fa-info-circle"></i> Remember that the <span style="border-color: #'.$BotRoleColor.'; color: #'.$BotRoleColor.'">'.$BotRoleName.'</span> role needs to be <strong>Above</strong> any roles you want to make self-serve.</div>
		</div>';	
	}else{

		if(count($Guild['categories']['categories'])==0){
			echo '
		<div class="alert alert-info" role="alert">
			<i class="fas fa-info-circle"></i> No categories configured, how about we start there? <button class="btn btn-sm btn-outline-primary Category-Maintenance" type="button"><i class="fas fa-cogs"></i> Category Maintenance</button>
		</div>';	
		}

		echo '
		<div class="row">
			<div class="col">
				<form method="post" id="guild-form">
					<div class="category-list">
						<table class="table table-sm">
							<thead>
								<tr>
									<th>Role</th>
									<th>Self-Servable</th>
									<th>Category <button class="btn btn-sm btn-outline-primary Category-Maintenance" type="button"><i class="fas fa-cogs"></i></button></th>
								</tr>
							</thead>
							<tbody>';
			if(count($Roles)>0) foreach($Roles as $RoleID => $Role){

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

				if(true){ //Slider
					echo '
								<tr>
									<td>
										<span class="tag" for="Role-'.$RoleID.'" style="border-color: #'.$Role['Color'].';color: #'.$Role['Color'].'">'.$Role['Name'].'</span>
										<input type="hidden" name="role[]" value="'.$RoleID.'">
									</td>
									<td>
										<div class="custom-control custom-switch" style="display:inline-block">
											<input type="checkbox" class="custom-control-input" id="Role-'.$RoleID.'" name="switch-'.$RoleID.'" value="'.$RoleID.'"'.($Selected ? ' checked="checked"' : '').'>
											<label class="custom-control-label" for="Role-'.$RoleID.'"></label>
										</div>
									</td>
									<td>';
									if(count($Guild['categories']['categories'])>0){
										echo '
										<select class="form-control" name="category-'.$RoleID.'">
											<option value="">No Category</option>';
										foreach($Guild['categories']['categories'] as $Name => $Settings){
											echo '
											<option value="'.$Name.'"'.(array_key_exists($RoleID, $Guild['categories']['roles']) ? ($Guild['categories']['roles'][$RoleID]==$Name ? ' selected="selected"' : '') : '').'>'.$Name.'</option>';
										}
										echo '
										</select>';
									}else{
										echo '<i>No Categories Defined</i>';
									}
					echo '
									</td>
								</tr>';
				}
			}
			echo '
							</tbody>
						</table>
					</div>
					<div class="mt-3"><i class="fas fa-info-circle"></i> Roles Missing? Remember that the <span style="border-color: #'.$BotRoleColor.'; color: #'.$BotRoleColor.'">'.$BotRoleName.'</span> role needs to be <strong>Above</strong> any roles you want to make self-serve.</div>
					<input type="hidden" name="guild" id="guild" value="'.$Guild['guild_id'].'">
					<input type="hidden" name="secret" id="secret" value="'.$Guild['secret'].'">
					<button type="button" class="btn btn-lg btn-primary mt-3 Guild-Save">Save</button>
				</form>
			</div>
		</div>';
	}
			
			
		
		
	

}catch(Exception $e){
	echo '<div class="alert alert-danger" role="alert" data-line="'.$e->getLine().'">'.$e->getMessage().'</div>';
}

require_once "includes/footer.php";
?>