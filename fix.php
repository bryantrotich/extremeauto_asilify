<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

include_once 'vendor/autoload.php';

use Simcify\Application;
use Simcify\Database;
use Simcify\Asilify;
use Simcify\Auth;
use Simcify\Mail;
use Simcify\Sms;


$app = new Application();

$today = date("Y-m-d");


/**
 * Send queued messages
 * 
 */
$messages = Database::table("messages")->where("status", "Queued")->orderBy("id", false)->get();
if (!empty($messages)) {
	foreach ($messages as $key => $message) {
	    
	}
}