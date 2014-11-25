<?php

/**
 * Project:     DocodocoJP: どこどこJP アクセスライブラリー
 * File:        docodoco.class.php
 * 
 * @link        http://www.docodoco.jp
 * @copyright   2010 - Cyber Area Research,Inc.
 * @support     support@arearesearch.co.jp
 * @author      Daisuke Miura <daisuke@arearesearch.co.jp>
 * @author      Tatsuya Tonooka <tatsuya@arearesearch.co.jp>
 * @package     DocodocoJP
 * @version     1.0.4
 * @last update 2013/05/09
 * @PHP version 5.2.x
 * @license     GNU Lesser General Public License (LGPL)
 */

class DocodocoJP {
    
    
    /**
     * リクエスト設定
     * @var array
     */
    protected $Config;
    
    
    /**
     * 接続ステータス
     * @var array
     */
    protected $Status;
    
    
    /**
     * 追加システム認識UA
     * @var string
     */
    protected $AddAgent = "car-dcph/1.0";
    
    
    
    /**
    * コンストラクタ
    * 
    * @param string $key1 APIキー1
    * @param string $key2 APIキー2
    * @param boolean $exe 自動実行 default:FALSE
    * @return object
    * @access public
    * 
    * 2010/09/02 @param $exe の追加
    */
    public function __construct( $key1 = "", $key2 = "", $exe = "" ) {
        $this->Config  = array( "DococoKey1" => "", "DococoKey2" => "", "SearchIP" => "", "CharSet" => "utf8" );
        $this->SetKey1( $key1 );
        $this->SetKey2( $key2 );
        if ( !is_bool( $exe )) {    $autoexe    = FALSE;    }
        if ( $exe && $this->Config["DococoKey1"] && $this->Config["DococoKey2"] ) { $this->GetAttribute();  }
    }
    
    
    /**
     * APIキー1のセット
     * 
     * @param string $apikey APIキー1
     * @return boolean 成否
     * @access public
     */
    public function SetKey1( $apikey ) {
        if ( empty( $apikey )) {    return  FALSE;  }
        $this->Config["DococoKey1"] = $apikey;
        return  TRUE;
    }
    
    
    /**
     * APIキー2のセット
     * 
     * @param string $apikey APIキー2
     * @return boolean 成否
     * @access public
     */
    public function SetKey2( $apikey ) {
        if ( empty( $apikey )) {    return  FALSE;  }
        $this->Config["DococoKey2"] = $apikey;
        return  TRUE;
    }
    
    
    /**
     * APIキーのセット
     * 
     * @param array $apikey APIキー（ key1、key2 ）
     * @return boolean 成否
     * @access public
     */
    public function SetKey( $apikey ) {
        if ( !is_array( $apikey )) {    return  FALSE;  }
        foreach ( $apikey as $key => $value ) {
            if ( preg_match( "/^key[12]$/", $key )) {
                $result[]   = $this->{"Set".$key}( $value );
            }
        }
        return  ( !in_array( FALSE, $result ) && 2 == count( $result ) ) ? TRUE : FALSE;
    }
    
    
    /**
     * 検索対象IPアドレスのセット
     * 
     * @param string $ip IPアドレス
     * @return boolean 成否
     * @access public
     */
    public function SetIp( $ip ) {
        if ( preg_match( "/^(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])$/", $ip )) {
            $this->Config["SearchIP"]   = $ip;
            return  TRUE;
        }
        return  FALSE;
    }
    
    
    /**
     * 文字コードのセット
     * 
     * @param string $character 文字コード（jis、sjis、eucjp、utf8）
     * @return boolean 成否
     * @access public
     */
    public function SetChar( $character ) {
        if ( empty( $character )) { return  FALSE;  }
        $character  = strtolower( str_replace( array( "-", "_" ), "", $character ));
        if ( $character == "shiftjis" ) {   $character  = "sjis";   }
        if ( preg_match( "/^jis|sjis|eucjp|utf8/", $character )) {
            $this->Config["CharSet"]    = $character;
            return  TRUE;
        }
        return  FALSE;
    }
    
    
    /**
     * リクエスト設定のセット
     * 
     * @param array $config 設定（key1、key2、ip、char）
     * @return boolean 成否
     * @access public
     */
    public function SetConfig( $config ) {
        if ( !is_array( $config )) {    return  FALSE;  }
        foreach ( $config as $key => $value ) {
            if ( method_exists( $this, "Set".$key )) {  $result[]   = $this->{"Set".$key}( $value );    }
        }
        return  ( !in_array( FALSE, $result ) && count( $config ) == count( $result ) ) ? TRUE : FALSE;
    }
    
    
    /**
     * どこどこJPの値取得
     * 
     * @param string $return array 結果を連想配列で要求する
     * @return mixed 成否/結果
     * @access public
     * 
     * 2010/09/02 リクエスト時にヘッダ送信追加
     * 2011/08/24 Refererの修正
     * 2013/05/09 リクエストURLのバージョン変更
     */
    public function GetAttribute( $return = "" ) {
        if ( !$this->Config["DococoKey1"] || !$this->Config["DococoKey2"] ) {   return  FALSE;  }
        $url    = "http://api.docodoco.jp/v4/search?key1=".$this->Config["DococoKey1"]."&key2=".$this->Config["DococoKey2"];
        $referer= ( getenv( "HTTPS" ) ? "https://" : "http://" ).getenv( "HTTP_HOST" ).getenv( "REQUEST_URI" );
        if ( $this->Config["SearchIP"] ) {  $url .= "&ipadr=".$this->Config["SearchIP"];    }
        $opts   = array( "http" => array( "method" => "GET",
                                          "header" => "Referer: ".$referer."\r\n".
                                                      "User-Agent: ".getenv( "HTTP_USER_AGENT" )."(".$this->AddAgent.")\r\n"
                                        ));
        $context    = stream_context_create( $opts );
        $xml        = simplexml_load_string( file_get_contents( $url, false, $context ));
        
        if ( $xml->status ) {
            $this->Status["code"]       = (int)$xml->status;
            $this->Status["message"]    = (string)$xml->message;
            return  FALSE;
        }
        else {
            if (isset( $xml ) && is_object( $xml )) {
                foreach ( $xml as $key => $value ) {
                    $this->{$key}   = mb_convert_encoding( (string)$value, $this->Config["CharSet"], "UTF-8" );
                }
            }
            if ( $this->IP ) {
                $this->Status   = array( "code" => 200, "message" => "OK" );
            }
            else {
                $this->Status   = array( "code" => 404, "message" => "Not Found" );
                return  FALSE;
            }
        }
        return  ( $return == "array" ) ? $this->GetArray() : TRUE;
    }
    
    
    /**
     * 判定結果を連想配列で取り出す
     * 
     * @return array 判定結果
     * @access public
     * 
     * 2010/09/02 レスポンスが取れてない時の処理の修正
     * 2013/05/09 どこどこJP Ver.4.0に対応.
     */
    public function GetArray() {
        if ( $this->GetStatusCode() ) {
            $tmp    = (array)$this;
            foreach ( $tmp as $key => $value ) {
                if ( strpos($key,"\0*\0") !== false ) {
                    unset($tmp[$key]);
                }
            }
            return $tmp;
        } else {
            $this->Status   = array( "code" => 500,"message" => "Internal Server Error（Does not execute the function）" );
        }
        return  FALSE;
    }
    
    
    /**
     * ステータスコードの取得
     * 
     * @return integer ステータスコード
     * @access public
     */
    public function GetStatusCode() {
        return  $this->Status["code"];
    }
    
    
    /**
     * ステータスメッセージの取得
     * 
     * @return string ステータスメッセージ
     * @access public
     */
    public function GetStatusMessage() {
        return  $this->Status["message"];
    }
    
    
    /**
     * ステータスの取得
     * 
     * @return array ステータス
     * @access public
     */
    public function GetStatus() {
        return  $this->Status;
    }
    
    
}
?>