<?php
	require_once 'logfile2Telegram.config.php';
	
	$input  = shell_exec('tail -n10000 /etc/teslalogger/nohup.out');
	if ($input === false) {
        // Handle the error
		echo "error";
    }
    
    //echo "getMet: ";
    //echo file_get_contents("https://api.telegram.org/". BOT_IDENTIFIER ."/getMe");
    //echo "<br>";
    //echo "getUpdates: ";
    //echo file_get_contents("https://api.telegram.org/". BOT_IDENTIFIER ."/getUpdates");

    $inputArray = explode(PHP_EOL, $input);

    $outputArray = [];

    $telegram_message="";

    $now = new DateTime();
    foreach (array_reverse($inputArray) as $line){

    	if (strpos($line, 'eocoding') !== false){
    		continue;
    	}
    	if (strpos($line, ' : offline') !== false){
    		continue;
    	}
    	if (strpos($line, 'TOKEN:') !== false){
    		continue;
    	}
    	if (strpos($line, 'Streamingtoken:') !== false){
    		continue;
		}
		if (strpos($line, 'Waiting for car to go to sleep') !== false){
			$numberPos = strrpos($line, ' ') + 1;
			$number = (int) trim(substr($line, $numberPos));
			if (!in_array($number, [0,20])) {
				continue;
			}
		}

    	//16.08.2019 20:44:46	
    	$dateTimeString = substr ($line ,  0 , 19 ); 
    	$dateTime = new DateTime($dateTimeString);
    	if (empty($dateTime)){
    		echo "dateTime false - " . $line;
    		continue;
    	}

		$diff_in_seconds = date_timestamp_get($now)-date_timestamp_get($dateTime);

        if ($diff_in_seconds > 305){ // Meldungen älter als 5 Minuten und 5 Sekunden irgnorieren -> 305
			break;
		}

		if(!empty(trim($line))){
			$outputArray[] = trim($line) . "\n" ;
		}
	}

	$telegram_message=$now -> format("Y-m-d H:i:s"). "\n";

	foreach (array_reverse($outputArray) as $line){
		$telegram_message = $telegram_message . $line;
	}

    if (strpos($telegram_message, 'StopStreaming') !== false){
		if ($trip = getTripOutput()) {
			$telegram_message = $telegram_message . $trip;
		}
    }

	echo nl2br($telegram_message);

	$data = [
	    'text' => $telegram_message,
	    'chat_id' => CHAT_ID
	];

	if (sizeof($outputArray) > 0){
		echo file_get_contents("https://api.telegram.org/". BOT_IDENTIFIER ."/sendMessage?" . http_build_query($data) );
	}


function getTripOutput(){

    $input = file_get_contents('/etc/teslalogger/current_json.txt');
    if ($input === false) {
       // Handle the error
       // echo 'error reading file';
       return false;
    }

    $input = removeBOM($input);
	$data = json_decode($input);
	
	// trips under 100m are not interesting
	if ($data->trip_distance < 0.1) {
		return false;
	}

    $output = "------------------------\n";
    $output = $output . "Batteriestand: " .$data->battery_level . "%\n";
    $output = $output . "Ideale Reichweite: " .number_format($data->ideal_battery_range_km, 2, "," , "." ) . " km\n";
    $output = $output . "Kilometerstand: " .number_format($data->odometer, 0, "," , "." ) . " km\n";
    $output = $output . "Letzter Trip: \n";
    $output = $output . "Start: " .$data->trip_start . "\n";
    $output = $output . "Dauer: " .number_format($data->trip_duration_sec/60, 0, "," , "." ) . " Minuten\n";
    $output = $output . "Verbrauch: " .number_format($data->trip_kwh, 2, "," , "." ) . " kwh\n";
    $output = $output . "Durchschnittsverbrauch: " .number_format($data->trip_avg_kwh, 2, "," , "." ) . " kwh\n";
    $output = $output . "Entfernung: " .number_format($data->trip_distance, 2, "," , "." ) . " km\n";
    return $output;
}

function removeBOM($data) {
    if (0 === strpos(bin2hex($data), 'efbbbf')) {
        return substr($data, 3);
    }
    return $data;
}