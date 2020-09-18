<?php


namespace Logfile2Telegram\Service;


class LogReader
{
    private $input;
    private $logLines;
    private $logLinesSeconds;

    private $excludeLinesWith = [
        'eocoding',
        ' : offline',
        'TOKEN:',
        'Streamingtoken:',
        // 'Distance:',
        'ScanMyTesla FastMode:',
        'UpdateTripElevation',
        'GeocodeCache',
        'Missing:',
        'Mothership Timeout',
        '*** Check new Version ***',
        'ShiftStateChange:',
        // 'change TeslaLogger state:',
        'Checking TeslaLogger online update',
        'TeslaLogger is up to date',
    ];

    private function readInput()
    {
        $input = shell_exec('tail -n500 /etc/teslalogger/nohup.out');
        if ($input === false) {
            throw new \Exception('unable to read logfile');
        }
        return $input;
    }

    private function generateLogLines($seconds = 305)
    {
        $input = $this->getInput();

        $inputArray = explode(PHP_EOL, $input);
        $outputArray = [];

        $now = new \DateTime();
        foreach ($inputArray as $line) {

            if (empty(trim($line))) {
                continue;
            }

            try {
                //16.08.2019 20:44:46
                $dateTimeString = substr($line, 0, 19);
                $dateTime = new \DateTime($dateTimeString);
            } catch (\Exception $e) {
                echo $e->getMessage();
                echo "dateTime false - " . $line;
                continue;
            }

            $diff_in_seconds = $now->getTimestamp() - $dateTime->getTimestamp();
            if ($diff_in_seconds > $seconds) { // Meldungen älter als x Sekunden ignorieren
                continue;
            }

            foreach ($this->excludeLinesWith as $needle) {
                if (strpos($line, $needle) !== false) {
                    continue 2;
                }
            }
            if (strpos($line, 'Waiting for car to go to sleep') !== false) {
                $numberPos = strrpos($line, ' ') + 1;
                $number = (int)trim(substr($line, $numberPos));
                if (!in_array($number, [0, 20])) {
                    continue;
                }
            }

            $outputArray[] = trim($line) . "\n";

        }
        return $outputArray;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getInput()
    {
        if ($this->input === null) {
            $this->input = $this->readInput();
        }
        return $this->input;
    }

    /**
     * @param $seconds
     * @return mixed
     */
    public function getLogLines($seconds)
    {
        if ($this->logLines === null || $seconds != $this->logLinesSeconds) {
            $this->logLines = $this->generateLogLines($seconds);
            $this->logLinesSeconds = $seconds;
        }
        return $this->logLines;
    }

    public function getCurrentJson()
    {
        $file = file_get_contents('/etc/teslalogger/current_json.txt');
        $input = $this->removeBOM($file);
        $data = json_decode($input);
        return $data;
    }

    private function removeBOM($data)
    {
        if (0 === strpos(bin2hex($data), 'efbbbf')) {
            return substr($data, 3);
        }
        return $data;
    }
}
