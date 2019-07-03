<?php

namespace kuiper\helper;

use kuiper\helper\fixtures\User;

/**
 * TestCase for Arrays.
 */
class ArraysTest extends TestCase
{
    public function testFetch()
    {
        $arr = ['foo' => 1];
        $this->assertEquals(Arrays::fetch($arr, 'foo'), 1, 'key exists');
        $this->assertEquals(Arrays::fetch($arr, 'bar'), null, 'key does not exists');
        $this->assertEquals(Arrays::fetch($arr, 'bar', 10), 10, 'key does not exists and with default value');
    }

    public function testPull()
    {
        $arr = [['name' => 'john'], ['name' => 'jim']];
        $this->assertEquals(Arrays::pull($arr, 'name'), ['john', 'jim']);

        $objs = array_map(function ($a) {
            return (object) $a;
        }, $arr);
        $this->assertEquals(Arrays::pull($objs, 'name', Arrays::OBJ), ['john', 'jim']);

        $users = array_map(function ($a) {
            return new User($a['name']);
        }, $arr);
        $this->assertEquals(Arrays::pull($users, 'name', Arrays::GETTER), ['john', 'jim']);

        $arr = ['john' => [1, 2], 'jim' => [3, 4]];
        $this->assertEquals([2, 4], Arrays::pull($arr, 1));
    }

    public function testAssoc()
    {
        $arr = [['name' => 'john'], ['name' => 'jim']];
        $this->assertEquals(Arrays::assoc($arr, 'name'), [
            'john' => ['name' => 'john'],
            'jim' => ['name' => 'jim'],
        ]);

        $objs = array_map(function ($a) {
            return (object) $a;
        }, $arr);
        $this->assertEquals(Arrays::assoc($objs, 'name', Arrays::OBJ), [
            'john' => $objs[0],
            'jim' => $objs[1],
        ]);

        $users = array_map(function ($a) {
            return new User($a['name']);
        }, $arr);
        $this->assertEquals(Arrays::assoc($users, 'name', Arrays::GETTER), [
            'john' => $users[0],
            'jim' => $users[1],
        ]);
    }

    public function testExclude()
    {
        $arr = ['foo' => 1, 'bar' => 2];
        $this->assertEquals(Arrays::exclude($arr, ['foo']), ['bar' => 2]);
    }

    public function testRename()
    {
        $arr = ['foo' => 1, 'bar' => 2];
        $this->assertEquals(Arrays::rename($arr, ['foo' => 'baz']), ['baz' => 1, 'bar' => 2]);
    }

    public function testSelectArray()
    {
        $arr = ['foo' => 1, 'bar' => 2];
        $this->assertEquals(Arrays::select($arr, ['foo']), ['foo' => 1]);
    }

    public function testSelectObject()
    {
        $arr = (object) ['foo' => 1, 'bar' => 2];
        $this->assertEquals(Arrays::select($arr, ['foo'], Arrays::OBJ), ['foo' => 1]);
    }

    public function testSelectGetter()
    {
        $user = new User('john');
        $this->assertEquals(Arrays::select($user, ['name'], Arrays::GETTER), ['name' => 'john']);
    }

    public function testFilter()
    {
        $arr = ['foo' => 0, 'bar' => '', 'baz' => 1, 'bzz' => null];
        $this->assertEquals(Arrays::filter($arr), [
             'foo' => 0, 'bar' => '', 'baz' => 1,
        ]);
    }

    public function testSorter()
    {
        $arr = [['name' => 'john'], ['name' => 'jim']];
        $users = array_map(function ($a) {
            return new User($a['name']);
        }, $arr);

        usort($users, Arrays::sorter('name', 'strcmp', Arrays::GETTER));
        $this->assertEquals($users[0]->name, 'jim');
    }

    public function testAssignPublic()
    {
        $user = new User('john');
        $attrs = ['age' => 10, 'name' => 'mary', 'gender' => 'male', 'lastName' => 'He'];
        Arrays::assign($user, $attrs);
        $this->assertEquals('mary', $user->name);
        $this->assertEquals(10, $user->getAge());
        $this->assertAttributeSame(null, 'gender', $user);
        $this->assertTrue(!isset($user->lastName));
        // print_r($user);
    }

    public function testAssignPrivate()
    {
        $user = new User('john');
        $attrs = ['age' => 10, 'name' => 'mary', 'gender' => 'male', 'lastName' => 'He'];
        Arrays::assign($user, $attrs, false);
        $this->assertEquals('mary', $user->name);
        $this->assertEquals(10, $user->getAge());
        $this->assertAttributeSame('male', 'gender', $user);
        $this->assertTrue(!isset($user->lastName));
    }

    public function testToArray()
    {
        $user = new User('john');
        $user->setFemale(false);
        $this->assertEquals(Arrays::toArray($user), ['name' => 'john', 'age' => null, 'female' => false]);
        $this->assertEquals(Arrays::toArray($user, false), ['name' => 'john']);
    }

    public function testRecursiveToArray()
    {
        $user = new User('john');
        $company = new \stdClass();
        $company->employees = [$user];

        $this->assertEquals([
            'employees' => [
                [
                    'name' => 'john',
                    'age' => null,
                    'female' => null,
                ],
            ],
        ], $ret = Arrays::toArray($company, true, false, true));
    }

    public function testMapKeys()
    {
        $arr = Arrays::mapKeys(['fooId' => 1], [Text::class, 'uncamelize']);
        $this->assertEquals(['foo_id' => 1], $arr);

        $ret = Arrays::mapKeys($arr, function ($key) {
            return 'prefixed_'.$key;
        });
        $this->assertEquals(['foo_id' => 1], $arr);
        $this->assertEquals(['prefixed_foo_id' => 1], $ret);
    }
}
