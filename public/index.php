<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Counter;
use App\Live;

Live::make()
	->page('/', Counter::class)
	->page('/hello', Counter::class)
	->start();
