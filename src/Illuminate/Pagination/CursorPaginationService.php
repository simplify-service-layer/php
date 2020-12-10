<?php

namespace Dbwhddn10\FService\Illuminate\Pagination;

use Dbwhddn10\FService\Service;

class CursorPaginationService extends Service
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
            'result' => function ($cursor='', $limit='', $orderByArray, $query) {

                $wheres = [];
                $result = [];

                foreach ( $orderByArray as $column => $direction )
                {
                    if ( empty($cursor) )
                    {
                        break;
                    }

                    if ( $direction == 'asc' )
                    {
                        $wheres[] = [$column, '>', $cursor->{$column}];
                    }
                    else
                    {
                        $wheres[] = [$column, '<', $cursor->{$column}];
                    }
                }

                do
                {
                    $newQuery = clone $query;

                    foreach ( $wheres as $i => $where )
                    {
                        if ( $where == end($wheres) )
                        {
                            $newQuery->where($where[0], $where[1], $where[2]);
                        }
                        else
                        {
                            $newQuery->where($where[0], '=', $where[2]);
                        }
                    }

                    array_pop($wheres);

                    $list   = $newQuery->get();
                    $limit  = $limit - count($list);
                    $result = array_merge($result, $list->all());
                }
                while ( $limit != 0 && count($wheres) != 0 );

                return $query->getModel()->newCollection($result);
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
