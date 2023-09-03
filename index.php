<?php
date_default_timezone_set('Asia/Tokyo');
header('content-type: application/json');

function is_included_ipv4_addresses($range, $remote_ip){
	/**
	 * IPアドレスが指定した範囲内にあるかどうか判別する
	 * https://qiita.com/ran/items/039706c93a8ff85a011a
	 * 
	 * @param {String} range CIDR表記(0.0.0.0/32)
	 * @param {String} remote_ip
	 * @return {Boolean} 判別結果
	 *
	 */
	list($accept_ip, $mask) = explode('/', $range);
	$accept_long = ip2long($accept_ip) >> (32 - $mask);
	$remote_long = ip2long($remote_ip) >> (32 - $mask);
	return $accept_long == $remote_long;
}

function isJP($reqip){
	/**
	 * IPアドレス一覧をダウンロードしてコメント行と空白行を削る
	 * 
	 * @param {String} reqip
	 * @return {Boolean} 判別結果
	 *
	 */
	$ipv4 = ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'];

	/* リクエストパラメータ `ip` と比較してマッチしたら TRUE 返答返し終了 */
	foreach ( $ipv4 as $key => $val ) {
		if ( is_included_ipv4_addresses( $val, $reqip ) === TRUE ) {
			return ['result'=>TRUE, 'detail'=>'RFC1918'];
		}
	}

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

	/* リクエストパラメータ `ip` と比較してマッチしたら TRUE 返答返し終了 */
	foreach ( $ipv4 as $key => $val ) {
		if ( is_included_ipv4_addresses( $val, $reqip ) === TRUE ) {
			return ['result'=>TRUE, 'detail'=>'ja_JP'];
		}
	}

	return [FALSE, ''];
}

function main(){
	/* リクエストパラメータ `ip` に値を持ってたらそれに置き換える */
	$reqip = $_SERVER['REMOTE_ADDR'];
	if ( isset( $_GET['ip'] ) && $_GET['ip'] != '' ) {
		$reqip = $_GET['ip'];
	}
	
	echo json_encode(
		[
			'meta' => [
				'version' => 2,
				'runtime_hash' => md5(md5_file(__FILE__, TRUE)),
				'runtime_version' => dechex(filemtime(__FILE__))
			],
			'header' => [
				'result'=>[
					'result'=>'{Boolean} Result',
					'detail'=>'{String} Detail'
				],
				'request'=>'{String} Request IP Address',
				'time'=>[
					'timestamp'=>'{Integer} Timestamp',
					'description'=>'{Datetime} DateTime Format'
				]
			],
			'result' => [
				'result'=>isJP($reqip),
				'request'=>$reqip,
				'time'=>[
					'timestamp'=>time(),
					'description'=>date('Y/m/d H:i:s T')
				]
			]
		]
	);
	exit();
}
main();
