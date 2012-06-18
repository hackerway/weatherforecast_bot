<?php

require_once('twitteroauth/twitteroauth.php');
require_once('config.php');
require_once('makeText.php');

$conn = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

$params = array(
  'status' => $report
);

$result = $conn->post('statuses/update', $params);

var_dump($result);
