<?php

include_once './classes/DbLayer.class.php';
$settings= parse_ini_file("config/local.ini");
$db = new DbLayer($settings["username"], $settings["password"], $settings["host"], $settings["database"]);
$db->tearDown();
$db->setFixtures();