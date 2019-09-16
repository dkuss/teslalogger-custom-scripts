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
        $output .= "Batteriestand: " . $data->battery_level . "%\n";
        $output .= "Ideale Reichweite: " . number_format($data->EndRange, 2, ",", ".") . " km\n";
        $output .= "Kilometerstand: " . number_format($data->EndKm, 0, ",", ".") . " km\n";
        $output .= "Letzter Trip: \n";
        $output .= "Start: " . $data->StartDate . "\n";
        $output .= "Dauer: " . number_format($data->DurationMinutes, 0, ",", ".") . " Minuten\n";
        $output .= "Verbrauch: " . number_format($data->consumption_kWh, 2, ",", ".") . " kWh\n";
        $output .= "Durchschnittsverbrauch: " . number_format($data->avg_consumption_kWh_100km, 2, ",", ".") . " kWh\n";
        $output .= "Entfernung: " . number_format($data->km_diff, 2, ",", ".") . " km\n";
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
        $output .= "Letzte Ladung: \n";
        $output .= "Start: " . $data->StartDate . "\n";
        $output .= "Dauer: " . number_format($data->MinuteDiff, 0, ",", ".") . " Minuten\n";
        $output .= "Power geladen: " . number_format($data->charge_energy_added, 2, ",", ".") . " kWh\n";
        $output .= "Entfernung geladen: " . number_format($data->SOCgeladen, 2, ",", ".") . " km\n";
        $output .= "End SoC: " . number_format($data->End_battery_level, 0, ",", ".") . "%\n";
        $output .= "End Reichweite: " . number_format($data->EndSOC, 0, ",", ".") . " km\n";
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

            $data = [
                'text' => $this->messageContent,
                'chat_id' => $this->chatId
            ];
            echo file_get_contents("https://api.telegram.org/" . $this->botId . "/sendMessage?" . http_build_query($data));
        }
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