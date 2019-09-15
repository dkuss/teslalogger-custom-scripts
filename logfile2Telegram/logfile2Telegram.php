<?php
$config = require 'logfile2Telegram.config.php';
if ($config['env'] === 'dev') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

$connection = mysqli_connect($config['db_host'], $config['db_user'], $config['db_password']) or die ("Verbindungsversuch fehlgeschlagen: " . mysql_error());
mysqli_select_db($connection, $config['db_name']) or die("Konnte die Datenbank nicht waehlen.");
mysqli_set_charset($connection, 'utf8') or die("Konnte die Datenbank nicht auf UTF-8 setzen.");

$input = shell_exec('tail -n10000 /etc/teslalogger/nohup.out');
if ($input === false) {
    // Handle the error
    echo "error";
}

//echo "getMet: ";
//echo file_get_contents("https://api.telegram.org/". $config['telegram_bot_identifier'] ."/getMe");
//echo "<br>";
//echo "getUpdates: ";
//echo file_get_contents("https://api.telegram.org/". $config['telegram_bot_identifier'] ."/getUpdates");

$inputArray = explode(PHP_EOL, $input);
$outputArray = [];

$telegram_message = "";

$now = new DateTime();
foreach (array_reverse($inputArray) as $line) {

    if (strpos($line, 'eocoding') !== false) {
        continue;
    }
    if (strpos($line, ' : offline') !== false) {
        continue;
    }
    if (strpos($line, 'TOKEN:') !== false) {
        continue;
    }
    if (strpos($line, 'Streamingtoken:') !== false) {
        continue;
    }
    if (strpos($line, 'Waiting for car to go to sleep') !== false) {
        $numberPos = strrpos($line, ' ') + 1;
        $number = (int)trim(substr($line, $numberPos));
        if (!in_array($number, [0, 20])) {
            continue;
        }
    }

    //16.08.2019 20:44:46
    $dateTimeString = substr($line, 0, 19);
    $dateTime = new DateTime($dateTimeString);
    if (empty($dateTime)) {
        echo "dateTime false - " . $line;
        continue;
    }

    $diff_in_seconds = date_timestamp_get($now) - date_timestamp_get($dateTime);

    if ($diff_in_seconds > 305) { // Meldungen älter als 5 Minuten und 5 Sekunden irgnorieren -> 305
        break;
    }

    if (!empty(trim($line))) {
        $outputArray[$dateTime->format("Y-m-d H:i:s")] = trim($line) . "\n";
    }
}

$telegram_message = $now->format("Y-m-d H:i:s") . "\n";

$tripMessages = [];
foreach (array_reverse($outputArray, true) as $time => $line) {
    $telegram_message .= $line;
    if (strpos($line, 'StopStreaming') !== false) {
        if ($trip = getTripOutput($time, $connection)) {
            $tripMessages[] = $trip;
        }
    }
}
if (!empty($tripMessages)) {
    $telegram_message .= implode('', $tripMessages);
}

echo nl2br($telegram_message);

$data = [
    'text' => $telegram_message,
    'chat_id' => $config['telegram_chat_id']
];

if (sizeof($outputArray) > 0) {
    echo file_get_contents("https://api.telegram.org/" . $config['telegram_bot_identifier'] . "/sendMessage?" . http_build_query($data));
}


function getTripOutput($time = null, $connection = null)
{
    if ($time !== null) {
        $input = getTripJson($time, $connection);
        $source = 'trip_database';
    } else {
        $input = getCurrentJson();
        $source = 'current_json';
    }
    if ($input === false) {
        // Handle the error
        // echo 'error reading file';
        return false;
    }

    $input = removeBOM($input);
    $data = json_decode($input);

    // short trips are not interesting
    $trip_distance = $source === 'current_json' ? $data->trip_distance : $data->km_diff;
    if ($trip_distance < 0.5) {
        return false;
    }

    $output = '';
    if ($source === 'current_json') {

        $output = "------------------------\n";
        $output .= "Batteriestand: " . $data->battery_level . "%\n";
        $output .= "Ideale Reichweite: " . number_format($data->ideal_battery_range_km, 2, ",", ".") . " km\n";
        $output .= "Kilometerstand: " . number_format($data->odometer, 0, ",", ".") . " km\n";
        $output .= "Letzter Trip: \n";
        $output .= "Start: " . $data->trip_start . "\n";
        $output .= "Dauer: " . number_format($data->trip_duration_sec / 60, 0, ",", ".") . " Minuten\n";
        $output .= "Verbrauch: " . number_format($data->trip_kwh, 2, ",", ".") . " kWh\n";
        $output .= "Durchschnittsverbrauch: " . number_format($data->trip_avg_kwh, 2, ",", ".") . " Wh\n";
        $output .= "Entfernung: " . number_format($data->trip_distance, 2, ",", ".") . " km\n";

    } elseif ($source === 'trip_database') {

        $output = "------------------------\n";
        $output .= "Batteriestand: " . $data->battery_level . "%\n";
        $output .= "Ideale Reichweite: " . number_format($data->EndRange, 2, ",", ".") . " km\n";
        $output .= "Kilometerstand: " . number_format($data->EndKm, 0, ",", ".") . " km\n";
        $output .= "Letzter Trip: \n";
        $output .= "Start: " . $data->StartDate . "\n";
        $output .= "Dauer: " . number_format($data->DurationMinutes, 0, ",", ".") . " Minuten\n";
        $output .= "Verbrauch: " . number_format($data->consumption_kWh, 2, ",", ".") . " kWh\n";
        $output .= "Durchschnittsverbrauch: " . number_format($data->avg_consumption_kWh_100km, 2, ",", ".") . " kWh\n";
        $output .= "Entfernung: " . number_format($data->km_diff, 2, ",", ".") . " km\n";

    }

    return $output;
}

function getCurrentJson()
{
    return file_get_contents('/etc/teslalogger/current_json.txt');
}

function getTripJson($time, $connection)
{
    $data = null;
    $sql = "SELECT t.*, p.battery_level
            FROM trip t
            LEFT JOIN drivestate d ON t.EndDate = d.EndDate
            LEFT JOIN pos p ON d.EndPos = p.id
            WHERE t.endDate < '" . $time . "' ORDER BY t.EndDate DESC LIMIT 1;";
    if ($result = mysqli_query($connection, $sql)) {
        $trip = mysqli_fetch_assoc($result);
        $data = json_encode($trip);
    }
    return $data;
}

function removeBOM($data)
{
    if (0 === strpos(bin2hex($data), 'efbbbf')) {
        return substr($data, 3);
    }
    return $data;
}