<?php
session_start();
date_default_timezone_set('Asia/Tokyo');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Origin, Accept, Access-Control-Allow-Headers, Authorization, X-Requested-With");

class IsJP {
	public const FLAG_JSON_ENCODE = JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_SLASHES;
	public const FLAG_JSON_DECODE = JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR;
	public const COMPOSER_FILE = './vendor/autoload.php';
	public const IPV4_FETUS_JP = 'https://ipv4.fetus.jp/jp.txt';
	public const IPV4_FETUS_EN = 'https://ipv4.fetus.jp/us.txt';
	public const PDO_OPTION = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => true,
		PDO::ATTR_PERSISTENT => true,
	];
	public $config = [];
	public $pdo_dsn = 'sqlite::memory:';

	function __construct(){
		$this->loadConfig(__DIR__.'/.secret/config.json');
		$this->setDatabase_option();
	}

	function loadConfig($fname=null){
		$fdata=file_get_contents($fname);
		$this->config=json_decode($fdata, true, 512, self::FLAG_JSON_DECODE);
	}

	function setDatabase_option(){
		$this->pdo_dsn = '';
		$this->pdo_dsn .= $this->config['internal']['databases'][0]['schema'];
		$this->pdo_dsn .= ':';
		$this->pdo_dsn .= 'host=' . $this->config['internal']['databases'][0]['host'] . ';';
		$this->pdo_dsn .= 'port=' . $this->config['internal']['databases'][0]['port'] . ';';
		$this->pdo_dsn .= 'dbname=' . $this->config['internal']['databases'][0]['database'] . ';';
		$this->pdo_dsn .= 'user=' . $this->config['internal']['databases'][0]['user'] . ';';
		$this->pdo_dsn .= 'password=' . $this->config['internal']['databases'][0]['password'] . ';';
		$this->pdo_dsn .= '';
	}

	function is_included_ipv4_addresses($range, $remote_ip){
		/**
		 * IPアドレスが指定した範囲内にあるかどうか判別する
		 * https://qiita.com/ran/items/039706c93a8ff85a011a
		 * @param {String} range CIDR表記(0.0.0.0/32)
		 * @param {String} remote_ip
		 * @return {Boolean} 判別結果
		 */
		list($accept_ip, $mask) = explode('/', $range);
		$accept_long = ip2long($accept_ip) >> (32 - $mask);
		$remote_long = ip2long($remote_ip) >> (32 - $mask);
		return $accept_long == $remote_long;
	}

	function download_iplist($dbfile){
		/* ダウンロードして整形 */
		$ipv4_raw = file($dbfile);
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
		$ipv4 = explode( "\n", trim( $ipv4 ) );
		return $ipv4;
	}

	function isJP($reqip){
		/**
		 * IPアドレス一覧をダウンロードしてコメント行と空白行を削る
		 * @param {String} reqip
		 * @return {Boolean} 判別結果
		 */

		$ipv4 = ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'];
		foreach ( $ipv4 as $key => $val ) {
			if ( $this->is_included_ipv4_addresses( $val, $reqip ) === TRUE ) {
				return ['result'=>FALSE, 'detail'=>'RFC1918'];
			}
		}

		$ipv4 = ['127.0.0.0/8'];
		foreach ( $ipv4 as $key => $val ) {
			if ( $this->is_included_ipv4_addresses( $val, $reqip ) === TRUE ) {
				return ['result'=>FALSE, 'detail'=>'RFC5735'];
			}
		}

		$ipv4 = ['169.254.0.0/16'];
		foreach ( $ipv4 as $key => $val ) {
			if ( $this->is_included_ipv4_addresses( $val, $reqip ) === TRUE ) {
				return ['result'=>FALSE, 'detail'=>'RFC3927'];
			}
		}

		$ipv4 = $this->download_iplist(self::IPV4_FETUS_JP);
		foreach ( $ipv4 as $key => $val ) {
			if ( $this->is_included_ipv4_addresses( $val, $reqip ) === TRUE ) {
				return ['result'=>TRUE, 'detail'=>'ja_JP'];
			}
		}

		$ipv4 = $this->download_iplist(self::IPV4_FETUS_EN);
		foreach ( $ipv4 as $key => $val ) {
			if ( $this->is_included_ipv4_addresses( $val, $reqip ) === TRUE ) {
				return ['result'=>FALSE, 'detail'=>'en_US'];
			}
		}

		return ['result'=>FALSE, 'detail'=>''];
	}

	function concat($arr){
		return implode( '', $arr );
	}

	function get_logdb(){
		try {
			$pdo = new PDO( $this->pdo_dsn, null, null, self::PDO_OPTION );
			$stm = $pdo->prepare('SELECT * FROM ' . $this->config['internal']['databases'][0]['tableprefix'] . ' WHERE client = :client and timestamp > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP - interval :adjusttime);');
			$attr = [
				'client'=>$_SERVER['REMOTE_ADDR'],
				'adjusttime'=>$this->config['internal']['api']['timelimit'],
			];
			$res = $stm->execute($attr);
			if( $res === false ){
				throw new \Exception('SQL Error');
			}
			$res = $stm->fetchAll();
			if( $res === false ){
				throw new \Exception('SQL Error');
			}
			return $res;
		} catch (\Exception $th) {
			error_log($th->getMessage());
			return [];
		}
	}

	function put_logdb($reqip){
		try {
			$pdo = new PDO( $this->pdo_dsn, null, null, self::PDO_OPTION );
			$stm = $pdo->prepare('INSERT INTO ' . $this->config['internal']['databases'][0]['tableprefix'] . ' VALUES (:timestamp, :uuid, :client, :request);');
			$attr = [
				'timestamp'=>microtime(true),
				'uuid'=>preg_replace_callback(
					'/x|y/',
					function($m) {
						return dechex($m[0] === 'x' ? random_int(0, 15) : random_int(8, 11));
					},
					'xxxxxxxx_xxxx_4xxx_yxxx_xxxxxxxxxxxx'
				),
				'client'=>$_SERVER['REMOTE_ADDR'],
				'request'=>$reqip,
			];
			if(! $stm->execute($attr)){
				throw new \Exception('SQL Error');
			}
		} catch (\Exception $th) {
			error_log($th->getMessage());
		}
	}

	function main(){
		$result = [
			'meta' => [
				'version' => 2,
				'runtime' => [
					'hash' => [
						'md5' => md5(md5_file(__FILE__, TRUE)),
						'sha1' => sha1(sha1_file(__FILE__, TRUE)),
						'sha256' => hash('sha256', hash_file('sha256', __FILE__, TRUE)),
					],
					'version' => dechex(filemtime(__FILE__)),
				],
				'issued_at'=>[
					'timestamp'=>time(),
					'description'=>date('Y/m/d H:i:s T'),
					'timezone'=>date_default_timezone_get(),
					'diffgmt'=>date('O'),
				]
			],
			'result' => [
				'result'=>[
					'result'=>null,
					'detail'=>null,
				],
				'request'=>[
					'request'=>null,
					'detail'=>null,
				]
			],
			'documents' => [
				'github_url' => 'https://github.com/n138-kz/isJP',
				'database_url' => [
					self::IPV4_FETUS_JP,
					self::IPV4_FETUS_EN,
				],
			],
			'usage' => [
				$this->concat([$_SERVER['REQUEST_SCHEME'], '://', $_SERVER['HTTP_HOST'], preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']), ''])
			],
			'api' => [
				'use' => count($this->get_logdb()),
				'limit' => $this->config['internal']['api']['ratelimit'],
			],
		];

		if($result['api']['use']>$this->config['internal']['api']['ratelimit']){
			$result['result']['result']['detail']='Reached the API Rate limit. Please refer the documents.';

			http_response_code(429);
			return json_encode( $result, self::FLAG_JSON_ENCODE);
		}

		/* リクエストパラメータ `ip` に値を持ってたらそれに置き換える */
		$reqip = $_SERVER['REMOTE_ADDR'];
		if ( isset( $_GET['ip'] ) && $_GET['ip'] != '' ) {
			$reqip = $_GET['ip'];
		}

		$result['result']['result']=$this->isJP($reqip);
		$result['result']['request']=[
			'request'=>$reqip,
			'detail'=>gethostbyaddr($reqip),
		];
		array_push($result['usage'],
			$this->concat([$_SERVER['REQUEST_SCHEME'], '://', $_SERVER['HTTP_HOST'], preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']), '?ip=', $reqip, ''])
		);

		$this->put_logdb($reqip);
		return json_encode( $result, self::FLAG_JSON_ENCODE);
	}
}

$api = new isJP();
echo $api->main();
