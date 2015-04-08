<?php
	$player = 1;
	
	if (isset($_GET['player'])) $player = $_GET['player'];
?>


<html>
<head>
	<title>D3 Battleship - Eric Y</title>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
	<script src="http://code.jquery.com/jquery-1.11.2.min.js" charset="utf-8"></script>
	<script src="http://code.jquery.com/ui/1.11.4/jquery-ui.min.js" charset="utf-8"></script>
	<script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
	<script src="vector.js" charset="utf-8"></script>
	<style>
		button {
			margin-bottom: 10px;
		}
		#buttons {
			margin-top: 100px;
		}
		#radio { margin-bottom: 10px; }
	</style>
</head>
<body>
	<div id="buttons" style="width: 150px; float:left">
		<label id="player">Player</label><br/>
		<!--div id="radio">
		<input type="radio" id="radio1" name="radio"><label for="radio1">1</label>
		<input type="radio" id="radio2" name="radio" checked="checked"><label for="radio2">2</label>
		</div-->
		<button id="donesetup" onclick="start()">Start</button><br/>
		<!--button id="donesetup" onclick="game()">Check</button><br/-->
		<div id="status">Mode: Setup</div>
	</div>
	<script>
		// initialize stuff
		var player = <?php echo $player; ?>;
		var gs = 30;			//grid spacing
		var max_ship_size = 3;	// max ship size default
		var my_ship_count = 0;
		var mode = "setup";		// setup or play
		
		$(function() {
			$( "#player").html("Player: "+player);
			$( "button" ).button();
			$( "#radio" ).buttonset();
			
			$("#status").html("Setting up grids...");
			d3.timer(setup_d3,500);
		});
		
		// done setup button
		function start() {
			$("#radio").html("1");
			$("#donsetup").hide();
			
			// json {"dots":[{},{}...], "ships":[{},{}...]}
			var json_str = package_setup_data();
			send_setup(json_str);
		}
		
		function package_setup_data() {
			pack1 = '{"cmd":"setup","player": '+player+', "dots":[';
			
			var i = 0;
			$("g.my-sea > .dot").each(function(){
				if (i>0) pack1 += ","
				//pack1 += '"'+$(this).attr("id")+'":';
				pack1 += "{";
				pack1 += '"x":'+$(this).attr("xx")+",";
				pack1 += '"y":'+$(this).attr("yy");
				if ($(this).attr("shipid") !== undefined) pack1 += ',"shipid":'+$(this).attr("shipid");
				pack1 += "}"
				i++;
			});
			
			pack1 += "],";
			pack1 += '"ships":[';

			var i = 0;
			$("g.my-sea > .ship").each(function(){
				if (i>0) pack1 += ","
				pack1 += "{";
				pack1 += '"shipid":'+$(this).attr("shipid")+",";
				pack1 += '"x":'+$(this).attr("xx")+",";
				pack1 += '"y":'+$(this).attr("yy")+",";
				pack1 += '"hitcount":'+$(this).attr("hitcount")+",";
				pack1 += '"shipsize":'+$(this).attr("shipsize");
				pack1 += "}"
				i++;
			});
			
			
			pack1 += ']';
			pack1 += "}";
			
			//console.log(pack1);
		}
		
		function send_setup(str) {
			$.ajax({
				type: 'POST',
				url: 'battleship_srv.php',
				data: {gamedata:pack1},
				dataType: "JSON",
				success: function(msg) {
				  //alert(msg);
				  console.log(msg);
				  mode = "game";
				  $("#status").html("Mode: Play<br/>fire some torpedos into His Sea!");
				  
				  setInterval(game, 2000);
				}
			});
		}
		
		// setup d3 canvas, my grid and his grid
		function setup_d3() {
			canvas = d3.select("body")
				.append("svg")
				.attr("width",700)
				.attr("height",400)
				;
				
			// my sea
			my_sea = canvas.append("g")
				.attr("class","my-sea")
				.attr("transform","translate(20,50)")
				;
			// his sea
			his_sea = canvas.append("g")
				.attr("class","his-sea")
				.attr("transform","translate(350,50)")
				;
			my_sea.append("text")
				.attr("text-anchor","top")
				.attr("font-size","1em")
				.attr("transform","translate(0,-20)")
				.text("My Sea");
			his_sea.append("text")
				.attr("text-anchor","top")
				.attr("font-size","1em")
				.attr("transform","translate(0,-20)")
				.text("His Sea");
				

			for(var y=0;y<10;y++) {
				for(var x=0;x<10;x++) {
					my_sea.append("circle")
						.attr("class","dot")
						.attr("cx",x*gs)
						.attr("cy",y*gs)
						.attr("xx",x)
						.attr("yy",y)
						.attr("r",10)
						.attr("fill","lightblue")
						.attr("id","my"+coordToStr(x,y))
						;
					
					his_sea.append("circle")
						.attr("class","dot")
						.attr("cx",x*gs)
						.attr("cy",y*gs)
						.attr("xx",x)
						.attr("yy",y)
						.attr("r",10)
						.attr("fill","lightblue")
						.attr("id","his"+coordToStr(x,y))
						;
				}
			}
			$("#status").html("Mode: Setup<br/>place battleships in My Sea");
			d3.timer(setup_click_handlers,500);
			return true;	// exit timer function
		}
		
		// when game is running this will periodically poll the server
		function game(){
			var str = '{"cmd":"check","player":'+player+'}';
			$.ajax({
				type: 'POST',
				url: 'battleship_srv.php',
				data: {gamedata:str},
				dataType: "JSON",
				success: function(msg) {
				  console.log(msg);
				  var dot = "#my"+coordToStr(msg.x,msg.y);
				  if (msg.status=="hit")
						$(dot).attr("fill","red");
				  if (msg.status=="miss")
						$(dot).attr("fill","blue");
				  
				}
			});
		}
		
		
		// setup click handlers
		function setup_click_handlers() {
			$("g.my-sea > .dot").each(function() {
				$(this).on("click",newship);
			});
			$("g.his-sea > .dot").each(function() {
				$(this).on("click",shoot);
			});
			return true;	/// exit timer
		}
		function coordToStr(x,y) {
			return "" + x + "-" + y;
		}
		function newship() {
			if (mode != "setup") {
				alert("cannot add battle ships - game has started");
				return;
			}
			var x = parseInt($(this).attr("xx"));
			var y = parseInt($(this).attr("yy"));
			
			if (x > (10-max_ship_size)) return;	// don't allow ships if it will not fit the max ship size from right.
			
			console.log("newship: "+x+","+y);
			
			// create a ship that covers max_ship_size spaces
			my_sea.insert("rect",":first-child")
			   .attr("class","ship")
			   .attr("id","my-"+my_ship_count)
			   .attr("shipid",my_ship_count)
			   .attr("x", x*gs)
			   .attr("y", y*gs)
			   .attr("xx", x)
			   .attr("yy", y)
			   .attr("shipsize", max_ship_size)
			   .attr("hitcount", 0)
			   .attr("width", gs*max_ship_size)
			   .attr("height", gs)
			   .attr("fill","none")
			   .attr("stroke","blue")
			   .attr("stroke-width",2)
			   .attr("transform","translate(-15,-15)")
			   ;
			// mark the ship coords within the grid (each dot will reference the ship id)
			for(var i=0;i<max_ship_size;i++) {
				var sel = "#my"+coordToStr(x+i,y);
				//console.log(sel);
				$(sel).attr("shipid",my_ship_count);
			}
			my_ship_count++;
			
		}
		function shoot() {
			if (mode != "game") {
				alert("game has not started yet");
				return;
			}
			var x = parseInt($(this).attr("xx"));
			var y = parseInt($(this).attr("yy"));
			console.log("shoot: "+x+","+y);
			var str = '{"cmd":"shoot","player":'+player+',"x":'+x+',"y":'+y+'}';
			$.ajax({
				type: 'POST',
				url: 'battleship_srv.php',
				data: {gamedata:str},
				dataType: "JSON",
				success: function(msg) {
				  console.log(msg);
				  var dot = "#his"+coordToStr(msg.x,msg.y);
				  if (msg.status=="hit")
						$(dot).attr("fill","red");
				  else
						$(dot).attr("fill","blue");
				}
			});
		}

	</script>
</body>
</html>
