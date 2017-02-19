<?php

ini_set('max_execution_time', 59);
ini_set('memory_limit', '1G');

$startTime = microtime(true);

require_once("config/secrets.php");

function apiRequestWebhook($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  header("Content-Type: application/json");
  echo json_encode($parameters);
  return true;
}

function exec_curl_request($handle) {
  $response = curl_exec($handle);

  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    error_log("Curl returned error $errno: $error\n");
    curl_close($handle);
    return false;
  }

  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  curl_close($handle);

  if ($http_code >= 500) {
    // do not wat to DDOS server if something goes wrong
    sleep(10);
    return false;
  } else if ($http_code != 200) {
    $response = json_decode($response, true);
    error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    }
    return false;
  } else {
    $response = json_decode($response, true);
    if (isset($response['description'])) {
      error_log("Request was successfull: {$response['description']}\n");
    }
    $response = $response['result'];
  }

  return $response;
}

function apiRequest($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  foreach ($parameters as $key => &$val) {
    // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
      $val = json_encode($val);
    }
  }
  $url = API_URL.$method.'?'.http_build_query($parameters);

  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);

  return exec_curl_request($handle);
}

function apiRequestJson($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  $handle = curl_init(API_URL);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
  curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

  return exec_curl_request($handle);
}

function processMessage($message) {
  // process incoming message
  $message_id = $message['message_id'];
  $chat_id = $message['chat']['id'];
  if (isset($message['text'])) {
    // incoming text message
    $text = $message['text'];

    if (strpos($text, "/start") === 0) {
      apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Hello', 'reply_markup' => array(
        'keyboard' => array(array('Hello', 'Hi')),
        'one_time_keyboard' => true,
        'resize_keyboard' => true)));
    } else if ($text === "Hello" || $text === "Hi") {
      apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Nice to meet you'));
    } else if (strpos($text, "/stop") === 0) {
      // stop now
    } else if (strpos($text, "/pogoda") === 0) {
      $json = file_get_contents(WEATHER_API_URL);
      $text = processWeather($json);
      apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $text));
    } else {
      apiRequestJson("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => 'Cool'));
    }
  } else {
    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
  }
}

function processWeather($json) {
    $data = json_decode($json, true);
    $temp = round($data['main']['temp']);
    if ($temp > 0) {
       $temp = '+' . $temp;
    }
    $temp .= '°C';
    $windSpeed = round($data['wind']['speed']);
    $windSpeedKMH = round($data['wind']['speed'] * 3.6, 1);
    $windDeg = $data['wind']['deg'];
    $windDir = calcDirection($windDeg);
    $pressure = round($data['main']['pressure'] * 0.750, 2);
    $response = [
	'Температура' => $temp,
        'Ветер' => $windDir . ', ' . round($data['wind']['speed']) . 'м/c (' . $windSpeedKMH . ' км/ч) ',
        'Давление' => $pressure . ' мм рт.ст.',
	'Влажность' => $data['main']['humidity'] . '%',
    ];
    $text = '';
    foreach($response as $k => $v) {
       $text .= $k . ': ' . $v . PHP_EOL;
    }
    return $text;
}

function calcDirection($degree) {
    if ($degree < 0) {
       $degree = 360 + $degree;
    }
    $dirs = [
	'С',
	'СВ',
	'В',
	'ЮВ',
	'Ю',
	'ЮЗ',
	'З',
	'СЗ'
    ];
    return $dirs[(round($degree/45) % 8)];
}

//define('WEBHOOK_URL', 'https://bot.afinogenoff.com/tlghooks');

//if (php_sapi_name() == 'cli') {
  // if run from console, set or delete webhook
//  apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
//  exit;
//}

do {

    $files = scandir('../../frames');
    if (count($files) > 2) {

	apiRequest("sendMessage", array('chat_id' => CHAT_ID, 'text' => 'Тревога!'));
	sleep(2);
    $files = scandir('../../frames');
$file = $files[count($files)-1];

$postFields = [
    'chat_id' => CHAT_ID,
    'photo'   => new CURLFile(realpath('../../frames/' . $file)),
];
$ch = curl_init();
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type:multipart/form-data",
]);
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . BOT_TOKEN . "/sendPhoto?chat_id=" . CHAT_ID);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
$output = curl_exec($ch);
var_dump($output);

	sleep(10);
        $files = scandir('../../frames');
	foreach ($files as $file) {
	    if ($file[0] != '.') {
                unlink('../../frames/' . $file);
            }
        }
    }

} while(true);



exit(0);

$lastUpdate = 0;
do {
    $updates = apiRequest('getUpdates', ['offset' => $lastUpdate+1, 'limit' => 1, 'timeout' => 10]);
    foreach ($updates as $update) {
	processMessage($update['message']);
        $lastUpdate = $update['update_id'];
    }
    sleep(1);
} while (microtime(true) - $startTime < 58);

//$content = file_get_contents("php://input");
//$update = json_decode($content, true);

//if (!$update) {
//  // receive wrong update, must not happen
//  exit;
//}

//if (isset($update["message"])) {
//  processMessage($update["message"]);
//}