<?php
namespace kuiper\helper;

class DataDumperTest extends TestCase
{
    /**
     * @dataProvider data
     */
    public function testLoad($data, $content, $format)
    {
        $this->assertEquals(
            $data,
            DataDumper::load($content, $format)
        );
    }

    /**
     * @dataProvider data
     */
    public function testDump($data, $content, $format)
    {
        $this->assertEquals(
            $content,
            trim(DataDumper::dump($data, $format, false))
        );
    }

    public function data()
    {
        return [
            [[1], '[1]', 'json'],
            [[1], '- 1', 'yaml'],
            [[1], "array (\n  0 => 1,\n)", 'php'],
        ];
    }
}
