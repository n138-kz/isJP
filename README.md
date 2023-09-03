# isJP

## Futures

指定した(もしくは自分自身の)IPアドレスが日本国内のアドレスか判断します。

## Detail

- デフォルトで自分自身の接続元IPv4アドレスを用いてテストします。
- IPv4アドレスデータベースは https://ipv4.fetus.jp を基に設定しています。
- 内部データベースは **テキストファイル** です。（せめてsqliteにしときゃよかったって後悔してる）
- [RFC 1918](https://tools.ietf.org/html/rfc1918) で定義されている IPv4プライベートアドレス はサポートしていません。
- [RFC 4193](https://tools.ietf.org/html/rfc4193) で定義されている IPv6ユニキャストアドレス はサポートしていません。
- [RFC 2460](https://tools.ietf.org/html/rfc2460) で定義されている IPv6アドレス はサポートしていません。

## How to use

サーバの設定で `DirectoryIndex index.php` を設定している場合はファイル名を省略できます。もしくはファイル名を置換してください。

```XHR
GET /isJP/
```

```XHR
GET /isJP/index.php
```

## Supported Web Server

- PHP: >=5.4
- Apache: >= 2.4

## Requires

- HTTP WEB Server(Apacheなど)
- PHP
