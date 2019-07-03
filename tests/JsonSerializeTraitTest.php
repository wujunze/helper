<?php

namespace kuiper\helper;

use kuiper\helper\fixtures\JsonObject;

/**
 * TestCase for Enum
 */
class JsonSerializeTraitTest extends TestCase
{
    public function test()
    {
        $obj = new JsonObject(1, 'foo');
        $this->assertEquals('{"user_id":1,"userName":"foo"}', json_encode($obj));

        $this->assertEquals('{"userId":1,"userName":"foo"}', json_encode($obj->toArray('camelize')));

        $this->assertEquals('{"user_id":1,"user_name":"foo"}', json_encode($obj->toArray('uncamelize')));
    }
}
