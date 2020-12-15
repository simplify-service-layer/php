<?php

namespace Dbwhddn10\FService\Cloud;

use Dbwhddn10\FService\Service;
use Evernote\Client;

class EvernoteNoteService extends Service
{
    public static function getArrBindNames()
    {
        return [];
    }

    public static function getArrCallbackLists()
    {
        return [];
    }

    public static function getArrLoaders()
    {
        return [
            'result' => function ($token) {

                return new Client($token, $sandbox=false);
            },
        ];
    }

    public static function getArrPromiseLists()
    {
        return [];
    }

    public static function getArrRuleLists()
    {
        return [];
    }

    public static function getArrTraits()
    {
        return [];
    }
}
