<?php

use hub\HubServer;

$server = new HubServer();
$server
    ->addWiki()
    ->addProjects()
    ->addAccount()
    ->addRepository()
    ->run();