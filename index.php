<?php
date_default_timezone_set('Asia/Tokyo');
header('content-type: application/json');

function is_included_ipv4_addresses($range, $remote_ip){
  ## [IPアドレスが指定した範囲内にあるかどうか判別する](https://qiita.com/ran/items/039706c93a8ff85a011a) ##
  list($accept_ip, $mask) = explode('/', $range);
  $accept_long = ip2long($accept_ip) >> (32 - $mask);
  $remote_long = ip2long($remote_ip) >> (32 - $mask);
  return $accept_long == $remote_long;
}

function isJP($reqip){

  ## IPアドレス一覧をダウンロードしてコメント行と空白行を削る ##
  $ipv4_raw = file('https://ipv4.fetus.jp/jp.txt');
  $ipv4 = '';
  foreach( $ipv4_raw as $key => $val ){
    $val = trim( $val );
    $val = str_replace( array("\r\n", "\r", "\n"), '', $val );
    if( empty( $val ) || preg_match( '/^#/', $val ) ){
      $val = NULL;
    }

    if( ! is_null( $val ) ){
      $ipv4 .= $val . PHP_EOL;
    }
  }
  $ipv4 = trim( $ipv4 );
  $ipv4 = explode( "\n", $ipv4 );

  ## リクエストパラメータ'ip' と比較してマッチしたらTRUE 返答返し終了 ##
  foreach ( $ipv4 as $key => $val ) {
    if ( is_included_ipv4_addresses( $val, $reqip ) === TRUE ) {
      return [ TRUE, $reqip ];
      exit();
    }
  }

  return [ FALSE, $reqip ];
  exit();

}

## リクエストパラメータ'ip'に値を持ってたらそれに置き換える ##
$reqip = $_SERVER['REMOTE_ADDR'];
if ( isset( $_GET['ip'] ) && $_GET['ip'] != '' ) {
  $reqip = $_GET['ip'];
}

echo json_encode( isJP($reqip) );
