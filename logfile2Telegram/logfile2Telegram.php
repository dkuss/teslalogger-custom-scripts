<?php
	require_once 'logfile2Telegram.config.php';
	
	$input  = shell_exec('tail -n5000 /etc/teslalogger/nohup.out');
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

	echo nl2br($telegram_message);

	$data = [
	    'text' => $telegram_message,
	    'chat_id' => CHAT_ID
	];
	if (sizeof($outputArray) > 0){
		echo file_get_contents("https://api.telegram.org/". BOT_IDENTIFIER ."/sendMessage?" . http_build_query($data) );
	}
