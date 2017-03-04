<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Comp\GameManager\DB;

require_once('vendor/autoload.php');

return ConsoleRunner::createHelperSet(DB::getInstance());
