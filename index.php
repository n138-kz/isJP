<?php
header('content-type: application/json');

function is_included_ipv4_addresses($range, $remote_ip){
  // q: https://qiita.com/ran/items/039706c93a8ff85a011a
  // [IPアドレスが指定した範囲内にあるかどうか判別する](https://qiita.com/ran/items/039706c93a8ff85a011a)
  list($accept_ip, $mask) = explode('/', $range);
  $accept_long = ip2long($accept_ip) >> (32 - $mask);
  $remote_long = ip2long($remote_ip) >> (32 - $mask);
  return $accept_long == $remote_long;
}

$reqip = $_SERVER['REMOTE_ADDR'];
if (isset($_GET['ip']) && $_GET['ip']!='') {
  $reqip=$_GET['ip'];
}

try{
  date_default_timezone_set('Asia/Tokyo');
  clearstatcache();

  if( ! file_exists( 'ipv4.txt' ) ){ throw new Exception('No such db file.'); }
  if( ! is_file(     'ipv4.txt' ) ){ throw new Exception('Not in normally.'); }
  if( ! is_readable( 'ipv4.txt' ) ){ throw new Exception('Not in readable.'); }

  $db4 = file( 'ipv4.txt' );

  foreach ($db4 as $key => $v) {
    $chk = is_included_ipv4_addresses($v, $reqip);
    if ($chk === TRUE) { echo json_encode([TRUE,$reqip]); exit(); }
  }

  echo json_encode([FALSE,$reqip]);
  exit();
} catch(Exception $e){
  echo ($e->getMessage());
  exit();
}
