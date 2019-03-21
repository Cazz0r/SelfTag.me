const Discord = require('discord.js');
const client = new Discord.Client();
const mysql  = require('mysql');
const crypto = require('crypto');
const pad = require('pad-number');


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
	dbconn.end();
});


client.on('ready', () => {
  console.log(`Logged in as ${client.user.tag}!`);
});

//https://discordapp.com/oauth2/authorize?client_id=536705900606652417&permissions=268435456&scope=bot

function GenerateSecret(data){
    var data = data + " " + Date.now() + " LANA";
    //console.log(data);
    var secret = crypto.createHash('md5').update(data).digest("hex");
    //console.log(secret);
    return secret;
}
function RemoveData(guildid){
    
    global.dbconn = mysql.createConnection({
		host     : 'localhost',
		user     : 'cam',
		password : 'hello',
		database : 'test'
	});

	dbconn.connect(function(err) {
		if(err){
			console.log('Database connection error');
			dbconn.end();
		}else{
            //2019-03-21 CAM: We want to keep the guild record (for accounting purposes), we may as well keep the rest of the records so that the configuration is never lost in case they come back to selftag.me in the future. If this decision is reversed, these SQLs will need to be adjusted to suit the new schema/foreign keys.
            /*
            var sql = "DELETE FROM selftagme.guild WHERE guild_id = '" + theguild.id + "'";
            dbconn.query(sql, function (err2, result) {
                if(err2){
                    console.log('Database query error: ' + err2);
                }
            });

            var sql = "DELETE FROM selftagme.role WHERE guild_id = '" + theguild.id + "'";
            dbconn.query(sql, function (err2, result) {
                if(err2){
                    console.log('Database query error: ' + err2);
                }
            });

            var sql = "DELETE FROM selftagme.user WHERE guild_id = '" + theguild.id + "'";
            dbconn.query(sql, function (err2, result) {
                if(err2){
                    console.log('Database query error: ' + err2);
                }
            });

            var sql = "DELETE FROM selftagme.queue WHERE guild_id = '" + theguild.id + "'";
            dbconn.query(sql, function (err2, result) {
                if(err2){
                    console.log('Database query error: ' + err2);
                }
                dbconn.end();
            });
            */
        }
	});
}
function Commands(msg){
    //console.log(msg.channel.type + ': ' + msg.author.id);
    if(msg.channel.type === 'text'){ //Other options: dm
    
        if(msg.content === '.me'){
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
                    dbconn.end();
                }else{

                    //Check if the guild has been configured. A record will exist in selftagme.guild.
                    var sql = "SELECT id FROM selftagme.guild WHERE guild_id = '" + msg.guild.id + "'";
                    dbconn.query(sql, function (err, result) {
                        if(err){
                            console.log('Database query error: ' + err);
                            dbconn.end();
                        }else{
                            if(result.length === 0){

                                if(msg.author.id == msg.guild.ownerID){
                                    msg.reply("This server has not yet been configured, please issue the '.configure' command first.");
                                }else{
                                    var ownername = msg.guild.owner.nickname;
                                    if(ownername === '' || ownername === 'null' || ownername === null) ownername = msg.guild.owner.user.username;
                                    msg.reply("This server has not yet been configured by it's owner. Please have " + ownername + " issue the '.configure' command.");
                                }
                                dbconn.end();
                            }else{

                                //Check that the server has self-serve roles. Records will exist in selftagme.role.
                                var sql = "SELECT id FROM selftagme.role WHERE guild_id = '" + result[0].id + "'";
                                dbconn.query(sql, function (err2, result2) {
                                    if(err2){
                                        console.log('Database query error: ' + err2);
                                        dbconn.end();
                                    }else{
                                        if(result2.length === 0){
                                            if(msg.author.id == msg.guild.ownerID){
                                                msg.reply("This server has no self-serve roles configured. Please issue the '.configure' command first.");
                                            }else{
                                                var ownername = msg.guild.owner.nickname;
                                                if(ownername === '' || ownername === 'null' || ownername === null) ownername = msg.guild.owner.user.username;
                                                msg.reply("This server has no self-serve roles configured. Please have " + ownername + " issue the '.configure' command.");
                                            }
                                            dbconn.end();
                                        }else{

                                            //Check if this user has a secret.
                                            var sql = "SELECT * FROM selftagme.user WHERE guild_id = '" + result[0].id + "' AND user_id = '" + msg.author.id + "'";
                                            //console.log(sql);
                                            dbconn.query(sql, function (err3, result3) {
                                                if (err3){
                                                    console.log('Database query error: ' + err3);
                                                    dbconn.end();
                                                }else{
                                                    //console.log("Found: " + result3.length);
                                                    if(result3.length === 0){
                                                        var secret = GenerateSecret(msg.author.id); //Use discord's user id here, it's a snowflake.

                                                        var sql = "INSERT INTO selftagme.user (guild_id, user_id, secret) VALUES('" + result[0].id + "', '" + msg.author.id + "', '" + secret + "')";
                                                        //console.log(sql);
                                                        dbconn.query(sql, function (err4, result4) {
                                                            if(err4){
                                                                console.log('Database query error: ' + err4);
                                                            }else{
                                                                msg.author.send('To tag yourself with roles in **' + msg.guild.name + '** head to <https://selftag.me/user/' + msg.guild.id + '/' + msg.author.id + '/' + secret + '>');
                                                                msg.reply("Check your DM's!");
                                                            }

                                                            dbconn.end();
                                                        });
                                                    }else{
                                                        msg.author.send('To tag yourself with roles in **' + msg.guild.name + '** head to <https://selftag.me/user/' + msg.guild.id + '/' + msg.author.id + '/' + result3[0].secret + '>');
                                                        msg.reply("Check your DM's!");
                                                        dbconn.end();
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
                        dbconn.end();
                    }else{

                        //Check if the guild has been configured. A record will exist in selftagme.guild.
                        var sql = "SELECT * FROM selftagme.guild WHERE guild_id = '" + msg.guild.id + "'";
                        dbconn.query(sql, function (err, result) {
                            if(err){
                                console.log('Database query error: ' + err);
                                dbconn.end();
                            }else{
                                if(result.length === 0){

                                    var secret = GenerateSecret(msg.guild.id); //Use discord's guild id here, it's a snowflake.

                                    var sql = "INSERT INTO selftagme.guild (guild_id, secret) VALUES('" + msg.guild.id + "', '" + secret + "')";
                                    //console.log(sql);
                                    dbconn.query(sql, function (err2, result2) {
                                        if(err2){
                                            console.log('Database query error: ' + err2);
                                        }else{
                                            msg.author.send('Configure **' + msg.guild.name + '** at this address: <https://selftag.me/guild/' + msg.guild.id + '/' + secret + '>');
                                            msg.reply("Check your DM's!");
                                        }
                                        dbconn.end();
                                    });

                                }else{
                                    msg.author.send('Configure **' + msg.guild.name + '** at this address: <https://selftag.me/guild/' + msg.guild.id + '/' + result[0].secret + '>');
                                    msg.reply("Check your DM's!");
                                    dbconn.end();
                                }
                            }
                        });
                    }
                });


            }else{
                //Not the owner.
                var ownername = msg.guild.owner.nickname;
                if(ownername === '' || ownername === 'null' || ownername === null) ownername = msg.guild.owner.user.username;
                msg.reply('Only the owner of this discord server can issue this command, sorry. Please ask ' + ownername + ' to issue this command.');
            }
        }

        if(msg.content === '.help'){
            msg.reply("**Commands:**\n.configure - Allows the server owner to configure the self-serve roles.\n.me - Allows you to select which roles you want");
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
                        RemoveData(guildmessage[1]);

                    }).catch(console.error);
                }
            }else{ 
                msg.reply("You aren't the boss of me!"); 
            }
        }

    }else if(msg.channel.type === 'dm' && msg.author.id !== '536705900606652417'){
        msg.reply("Sorry, I don't reply to DM's.");
    }
}

client.on('message', msg => {
    Commands(msg);
});

client.on('messageUpdate', (omsg, msg) => {
    Commands(msg);
});

client.on('guildCreate', newguild => {
	console.log('Joined Guild: ' + newguild.name + ' (' + newguild.id + ')');
    console.log(newguild);
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
			dbconn.end();
		}else{

			//Check if the guild has been configured. A record will exist in selftagme.guild.
			var sql = "SELECT secret FROM selftagme.guild WHERE guild_id = '" + guild.id + "'";
			dbconn.query(sql, function (err, result) {
				if(err){
					console.log('Database query error: ' + err);
					dbconn.end();
				}else{
					if(result.length === 0){

						//Generate a secret for the guild.
						var data = guild.id + " LANA";
						//console.log(data);

						var secret = crypto.createHash('md5').update(data).digest("hex");
						//console.log(secret);

						var sql = "INSERT INTO selftagme.guild (guild_id, secret) VALUES('" + guild.id + "', '" + secret + "')";
						//console.log(sql);
						dbconn.query(sql, function (err2, result2) {
							if(err2){
								console.log('Database query error: ' + err2);
							}else{
								console.log('PM\'d Owner: ' + guild.owner.user.username);
								guild.owner.send(`I was just added to your Discord server, **` + newguild.name + `**, as you're the owner, you'll need to configure the self-serve roles here: <https://selftag.me/guild/` + guild.id + `/` + secret + '>');
							}
							dbconn.end();
						});

					}else{
						guild.owner.send(`I was just added to your Discord server, **` + newguild.name + `**, as you're the owner, you'll need to configure the self-serve roles here: <https://selftag.me/guild/` + guild.id + `/` + result[0].secret + '>');
						dbconn.end();
					}
				}
			});
		}
	});
});
client.on('guildDelete', theguild => {
	console.log('Guild Deleted: ' + theguild.name + ' (' + theguild.id + ')');
	
	//Remove all the extra bits and bobs.
    RemoveData(theguild.id);
		
});

client.login('NTM2NzA1OTAwNjA2NjUyNDE3.DyamAw.MZOcn9otIklwPFe10V6YsiUC7Ks');
