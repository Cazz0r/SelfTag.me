const Discord = require('discord.js');
const client = new Discord.Client();
const mysql  = require('mysql');
const crypto = require('crypto');


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
    	console.log('Database connection successful');
	}
});

client.on('ready', () => {
  console.log(`Logged in as ${client.user.tag}!`);
});

client.on('message', msg => {
    if(msg.content === '.tagme'){
        console.log('Tag Request ' + msg.author.id + ' (' + msg.author.username + ') in ' + msg.guild.id + ' (' + msg.guild.name + ')');

		global.dbconn = mysql.createConnection({
			host     : 'localhost',
			user     : 'cam',
			password : 'hello',
			database : 'test'
		});


		dbconn.connect(function(err) {
			if(err){
				console.log('Database connection error');
			}else{

				//Check if the guild has been configured. A record will exist in test.guild.
				var sql = "SELECT * FROM test.guild WHERE guild_id = '" + msg.guild.id + "'";
				dbconn.query(sql, function (err, result) {
					if(err){
						console.log('Database query error: ' + err);
					}else{
						if(result.length === 0){
							var ownername = msg.guild.owner.nickname;
							if(ownername === '' || ownername === 'null' || ownername === null) ownername = msg.guild.owner.user.username;
							msg.reply("This server has not yet been configured by it's owner. Please have " + ownername + " issue the 'configure' command.");
						}else{

							//Check that the server has self-serve roles. Records will exist in test.role.
							var sql = "SELECT * FROM test.role WHERE guild_id = '" + msg.guild.id + "'";
							dbconn.query(sql, function (err2, result2) {
			                    if(err2){
                			        console.log('Database query error: ' + err2);
			                    }else{
            			            if(result2.length === 0){
										var ownername = msg.guild.owner.nickname;
										if(ownername === '' || ownername === 'null' || ownername === null) ownername = msg.guild.owner.user.username;
                        			    msg.reply("This server has no self-serve roles configured. Please have " + ownername + " issue the 'configure' command.");
			                        }else{

										//Check if this user has a secret.
										var sql = "SELECT * FROM test.user WHERE guild_id = '" + msg.guild.id + "' AND user_id = '" + msg.author.id + "'";
										//console.log(sql);
			  							dbconn.query(sql, function (err3, result3) {
			    							if (err3){
												console.log('Database query error: ' + err3);
											}else{
												//console.log("Found: " + result3.length);
												if(result3.length === 0){
													//No secret, generate it.
													var data = msg.guild.id + " " + msg.author.id + " LANA";
													//console.log(data);
						
													var secret = crypto.createHash('md5').update(data).digest("hex");
													//console.log(secret);

													var sql = "INSERT INTO test.user (guild_id, user_id, secret) VALUES('" + msg.guild.id + "', '" + msg.author.id + "', '" + secret + "')";
													//console.log(sql);
													dbconn.query(sql, function (err4, result4) {
														if(err4){
															console.log('Database query error: ' + err4);
														}else{
															msg.author.send('To tag yourself with roles on **' + msg.guild.name + '** head to http://157.230.151.242/user/' + msg.guild.id + '/' + msg.author.id + '/' + secret);
															msg.reply("You have been PM'd");
														}
													});
												}else{
													msg.author.send('To tag yourself with roles on **' + msg.guild.name + '** head to http://157.230.151.242/user/' + msg.guild.id + '/' + msg.author.id + '/' + result3[0].secret);
													msg.reply("You have been PM'd");
												}
											}	
							  			});
									}
								}
							});

						}
					}
				});

			}
			
			//dbconn.end();
		});

    }

	if(msg.content === '.configure'){
		console.log('Configure Request ' + msg.author.id + ' (' + msg.author.username + ') in ' + msg.guild.id + ' (' + msg.guild.name + ')');
		if(msg.guild.ownerID === msg.author.id || msg.author.id == 125756591382200321){
			//Check if we know this guild

			global.dbconn = mysql.createConnection({
            	host     : 'localhost',
	            user     : 'cam',
    	        password : 'hello',
        	    database : 'test'
	        });


	        dbconn.connect(function(err) {
    	        if(err){
        	        console.log('Database connection error');
    	        }else{
	
        	        //Check if the guild has been configured. A record will exist in test.guild.
            	    var sql = "SELECT * FROM test.guild WHERE guild_id = '" + msg.guild.id + "'";
                	dbconn.query(sql, function (err, result) {
                    	if(err){
	                        console.log('Database query error: ' + err);
    	                }else{
        	                if(result.length === 0){

								//Generate a secret for the guild.
								var data = msg.guild.id + " LANA";
					            console.log(data);
            
								var secret = crypto.createHash('md5').update(data).digest("hex");
					            console.log(secret);
							
								var sql = "INSERT INTO test.guild (guild_id, secret) VALUES('" + msg.guild.id + "', '" + secret + "')";
                                console.log(sql);
                                dbconn.query(sql, function (err2, result2) {
        	                        if(err2){
    	                            	console.log('Database query error: ' + err2);
	                                }else{
										msg.author.send('Configure **' + msg.guild.name + '** at this address: http://157.230.151.242/guild/' + msg.guild.id + '/' + secret);
		                              	msg.reply("You have been PM'd");
                                	}
                                });

							}else{
								msg.author.send('Configure **' + msg.guild.name + '** at this address: http://157.230.151.242/guild/' + msg.guild.id + '/' + result[0].secret);
                                msg.reply("You have been PM'd");
							}
						}
					});
				}
				
				//dbconn.end();
			});
			

		}else{
			//Not the owner.
			var ownername = msg.guild.owner.nickname;
			if(ownername === '' || ownername === 'null' || ownername === null) ownername = msg.guild.owner.user.username;
			msg.reply('Only the owner of this discord server can issue this command, sorry. Please ask ' + ownername + ' to issue this command.');
		}
	}

	if(msg.content === '.help'){
		msg.reply("**Commands:**\n.configure - Allows the server owner to configure the self-serve roles\n.tagme - Allows you to select which roles you want");
	}	
	
	if(msg.content.includes('!leave ', 0)){
		if(msg.author.id == 125756591382200321){
			var guildmessage = msg.content.split(" ");
			//console.log(guildmessage);
			const guild = client.guilds.get(guildmessage[1]);
			//console.log(guild);
			if (!guild){
				msg.reply('Not in guild: ' + guildmessage[1]);
			}else{
				msg.reply('Ok, leaving: ' + guildmessage[1]);
				guild.leave().then(function(g){
					msg.reply(`Left guild ${g} (` + guildmessage[1] + `)`);
					
					//Remove all the extra bits and bobs.
					global.dbconn = mysql.createConnection({
						host     : 'localhost',
						user     : 'cam',
						password : 'hello',
						database : 'test'
					});

					dbconn.connect(function(err) {
						if(err){
							console.log('Database connection error');
						}else{

							var sql = "DELETE FROM test.guild WHERE guild_id = '" + guildmessage[1] + "'";
							dbconn.query(sql, function (err2, result) {
								if(err2){
									console.log('Database query error: ' + err2);
								}
							});

							var sql = "DELETE FROM test.role WHERE guild_id = '" + guildmessage[1] + "'";
							dbconn.query(sql, function (err2, result) {
								if(err2){
									console.log('Database query error: ' + err2);
								}
							});

							var sql = "DELETE FROM test.user WHERE guild_id = '" + guildmessage[1] + "'";
							dbconn.query(sql, function (err2, result) {
								if(err2){
									console.log('Database query error: ' + err2);
								}
							});

							var sql = "DELETE FROM test.queue WHERE guild_id = '" + guildmessage[1] + "'";
							dbconn.query(sql, function (err2, result) {
								if(err2){
									console.log('Database query error: ' + err2);
								}
							});
						}
						
						//dbconn.end();
					});
					
				}).catch(console.error);
			}
		}else{ 
			msg.reply("You aren't the boss of me!"); 
		}
	}
});

