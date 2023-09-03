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
	$ipv4_raw = '';
	$ipv4 = ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'];

	/* リクエストパラメータ `ip` と比較してマッチしたら TRUE 返答返し終了 */
	foreach ( $ipv4 as $key => $val ) {
		if ( is_included_ipv4_addresses( $val, $reqip ) === TRUE ) {
			return ['result'=>TRUE, 'detail'=>'RFC1918'];
		}
	}

	$internalDB = [];
	/**
	 * 
	 * @flow InternalDB::textfile
	 * - <キャッシュファイル>が存在するか→[a0]
	 * →[a0]が真の時、mtimeを取得→[a1]
	 *    →[a1]の時間がと今の時間を比較→[a2]
	 *    →[a2]の時間が18時間(3/4日)以上空いているか→[a3]
	 *    →[a3]が真の時、<神様データ>からデータダウンロードする→[a4]
	 *       →[a4]を整形し、<キャッシュファイル>に保存。→[a5]
	 *       →[a5]を保存できたか→[a6]
	 *       →比較元を<神様データ>とする。
	 *    →[a3]が偽の時、比較元を<キャッシュファイル>とする。
	 * →[a0]が偽の時、<神様データ>からデータダウンロードする→[a4] 
	 *    →[a4]を整形し、<キャッシュファイル>に保存。→[a5]
	 *    →比較元を<神様データ>とする。
	 */
	$internalDB['mode'] = 'text';
	if (FALSE) {
	} elseif ( $internalDB['mode'] == 'text' ) {
		/* InternalDB::textfile */
		$internalDB['fname'] = 'jp.txt';
		if (file_exists($$internalDB['fname'])) {
			$internalDB['mtime'] = filemtime($internalDB['fname']);
			$internalDB['older'] = FALSE;
			$internalDB['older'] = ( time() - $internalDB['mtime'] ) >= ( 3660 * 18 );

			if ($internalDB['older']) {
				$ipv4_raw = file_get_contents('https://ipv4.fetus.jp/jp.txt');
				$internalDB['saved'] = file_put_contents($internalDB['fname'], $ipv4_raw);
			} else {
				$ipv4_raw = file_get_contents($internalDB['fname']);
			}
		} else {
			$ipv4_raw = file_get_contents('https://ipv4.fetus.jp/jp.txt');
			$internalDB['saved'] = file_put_contents($internalDB['fname'], $ipv4_raw);
		}
	}

	/* ダウンロードして整形 */
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
	/* ダウンロードして整形::until */

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
				'runtime_version' => dechex(filemtime(__FILE__)),
				'issued_at'=>[
					'timestamp'=>time(),
					'description'=>date('Y/m/d H:i:s T')
				]
			],
			'result' => [
				'result'=>isJP($reqip),
				'request'=>$reqip,
			]
		]
	);
	exit();
}
main();
