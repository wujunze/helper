<?php
namespace kuiper\helper\fixtures;

use kuiper\helper\JsonSerializeTrait;

class JsonObject implements \JsonSerializable
{
    use JsonSerializeTrait;

    private $user_id;

    private $userName;

    public function __construct($id, $name)
    {
        $this->user_id = $id;
        $this->userName = $name;
    }
}
