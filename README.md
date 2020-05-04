# PHP-Visit-Counter

## Prerequisite
* Have an SQL table named `statistics` with the following fields: `id | pageId | numberOfDisplays | Date`

## Example of use to increment the number of views
```
require_once ToolsPath . '/PHP-Visit-Counter/VisitCounter.php';

// Get the id of the current page
$req = $db->prepare("SELECT id FROM pages where path = :path");
$req->bindValue('path', $_SERVER['REQUEST_URI'], PDO::PARAM_STR);
$req->execute();

// Increment the number of views
$visiteCounter = new VisitCounter($db);
$visiteCounter->incrementCounter($req->fetch()['id']);
```

## Example of use to see statistics
```
<h1>Statistics</h1>
<?php
    require_once '/PHP-Visit-Counter/StatisticsView.php';
    $statisticsView = new StatisticsView(DbConnect::getInstance(), 'pages', 'name');
    $statisticsView->lastDays();
?>
```
