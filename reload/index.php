<?php
header('content-type: text/plain');

// IPアドレス一覧をダウンロードしてコメント行と空白行を削る
$ipv4_raw = file('https://ipv4.fetus.jp/jp.txt');
$ipv4 = '';
foreach($ipv4_raw as $key => $val){
  $val = trim( $val );
  $val = str_replace( array("\r\n", "\r", "\n"), '', $val );
  if( empty( $val ) )             { $val = NULL; }
  if( preg_match( '/^#/', $val ) ){ $val = NULL; }

  if( ! is_null( $val ) ){
    $ipv4 .= $val . PHP_EOL;
  }
}
$ipv4 = trim($ipv4);

// ダウンロードしたデータをキャッシュする（書き込む）
try{
  date_default_timezone_set('Asia/Tokyo');
  clearstatcache();

  if( ! file_exists( '../ipv4.txt' ) ){ throw new Exception('No such db file.'); }
  if( ! is_file(     '../ipv4.txt' ) ){ throw new Exception('Not in normally.'); }
  if( ! is_readable( '../ipv4.txt' ) ){ throw new Exception('Not in readable.'); }
  if( ! is_writable( '../ipv4.txt' ) ){ throw new Exception('Not in writable.'); }

  $db4 = fopen( '../ipv4.txt', 'w' );
  fwrite( $db4, $ipv4 );
  fclose( $db4 );

  header('Location: ../');
  exit();
} catch(Exception $e){
  echo ($e->getMessage());
  exit();
}
