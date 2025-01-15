# [isJP](https://github.com/n138-kz/isJP)

[![pages-build-deployment](https://github.com/n138-kz/isJP/actions/workflows/pages/pages-build-deployment/badge.svg?branch=master)](https://github.com/n138-kz/isJP/actions/workflows/pages/pages-build-deployment)
![GitHub](https://img.shields.io/github/license/n138-kz/isJP)
  
[![GitHub language count](https://img.shields.io/github/languages/count/lkz138/isJP)](README.md)
[![GitHub top language](https://img.shields.io/github/languages/top/lkz138/isJP)](README.md)
[![GitHub commit activity](https://img.shields.io/github/commit-activity/m/lkz138/isJP)](README.md)
[![GitHub last commit](https://img.shields.io/github/last-commit/lkz138/isJP)](README.md)

## Futures

指定した(もしくは自分自身の)IPアドレスが日本国内のアドレスか判断します。

## Detail

- デフォルトで自分自身の接続元IPv4アドレスを用いてテストします。
- IPv4アドレスデータベースは `https://ipv4.fetus.jp` を基に設定しています。
- ~~内部データベースは **テキストファイル** です。（せめてsqliteにしときゃよかったって後悔してる）~~
- 内部データベースは使用しません。毎回神様データにアクセスします。
  - ただし近々実装予定です。 https://ipv4.fetus.jp/about#automation
- [RFC 1918](https://tools.ietf.org/html/rfc1918) で定義されている IPv4プライベートアドレス はサポートしています。([Ver2](https://github.com/n138-kz/isJP/tree/e426bfcebf861a9b9741ecfcd8383b471ad3acd9)以降)
- [RFC 5771](https://tools.ietf.org/html/rfc5771) で定義されている IPv4マルチキャストアドレス はサポートしていません。
- [RFC 4193](https://tools.ietf.org/html/rfc4193) で定義されている IPv6ユニキャストアドレス はサポートしていません。
- [RFC 2460](https://tools.ietf.org/html/rfc2460) で定義されている IPv6アドレス はサポートしていません。

## How to use

サーバの設定で `DirectoryIndex index.php` を設定している場合はファイル名を省略できます。もしくはファイル名を置換してください。

```http
GET /isJP/
```

```http
GET /isJP/index.php
```

## Supported Web Server

- PHP: >=5.4
- Apache: >= 2.4

## Requires

- HTTP WEB Server(Apacheなど)
- PHP

## Database

```sql
CREATE TABLE IF NOT EXISTS isjp (
    "timestamp" double precision NOT NULL,
    uuid TEXT NOT NULL,
    client TEXT NOT NULL,
    request TEXT NOT NULL,
    userid TEXT
);
```
```sql
CREATE TABLE IF NOT EXISTS isjp_authorizeduser (
    "timestamp" double precision NOT NULL,
    uuid TEXT NOT NULL,
    g_userid TEXT NOT NULL UNIQUE,
    g_email TEXT DEFAULT NULL,
    g_name TEXT DEFAULT NULL,
    g_picture TEXT DEFAULT NULL,
    g_given_name TEXT DEFAULT NULL,
    g_family_name TEXT DEFAULT NULL,
    g_locale TEXT DEFAULT NULL
);
```
```sql
CREATE OR REPLACE VIEW isjp_in10min
 AS
 SELECT "timestamp",
    uuid,
    client,
    request
   FROM isjp
   WHERE "timestamp" > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP - interval '10 minute');
```

## 出力データVersionログ

### ver 1

```json
[
    true,
    "0.0.0.0"
]
```
since: [0aaaf2add1efcc74a580cb63e13ffc36aef86d57](https://github.com/n138-kz/isJP/tree/0aaaf2add1efcc74a580cb63e13ffc36aef86d57)

### ver 2

```json
{
    "meta":{
        "version":2,
        "runtime_hash":"ffffffffffffffffffffffffffffffff",
        "runtime_version":"ffffffff",
        "issued_at":{
            "timestamp":1693704010,
            "description":"2023\/09\/03 10:20:10 JST"
        }
    },
    "result":{
        "result":{
            "result":true,
            "detail":"ja_JP"
            }
        ,"request":"183.x.x.252"
    }
}
```
since: [e426bfcebf861a9b9741ecfcd8383b471ad3acd9](https://github.com/n138-kz/isJP/tree/e426bfcebf861a9b9741ecfcd8383b471ad3acd9)
