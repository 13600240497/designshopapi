<?php
namespace Tests\Unit\Other;

use Tests\AbstractTestCase;

class SrcData
{
    private $data;

    public function __construct()
    {
        $this->data = [
            'user_list' => [
                '101' => ['name' => 'Jack', 'age' => 30]
            ]
        ];
    }

    public function &getDataRefer($key)
    {
        return $this->data[$key];
    }

    public function setName($name)
    {
        $this->data['user_list']['101']['name'] = $name;
    }
}

class ReferData
{
    private $data = [];

    public function setData($key, $data)
    {
        $this->data[$key] = $data;
    }

    public function getData($key)
    {
        return $this->data[$key];
    }
}


class ReferTest extends AbstractTestCase
{
    public function testRefer()
    {
        $src = new SrcData();
        $usersRefer = & $src->getDataRefer('user_list');
        $transUsers = [];
        $transUsers['101'] = & $usersRefer['101'];

        $key = 'users';
        $name = 'Tom';
        $refer = new ReferData();
        $refer->setData($key, $transUsers);

        $src->setName($name);
        $userInfo = $refer->getData($key);
        print_r($userInfo);
        $this->assertSame($name, $userInfo['101']['name']);
    }
}
