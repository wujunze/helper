<?php
namespace kuiper\helper;

class DotArrayTest extends TestCase
{
    public function createDotArray()
    {
        return new DotArray([
            'int' => 1,
            'array' => [1, 2, [3, 4]],
            'map' => [
                'k1' => 'v1',
                'k2' => 'v2'
            ],
        ]);
    }
    
    public function testIterator()
    {
        $array = $this->createDotArray();
        $this->assertEquals([
            'int' => 1,
            'array[0]' => 1,
            'array[1]' => 2,
            'array[2][0]' => 3,
            'array[2][1]' => 4,
            'map.k1' => 'v1',
            'map.k2' => 'v2',
        ], $array->toArray());
    }

    public function testArrayAccess()
    {
        $array = $this->createDotArray();
        foreach ([
            'int' => 1,
            'array[0]' => 1,
            'array[1]' => 2,
            'array[2][0]' => 3,
            'array[2][1]' => 4,
            'map.k1' => 'v1',
            'map.k2' => 'v2',
        ] as $key => $val) {
            $this->assertTrue(isset($array[$key]));
            $this->assertSame($val, $array[$key]);
        }
        $this->assertEquals([
            'k1' => 'v1',
            'k2' => 'v2'
        ], $array['map']);
    }

    public function testArrayUnset()
    {
        $array = $this->createDotArray();
        unset($array['map.k1']);
        $this->assertTrue(!isset($array['map.k1']));
    }

    public function testArraySet()
    {
        $array = $this->createDotArray();
        $array['map.k3'] = 'v3';
        $this->assertSame('v3', $array['map.k3']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage unable to set value for 'int[0]', value of 'int' is not array, got integer
     */
    public function testArraySetException()
    {
        $array = $this->createDotArray();
        $array['int[0]'] = 1;
    }
}
