# PHP-Visit-Counter

## Prerequisite
* Have an SQL table named `statistics` with the following fields: `id | pageId | numberOfDisplays | Date`

## Example of use
```
<h1>Statistics</h1>
<?php
    require_once '/PHP-Visit-Counter/StatisticsView.php';
    $statisticsView = new StatisticsView(DbConnect::getInstance(), 'pages', 'name');
    $statisticsView->lastDays();
?>
```