client.on('guildCreate', newguild => {
	console.log('Joined Guild: ' + newguild.name + ' (' + newguild.id + ')');
	const guild = client.guilds.get(newguild.id);
	
	global.dbconn = mysql.createConnection({
		host     : 'localhost',
		user     : 'cam',
		password : 'hello',
		database : 'test'
	});

	dbconn.connect(function(err) {
		if(err){
			console.log('Database connection error');
		}else{

			//Check if the guild has been configured. A record will exist in test.guild.
			var sql = "SELECT * FROM test.guild WHERE guild_id = '" + guild.id + "'";
			dbconn.query(sql, function (err, result) {
				if(err){
					console.log('Database query error: ' + err);
				}else{
					if(result.length === 0){

						//Generate a secret for the guild.
						var data = guild.id + " LANA";
						//console.log(data);

						var secret = crypto.createHash('md5').update(data).digest("hex");
						//console.log(secret);

						var sql = "INSERT INTO test.guild (guild_id, secret) VALUES('" + guild.id + "', '" + secret + "')";
						//console.log(sql);
						dbconn.query(sql, function (err2, result2) {
							if(err2){
								console.log('Database query error: ' + err2);
							}else{
								console.log('PM\'d Owner: ' + guild.owner.user.username);
								guild.owner.send(`I was just added to your Discord server, **` + newguild.name + `**, as you're the owner, you'll need to configure the self-serve roles here: http://157.230.151.242/guild/` + guild.id + `/` + secret);
							}
						});

					}else{
						guild.owner.send(`I was just added to your Discord server, **` + newguild.name + `**, as you're the owner, you'll need to configure the self-serve roles here: http://157.230.151.242/guild/` + guild.id + `/` + result[0].secret);
					}
				}
			});
		}
		
		//dbconn.end();
	});
	
	
});

client.login('NTM2NzA1OTAwNjA2NjUyNDE3.DyamAw.MZOcn9otIklwPFe10V6YsiUC7Ks');
