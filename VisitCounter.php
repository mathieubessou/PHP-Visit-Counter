<?php

/*
Prérequis:
- Table statistics avec les champs suivant : [id|pageId|numberOfDisplays|date]
*/
class VisitCounter
{
    const TableNameForStatistics = "statistics";
    const DbDateTimeFormat = 'Y-m-d H:i:s';
    const DbDateFormat = 'Y-m-d';
    const BotRegex = '/bot|crawl|spider|slurp/i';


    public function __construct($dbAccess)
    {
        $this->dbAccess = $dbAccess;
        $dt = new DateTime('now');
        $this->dateNowForSQL = $dt->format(self::DbDateFormat);
    }

    protected $dbAccess;
    protected $dateNowForSQL;
    
    // Récupère le nombre de vue d'aujourd'hui sur l'id de page indiqué
    public function getTodayCounterValue($pageId)
    {
        if (!is_numeric($pageId)) throw new Exception("Need a numerical value", 2);
        $req = $this->dbAccess->prepare("SELECT numberOfDisplays FROM ".self::TableNameForStatistics." WHERE pageId = :pageId AND date = :date");
        $req->bindValue('pageId', $pageId, PDO::PARAM_INT);
        $req->bindValue('date', $this->dateNowForSQL, PDO::PARAM_STR);
        $req->execute();
        return $req->fetch()['numberOfDisplays']; 
    }

    // Incrémente le compteur du jour, lié à l'id de page indiqué
    public function incrementCounter($pageId)
    {
        if (!is_numeric($pageId)) throw new Exception("Need a numerical value", 2);
        // Ne rien faire si la page est visité par un bot
        if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match(self::BotRegex, $_SERVER['HTTP_USER_AGENT']))
        {
            return;
        }

        $counter = intval($this->getTodayCounterValue($pageId));
        
        // Si le compteur est à 0 c'est que il n'y a pas d'entrée dans la base de données, lié à ce jour.
        if ($counter == null)
        {
            $req = $this->dbAccess->prepare("INSERT INTO ".self::TableNameForStatistics." 
            (id, pageId, numberOfDisplays, date) 
            VALUES ('', :pageId, :numberOfDisplays, :date)");
            $req->bindValue('pageId', $pageId, PDO::PARAM_INT);
            $req->bindValue('numberOfDisplays', 1, PDO::PARAM_INT);
            $req->bindValue('date', $this->dateNowForSQL, PDO::PARAM_STR);
            $req->execute();
        }
        else
        {
            $req = $this->dbAccess->prepare("UPDATE ".self::TableNameForStatistics." SET numberOfDisplays = :numberOfDisplays WHERE pageId = :pageId AND date = :date");
            $req->bindValue('pageId', $pageId, PDO::PARAM_INT);
            $req->bindValue('numberOfDisplays', $counter + 1, PDO::PARAM_INT);
            $req->bindValue('date', $this->dateNowForSQL, PDO::PARAM_STR);
            $req->execute();
        }
    }

    // Supprime les statistiques lié à l'id de page indiqué
    public function RemoveStats($pageId)
    {
        if (!is_numeric($pageId)) throw new Exception("Need a numerical value", 2);
        $req = $this->dbAccess->prepare("DELETE FROM ".self::TableNameForStatistics." WHERE pageId = :pageId");
        $req->bindValue('pageId', $pageId, PDO::PARAM_INT);
        $req->execute();
    }

    // Retourne un tableau classé par identifiant de page
    // Exemple: [idpage7 => numberOfDisplays]
    // 0 = Depuis le début
    // 1 = Aujourd'hui
    public function getAllStatsOfPages($dayNumber)
    {
        if (!is_int($dayNumber)) throw new Exception("Need an integer value", 2);
        if ($dayNumber > 0) {
            $dayNumber = $dayNumber - 1; // Ainsi, si $dayNumber==1 : retournera les stats du jour présent
            $dt = new DateTime('now');
            $interval = new DateInterval('P'.$dayNumber.'D');
            $firstDay = $dt->sub($interval);
            $firstDayForSql = $firstDay->format(self::DbDateFormat);
            
            $req = $this->dbAccess->prepare(
                "SELECT pageId, SUM(numberOfDisplays) FROM ".self::TableNameForStatistics." WHERE date BETWEEN :date1 AND :date2 GROUP BY pageId");
            $req->bindValue('date1', $firstDayForSql, PDO::PARAM_STR);
            $req->bindValue('date2', $this->dateNowForSQL, PDO::PARAM_STR);
            $req->execute();
        }
        else { // Depuis le début
            $req = $this->dbAccess->prepare(
                "SELECT pageId, SUM(numberOfDisplays) FROM ".self::TableNameForStatistics." GROUP BY pageId");
            $req->execute();
        }

        $this->totalStats = 0; // Réinitialisation du total
        $array;
        while($data = $req->fetch())
        {
            $array[$data['pageId']] = $data['SUM(numberOfDisplays)'];
            $this->totalStats = $this->totalStats + $data['SUM(numberOfDisplays)'];
        }

        return $array;
    }

    protected $totalStats = 0;
    public function getLastTotalStats()
    {
        return $this->totalStats;
    }
}