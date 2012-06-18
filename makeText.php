<?php

require_once('lib.php');

$daily = getHTML("http://weather.yahoo.co.jp/weather/jp/28/6310/28102.html");
$weekly = simplexml_load_file("http://rss.weather.yahoo.co.jp/rss/days/6310.xml");
$umbrella = getHTML("http://weather.yahoo.co.jp/weather/jp/expo/umbrella/28/6310.html");
$sourceURL = file_get_contents("http://tinyurl.com/api-create.php?url=http://weather.yahoo.co.jp/weather/jp/28/6310/28102.html");

$pattern_rainfall = h('<td bgcolor="#eeeeee" nowrap><small>降水量（mm/h）</small></td>');
$pattern_weather = h('<td bgcolor="#eeeeee"><small>天気</small></td>');
$pattern_umbrella_today = h('<!---Today--->');
$pattern_umbrella_tomorrow = h('<!---Tomorrow--->');


// 3時間ごとの降水量を$daily_rainfallへ格納
// 今日の降水量 -> $daily_rainfall_today[0~7] ,
// 明日の降水量 -> $daily_rainfall_tomorrow[0~7]
// 今日の天気 -> $daily_weather_today[0~7]
// 明日の天気 -> $daily_weather_tomorrow[0~7]
// 引数[0~7]に3をかけるとそのときの時間が求まる

$flag_rain = 0;
$flag_weather = 0;
foreach ($daily as $daily_num => $daily_line) {
  $line =  h($daily_line);
  if(strstr($line, $pattern_rainfall)){
    $flag_rain++;
    for($i = 1; $i < 9; $i++){
      if($flag_rain == 1){
        $daily_rainfall_today[] = strip_tags($daily[$daily_num + $i]);
      }
      elseif($flag_rain == 2){
        $daily_rainfall_tomorrow[] = strip_tags($daily[$daily_num + $i]);
      }
    }
  }
  if(strstr($line, $pattern_weather)){
    $flag_weather++;
    for($i = 1; $i < 9; $i++){
      if($flag_weather == 1){
        $daily_weather_today[] = strip_tags($daily[$daily_num + 2*$i - 1]);
      }
      elseif($flag_weather == 2){
        $daily_weather_tomorrow[] = strip_tags($daily[$daily_num + 2*$i - 1]);
      }
    }
    
  }
}

// 傘指数を$umbrella_scoreへ格納
// 今日の傘指数：$umbrella_score -> today
// 今日のコメント：$umbrella_score -> today_comment
// 明日の傘指数：$umbrella_score -> tomorrow
// 明日のコメント：$umbrella_score -> tomorrow_comment


foreach ($umbrella as $umbrella_num => $umbrella_line) {
  $line = h($umbrella_line);
  if(strstr($line, $pattern_umbrella_today)){
    $umbrella_score['today'] = strip_tags($umbrella[$umbrella_num + 10]);
    $umbrella_score['today_comment'] = strip_tags($umbrella[$umbrella_num + 9]) . '。'; 
  }
  if(strstr($line, $pattern_umbrella_tomorrow)){
    $umbrella_score['tomorrow'] = strip_tags($umbrella[$umbrella_num + 10]);
    $umbrella_score['tomorrow_comment'] = strip_tags($umbrella[$umbrella_num + 9]) . '。'; 
  }
}

// 降水量の最大値とその時間を求める
// 今日の最大降水量
// 時間：$max_rainfalltime_today
// 降水量：max($daily_rainfall_today)
// 明日の最大降水量
// 時間：$max_rainfalltime_tomorrow
// 降水量：max($daily_rainfall_tomorrow)

foreach ($daily_rainfall_today as $time => $rainfall){
  if(max($daily_rainfall_today) == $rainfall){
    $max_rainfalltime_today = 3*$time;
    break;
  }
}
foreach ($daily_rainfall_tomorrow as $time => $rainfall){
  if(max($daily_rainfall_tomorrow) == $rainfall){
    $max_rainfalltime_tomorrow = 3*$time;
    break;
  }
}


// 次に雨が降る日を検索

for($i = 1; $i < 7; $i++){
  if(strstr($weekly -> channel -> item[$i] -> description, '雨')){
    $rainday = $i;
    break;
  }
}
if(is_null($rainday)){
  $raindayrep = 'しばらく雨の予報はありません。';
}
elseif($rainday == 1){
  $raindayrep = '明日は雨の恐れがあります。';
}
else{
  $raindayrep = '次に雨の恐れがあるのは' . $rainday . '日後です。';
}



// 朝6時の場合
if(date("H")<12){
  $day = '今日';
  // 今日の天気を週間予報から取得
  $weather = '【' . $weekly -> channel -> item[0] -> description . '】'; 
  // 雨のピークタイムの告知
  if(max($daily_rainfall_today) == 0){
    $rainrep = '雨の心配はないでしょう。';
  }
  else{
    $rainrep = '雨のピークは' . $max_rainfalltime_today . '時頃の' . max($daily_rainfall_today) . 'mm/hの' . $daily_weather_today[$max_rainfalltime_today / 3] . 'です。';
  }
  // 傘に関するコメント告知
  $umbrellarep = $umbrella_score[today_comment]; 
}
// 夜11時の場合
else{
  $day = '明日';
  // 明日の天気を週間予報から取得
  $weather = '【' . $weekly -> channel -> item[1] -> description . '】'; 
  // 雨のピークタイムの告知
  if(max($daily_rainfall_tomorrow) == 0){
    $rainrep = '雨の心配はないでしょう。';
  }
  else{
    $rainrep = '雨のピークは' . $max_rainfalltime_tomorrow . '時頃の' . max($daily_rainfall_tomorrow) . 'mm/hの' . $daily_weather_tomorrow[$max_rainfalltime_tomorrow / 3] . 'です。';
  }
  // 傘に関するコメント告知
  $umbrellarep = $umbrella_score[tomorrow_comment]; 



}

$report = $day . 'の神戸市の天気(最高/最低気温)は、' . $weather . 'です。' . $rainrep . $umbrellarep . $raindayrep . ' ' . $sourceURL;
echo $report;


