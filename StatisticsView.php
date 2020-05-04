<?php
require_once __DIR__ . '/VisitCounter.php';

class StatisticsView
{
    public function __construct(PDO $dbInstance, string $pagesTableName, string $pageTitleFieldName) {
        $this->db = $dbInstance;
        $this->pagesTableName = htmlspecialchars($pagesTableName);
        $this->pageTitleFieldName = htmlspecialchars($pageTitleFieldName);
    }

    private $db;
    private $pagesTableName;
    private $pageTitleFieldName;

    public function lastDays()
    {
        $visiteCounter = new VisitCounter($this->db);

        // Get a list of pages
        $req = $this->db->prepare("SELECT id, $this->pageTitleFieldName FROM $this->pagesTableName");
        $req->execute();
        $pages = [];
        while ($data = $req->fetch())
        {
            $pages[$data['id']]['name'] = $data['name'];
            $pages[$data['id']]['counter'] = null;
        }

        // Get statistics from all pages on the selected period
        $interval = 7; // default period
        if (!isset($_POST['selectNumberDay']) && isset($_GET['d']) && is_numeric($_GET['d'])) {
            $interval = intval($_GET['d']);
        }
        elseif (!empty($_POST['selectNumberDay'])) {
            $interval = 0;
            if (is_numeric($_POST['selectNumberDay'])) {
                $interval = intval($_POST['selectNumberDay']);
            }
            $uri = str_split($_SERVER['REQUEST_URI'], '?')[0];
            header('Location:'.$uri.'?d=' . $interval);
        }
        $pageStats = $visiteCounter->getAllStatsOfPages($interval);

        
        foreach ($pageStats as $key => $value) {
            $pages[$key]['counter'] = $value;
        }
?>

<form action="" method="POST">
    <select name="selectNumberDay" onchange="this.form.submit();">
        <option value="1" <?= $interval==1 ? 'selected' : ''?>>Today</option>
        <option value="3" <?= $interval==3 ? 'selected' : ''?>>Last 3 days</option>
        <option value="7" <?= $interval==7 ? 'selected' : ''?>>Last 7 days</option>
        <option value="30" <?= $interval==30 ? 'selected' : ''?>>Last 30 days</option>
        <option value="90" <?= $interval==90 ? 'selected' : ''?>>Last 90 days</option>
        <option value="180" <?= $interval==180 ? 'selected' : ''?>>Last 180 days</option>
        <option value="365" <?= $interval==365 ? 'selected' : ''?>>Last 365 days</option>
        <option value="*" <?= $interval==0 ? 'selected' : ''?>>From the beginning</option>
    </select>
</form>
<br><br>
<table>
    <tr>
        <th>TOTAL</th>
        <td><?= $visiteCounter->getLastTotalStats() ?></td>
    </tr>
    <?php
        foreach($pages as $key => $page) {
            $name = $page['name'];
            $counter = $page['counter'] ?? 0;
            echo "<tr><th>$name</th><td>$counter</td></tr>";
        }
    ?>
</table>
<?php
    }


    public function byDate()
    {
        # code...
    }
}