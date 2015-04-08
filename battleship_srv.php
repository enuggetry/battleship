<?php
//$str = print_r($_POST,true);
$json = $_POST['gamedata'];
$data = json_decode($json);
$player = $data->player;

$opponent = 1;
if ($player==1) $opponent=2;

$cmd = $data->cmd;

$state = array("mode"=>"play");

//echo json_encode(print_r($data,true));
//exit;

switch($cmd) {
	case "clear":
		// return success=1
		break;
	case "setup":
		$tmp = (array)$data;
		$tmp["change"] = array("active"=>0,"x"=>$x,"y"=>$y,"status"=>$status);
		$data = (object)$tmp;
	
		file_put_contents("battleship_player$player.dat",json_encode($data));
		echo '{"success": 1}';
		// return success=1
		break;
		
	case "shoot":

		$x = $data->x;
		$y = $data->y;
		// get data
		$odata = json_decode(file_get_contents("battleship_player$opponent.dat"));
		$doti = coordToNum($x,$y);
		$status = "miss";
		if (isset($odata->dots[$doti]->shipid)) {
			// there is a ship on this dot
			//$shipid = $odata->dots[$doti]->shipid;
			//$ship = getShipIndex($odata,$shipid);
			//$odata->ships[$ship]->count++;
			$status = "hit";
		}
		$odata->change->active = 1;
		$odata->change->x = $x;
		$odata->change->y = $y;
		$odata->change->status = $status;

		// write it back
		file_put_contents("battleship_player$opponent.dat",json_encode($odata));
		
		echo json_encode($odata->change);

		break;
	case "check":
		// return x,y,status=hit|miss,sunk=shipid of myself
		$mydata = json_decode(file_get_contents("battleship_player$player.dat"));

		if ($mydata->change->active==1) {
			echo json_encode($mydata->change);
		
			$mydata->change->active = 0;
			file_put_contents("battleship_player$player.dat",json_encode($mydata));
		}
		else{
			echo '{"status":0}';
		}
		break;
}

// convert x,y to dot reference
function coordToNum($x,$y) {
	if ($y==0) return $x;
	return intval("$y$x");	
}
function getShipIndex(&$data, $shipid) {
	$i = 0;
	foreach($data->ships as $ship) {
		if ($ship->shipid==$shipid) return $i;
		$i++;
	}
}

//echo $json;
?>
