<?php


namespace Logfile2Telegram\Service;


class ChargeFinder
{
    public function findCharges($seconds = 305)
    {
        $charges = [];
        $sql = "SELECT chargingstate.StartDate, chargingstate.EndDate, 
                charging_End.charge_energy_added, 
                charging.ideal_battery_range_km AS StartSOC, 
                charging_End.ideal_battery_range_km AS EndSOC,
                charging_End.ideal_battery_range_km - charging.ideal_battery_range_km as SOCgeladen,
                charging.battery_level as Start_battery_level,
                charging_End.battery_level as End_battery_level,
                (select EndSOC / End_battery_level * 100 ) as CalculatedMaxRange,
                pos.odometer, TIMESTAMPDIFF(MINUTE, chargingstate.StartDate, chargingstate.EndDate) as MinuteDiff
                FROM charging
                INNER JOIN chargingstate ON charging.id = chargingstate.StartChargingID
                INNER JOIN pos ON chargingstate.pos = pos.id 
                LEFT OUTER JOIN charging AS charging_End ON chargingstate.EndChargingID = charging_End.id
                WHERE chargingstate.EndDate >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . (int) $seconds . " SECOND)
                AND TIMESTAMPDIFF(MINUTE, chargingstate.StartDate, chargingstate.EndDate) > 3 AND chargingstate.EndChargingID - chargingstate.StartChargingID > 4
                ORDER BY chargingstate.EndDate ASC";
        if ($result = mysqli_query(DbConnection::getDbLink(), $sql)) {
            while ($charge = mysqli_fetch_object($result)) {
                $charges[] = $charge;
            }
        }
        return $charges;
    }

    public function findChargeByTime($time)
    {
        $charge = null;
        $sql = "SELECT chargingstate.StartDate, chargingstate.EndDate, 
                charging_End.charge_energy_added, 
                charging.ideal_battery_range_km AS StartSOC, 
                charging_End.ideal_battery_range_km AS EndSOC,
                charging_End.ideal_battery_range_km - charging.ideal_battery_range_km as SOCgeladen,
                charging.battery_level as Start_battery_level,
                charging_End.battery_level as End_battery_level,
                (select EndSOC / End_battery_level * 100 ) as CalculatedMaxRange,
                pos.odometer, TIMESTAMPDIFF(MINUTE, chargingstate.StartDate, chargingstate.EndDate) as MinuteDiff
                FROM charging
                INNER JOIN chargingstate ON charging.id = chargingstate.StartChargingID
                INNER JOIN pos ON chargingstate.pos = pos.id 
                LEFT OUTER JOIN charging AS charging_End ON chargingstate.EndChargingID = charging_End.id
                WHERE chargingstate.EndDate <= '" . $time . "'
                AND TIMESTAMPDIFF(MINUTE, chargingstate.StartDate, chargingstate.EndDate) > 3 AND chargingstate.EndChargingID - chargingstate.StartChargingID > 4
                ORDER BY chargingstate.EndDate DESC
                LIMIT 1";
        if ($result = mysqli_query(DbConnection::getDbLink(), $sql)) {
            $charge = mysqli_fetch_object($result);
        }
        return $charge;
    }

    public function findLatestCharge()
    {
        $charge = null;
        $sql = "SELECT chargingstate.StartDate, chargingstate.EndDate, 
                charging_End.charge_energy_added, 
                charging.ideal_battery_range_km AS StartSOC, 
                charging_End.ideal_battery_range_km AS EndSOC,
                charging_End.ideal_battery_range_km - charging.ideal_battery_range_km as SOCgeladen,
                charging.battery_level as Start_battery_level,
                charging_End.battery_level as End_battery_level,
                (select EndSOC / End_battery_level * 100 ) as CalculatedMaxRange,
                pos.odometer, TIMESTAMPDIFF(MINUTE, chargingstate.StartDate, chargingstate.EndDate) as MinuteDiff
                FROM charging
                INNER JOIN chargingstate ON charging.id = chargingstate.StartChargingID
                INNER JOIN pos ON chargingstate.pos = pos.id 
                LEFT OUTER JOIN charging AS charging_End ON chargingstate.EndChargingID = charging_End.id
                WHERE TIMESTAMPDIFF(MINUTE, chargingstate.StartDate, chargingstate.EndDate) > 3 AND chargingstate.EndChargingID - chargingstate.StartChargingID > 4
                ORDER BY chargingstate.EndDate DESC
                LIMIT 1";
        if ($result = mysqli_query(DbConnection::getDbLink(), $sql)) {
            $charge = mysqli_fetch_object($result);
        }
        return $charge;
    }
}
