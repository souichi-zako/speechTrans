<?php

class Lang {
    const JAPANESE ="ja";
    const KOREAN ="ko";
    const ENGLISH ="en";
    const CHINESE ="zh-CN";
    const INDONESIAN ="id";
    const TAIWANESE ="zh-TW";
    const VIETNAMESE ="vi";
    const FRENCH ="fr";

    //定数の値を配列で取得する
    public static function getConstants() {
        $oClass = new ReflectionClass( __CLASS__ );

        return $oClass->getConstants();
    }

    // 変換先言語の値を配列で取得する
    public static function getMainLangList() {
        return [
            self::JAPANESE,
            self::KOREAN,
            self::ENGLISH
        ];
    }
}

class LangCodeSet {
    // BCP 47言語タグへの変換セット
    const BCP_47_LIST = [
        Lang::JAPANESE => "ja-JP",
        Lang::KOREAN => "ko-KR",
        Lang::ENGLISH => "en-US",
        Lang::CHINESE => "zh-CN",
        Lang::INDONESIAN => "id-ID",
        Lang::TAIWANESE => "zh-TW",
        Lang::VIETNAMESE => "vi-VN",
        Lang::FRENCH => "fr-FR",
    ];
}
