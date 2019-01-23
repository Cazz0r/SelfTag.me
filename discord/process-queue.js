const Discord = require('discord.js');
const client = new Discord.Client();
const mysql  = require('mysql');
const crypto = require('crypto');

client.on('ready', () => {
  console.log(`Logged in as ${client.user.tag}!`);
});

client.login('NTM2NzA1OTAwNjA2NjUyNDE3.DyamAw.MZOcn9otIklwPFe10V6YsiUC7Ks').then(function(){
	CheckQueue();
});

function CheckQueue(){
	global.dbconn = mysql.createConnection({
    	host     : 'localhost',
	    user     : 'cam',
    	password : 'hello',
	    database : 'test'
	});

	dbconn.connect(function(err){
    	if(err){
        	console.log('Database connection error');
	    }else{

			var sql = "SELECT * FROM test.queue";
            dbconn.query(sql, function (err, result) {
                if(err){
                    console.log('Database query error: ' + err);
                }else{
                    if(result.length === 0){
						
                    }else{
						console.log('Queue: ' + result.length);
						var actions = [];
						result.forEach(function(data, index){
							//console.log('Guild: ' + data.guild_id + ', User: ' + data.user_id + ', Role: ' + data.role_id + ', ' + data.direction);
							
							//Ensure we actually want to action these tags.
							var guild = client.guilds.get(data.guild_id);
							var member = guild.members.get(data.user_id);
							//console.log(member.roles);
							if(data.direction == 'Insert'){
								//Make sure the tag doesn't already exist.
								if(member.roles.get(data.role_id)){
									//console.log('Already has: ' + data.role_id);
									
									var sql = "DELETE FROM test.queue WHERE guild_id = '" + data.guild_id + "' AND user_id = '" + data.user_id + "' AND role_id = '" + data.role_id + "'";
									dbconn.query(sql, function (err2, result2) {
										if(err2){
											console.log('Database query error: ' + err2);
										}
									});
									
									return;
								}
							}else{
								//Make sure the tag does exist.
								if(!member.roles.get(data.role_id)){
									//console.log('Already gone: ' + data.role_id);
									
									var sql = "DELETE FROM test.queue WHERE guild_id = '" + data.guild_id + "' AND user_id = '" + data.user_id + "' AND role_id = '" + data.role_id + "'";
									dbconn.query(sql, function (err2, result2) {
										if(err2){
											console.log('Database query error: ' + err2);
										}
									});
									
									return;
								}
							}
							
							var key = data.guild_id + ' ' + data.user_id + ' ' + data.direction;
							if(actions.hasOwnProperty(key)){
								actions[key]['roles'].push(data.role_id);
							}else{
								actions[key] = { 'guild': data.guild_id, 'user': data.user_id, 'direction': data.direction, 'roles': [ data.role_id ] };	
							}
						});
						
						//console.log(actions);
						console.log('Performing Actions: ' + Object.keys(actions).length);
						Object.keys(actions).forEach(function(element, key, data){
							//console.log(element);
							//console.log(actions[element]);
							
							var guild = client.guilds.get(actions[element].guild);
							var member = guild.members.get(actions[element].user);		
							if(actions[element].direction == 'Insert'){
								member.addRoles(actions[element].roles, 'Self Serve').then(function(){
									console.log('Roles Insert: "' + member.user.username + '" in ' + guild.name);	
								}).catch(function(caught){
									console.log(caught);
								});
							}else{
								member.removeRoles(actions[element].roles, 'Self Serve').then(function(){
									console.log('Roles Remove: "' + member.user.username + '" in ' + guild.name);	
								}).catch(function(caught){
									console.log(caught);
								});
							}
							
							Object.keys(actions[element].roles).forEach(function(index, tmp, role){
								//Remove the item from the queue.
								var sql = "DELETE FROM test.queue WHERE guild_id = '" + actions[element].guild + "' AND user_id = '" + actions[element].user + "' AND role_id = '" + actions[element].roles[index] + "'";
								dbconn.query(sql, function (err2, result2) {
									if(err2){
										console.log('Database query error: ' + err2);
									}
								});
							});
						});
                    }
				}
				dbconn.end();
            });
        }
	});
   
	setTimeout(CheckQueue, 3000);
}