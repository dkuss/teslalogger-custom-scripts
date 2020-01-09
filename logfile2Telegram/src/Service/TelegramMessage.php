<?php


namespace Logfile2Telegram\Service;


class TelegramMessage
{
    private $botId;
    private $chatId;

    private $messageContent = '';

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->botId = $config['telegram_bot_identifier'];
        $this->chatId = $config['telegram_chat_id'];
    }

    public function addLine($string)
    {
        if (!empty($string)) {
            $this->messageContent .= $string;
        }
    }

    public function prependLine($string)
    {
        if (!empty($string)) {
            $this->messageContent = $string . $this->messageContent;
        }
    }

    public function addLogLines($logLines)
    {
        foreach ($logLines as $line) {
            $this->addLine($line);
        }
    }

    public function addTrips($trips)
    {
        foreach ($trips as $trip) {
            $this->addTrip($trip);
        }
    }

    public function addTrip($trip)
    {
        if ($trip->km_diff < 0.5) {
            return;
        }
        $formattedString = $this->formatTrip($trip);
        $this->addLine($formattedString);
    }

    public function formatTrip($data)
    {
        $output = "------------------------\n";
        $output .= "AKTUELL: \n";
        $output .= "Akt. SoC: " . $data->EndSoc . "%\n";
        $output .= "Akt. Typ. Range: " . number_format($data->EndRange, 2, ",", ".") . " km\n";
        $output .= "Km Stand: " . number_format($data->EndKm, 0, ",", ".") . " km\n";
        $output .= "------------------------\n";
        $output .= "LETZTER TRIP: \n";
        $output .= "Start: " . $data->StartDate . "\n";
        $output .= "Ende: " . $data->EndDate . "\n";
        $output .= "Dauer: " . number_format($data->DurationMinutes, 0, ",", ".") . " Minuten\n";
        $output .= "Distanz: " . number_format($data->km_diff, 2, ",", ".") . " km (TR: " . number_format(($data->StartRange - $data->EndRange), 2, ",", ".") . " km)\n";
        $output .= "Typ. Range: " . number_format($data->StartRange, 2, ",", ".") . " km → " . number_format($data->EndRange, 2, ",", ".") . " km\n";
        $output .= "SoC: " . $data->StartSoc . "% → " . $data->EndSoc . "%\n";
        $output .= "Ges. Verbrauch: " . number_format($data->consumption_kWh, 2, ",", ".") . " kWh\n";
        $output .= "Ø Verbrauch: " . number_format($data->avg_consumption_kWh_100km * 10, 0, ",", ".") . " Wh/km\n";
        return $output;
    }

    public function addCharges($charges)
    {
        foreach ($charges as $charge) {
            $this->addCharge($charge);
        }
    }

    public function addCharge($charge)
    {
        $formattedString = $this->formatCharge($charge);
        $this->addLine($formattedString);
    }

    public function formatCharge($data)
    {
        $output = "------------------------\n";
        $output .= "AKTUELL: \n";
        $output .= "Akt. SoC: " . $data->End_battery_level . "%\n";
        $output .= "Akt. Typ. Range: " . number_format($data->EndSOC, 2, ",", ".") . " km\n";
        $output .= "Km Stand: " . number_format($data->odometer, 0, ",", ".") . " km\n";
        $output .= "------------------------\n";
        $output .= "LETZTE LADUNG: \n";
        $output .= "Start: " . $data->StartDate . "\n";
        $output .= "Ende: " . $data->EndDate . "\n";
        $output .= "Dauer: " . number_format($data->MinuteDiff, 0, ",", ".") . " Minuten\n";
        $output .= "Strom geladen: " . number_format($data->charge_energy_added, 2, ",", ".") . " kWh\n";
        $output .= "Reichweite geladen: " . number_format($data->SOCgeladen, 2, ",", ".") . " km\n";
        $output .= "SoC: " . number_format($data->Start_battery_level, 0, ",", ".") . "% → " . number_format($data->End_battery_level, 0, ",", ".") . "%\n";
        $output .= "Typ. Range: " . number_format($data->StartSOC, 2, ",", ".") . " km → " . number_format($data->EndSOC, 2, ",", ".") . " km\n";
        $output .= "Max. Range: " . number_format($data->CalculatedMaxRange, 2, ",", ".") . " km\n";
        return $output;
    }

    public function hasContent()
    {
        return !empty($this->messageContent);
    }

    public function send()
    {
        if (!empty($this->messageContent)) {
            $now = new \DateTime();
            $this->prependLine($now->format("Y-m-d H:i:s") . "\n");

            $this->sendText($this->messageContent);
        }
    }

    public function sendText($text)
    {
        $data = [
            'text' => (string) $text,
            'chat_id' => $this->chatId
        ];
        echo file_get_contents("https://api.telegram.org/" . $this->botId . "/sendMessage?" . http_build_query($data));
    }

    /**
     * @return string
     */
    public function getMessageContent()
    {
        return $this->messageContent;
    }

    public function getMe()
    {
        echo "getMet: ";
        echo file_get_contents("https://api.telegram.org/". $this->botId ."/getMe");
        echo "<br>";
    }

    public function getUpdates()
    {
        echo "getUpdates: ";
        echo file_get_contents("https://api.telegram.org/". $this->botId ."/getUpdates");
        echo "<br>";
    }

}