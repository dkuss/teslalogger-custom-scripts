<?php


namespace Logfile2Telegram\Service;


class TripFinder
{
    public function findTrips($seconds = 305)
    {
        $trips = [];
        $sql = "SELECT t.*, pe.battery_level as EndSoc, ps.battery_level as StartSoc
                FROM trip t
                LEFT JOIN drivestate d ON t.EndDate = d.EndDate
                LEFT JOIN pos pe ON d.EndPos = pe.id
                LEFT JOIN pos ps ON d.StartPos = ps.id
                WHERE t.endDate >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . (int) $seconds . " SECOND) ORDER BY t.EndDate ASC;";
        if ($result = mysqli_query(DbConnection::getDbLink(), $sql)) {
            while ($trip = mysqli_fetch_object($result)) {
                $trips[] = $trip;
            }
        }
        return $trips;
    }

    public function findTripByTime($time)
    {
        $trip = null;
        $sql = "SELECT t.*, p.battery_level
                FROM trip t
                LEFT JOIN drivestate d ON t.EndDate = d.EndDate
                LEFT JOIN pos p ON d.EndPos = p.id
                WHERE t.endDate <= '" . $time . "' ORDER BY t.EndDate DESC
                LIMIT 1;";
        if ($result = mysqli_query(DbConnection::getDbLink(), $sql)) {
            $trip = mysqli_fetch_object($result);
        }
        return $trip;
    }

    public function findLatestTrip()
    {
        $trip = null;
        $sql = "SELECT t.*, p.battery_level
                FROM trip t
                LEFT JOIN drivestate d ON t.EndDate = d.EndDate
                LEFT JOIN pos p ON d.EndPos = p.id
                ORDER BY t.EndDate DESC
                LIMIT 1;";
        if ($result = mysqli_query(DbConnection::getDbLink(), $sql)) {
            $trip = mysqli_fetch_object($result);
        }
        return $trip;
    }
}
