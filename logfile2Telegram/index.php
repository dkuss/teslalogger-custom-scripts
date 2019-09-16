<?php

use Logfile2Telegram\Service\ChargeFinder;
use Logfile2Telegram\Service\LogReader;
use Logfile2Telegram\Service\TelegramMessage;
use Logfile2Telegram\Service\TripFinder;

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/config.php';
if ($config['env'] === 'dev') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

$telegramMessage = new TelegramMessage();
$logReader = new LogReader();
$tripFinder = new TripFinder();
$chargeFinder = new ChargeFinder();

$seconds = 305;

$logLines = $logReader->getLogLines($seconds);
$telegramMessage->addLogLines($logLines);

$trips = $tripFinder->findTrips($seconds);
$telegramMessage->addTrips($trips);

$charges = $chargeFinder->findCharges($seconds);
$telegramMessage->addCharges($charges);

$telegramMessage->send();

echo date('Y-m-d H:i:s') . '<br>';
echo nl2br($telegramMessage->getMessageContent());
