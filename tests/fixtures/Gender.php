<?php
namespace kuiper\helper\fixtures;

use kuiper\helper\Enum;

class Gender extends Enum
{
    const MALE = 'm';

    const FEMALE = 'f';

    protected static $PROPERTIES = [
        'description' => [
            self::MALE => '男',
            self::FEMALE => '女'
        ],
        'ordinal' => [
            self::MALE => 1,
            self::FEMALE => 2
        ],
        'enName' => [
            self::MALE => 'male'
        ],
    ];
}
