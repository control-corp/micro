<?php

namespace MicroTest;

use Micro\Application\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    private function getConfig()
    {
        return [
            'a' => 1,
            'b' => [
                'c' => 3,
                'd' => null
            ]
        ];
    }

    private function getConfigFactory($cacheable = false)
    {
        return new Config($this->getConfig(), $cacheable);
    }

    public function testFirstLevel()
    {
        $config = $this->getConfigFactory();

        $this->assertSame(1, $config->get('a'));
    }

    public function testFirstModifiedLevel()
    {
        $config = $this->getConfigFactory();

        $this->assertSame(1, $config->get('a'));

        $config->load(['a' => 'modified']);

        $this->assertSame('modified', $config->get('a'));
    }

    public function testWholeTreeLevel()
    {
        $config = $this->getConfigFactory();

        $this->assertSame([
            'c' => 3,
            'd' => null
        ], $config->get('b'));
    }

    public function testSecondLevel()
    {
        $config = $this->getConfigFactory();

        $this->assertSame(3, $config->get('b.c'));
    }

    public function testSecondModifiedLevel()
    {
        $config = $this->getConfigFactory();

        $this->assertSame(3, $config->get('b.c'));

        $config->load([
            'b' => [
                'c' => 'modified'
            ]
        ]);

        $this->assertSame('modified', $config->get('b.c'));
    }

    public function testSecondNullLevel()
    {
        $config = $this->getConfigFactory();

        $this->assertSame(null, $config->get('b.d', "NOTNULL"));
    }

    public function testFirstUndefinedLevel()
    {
        $config = $this->getConfigFactory();

        $this->assertSame('e', $config->get('e', 'e'));
    }

    public function testSecondUndefinedLevel()
    {
        $config = $this->getConfigFactory();

        $this->assertSame('e', $config->get('b.e', 'e'));
    }

    public function testLoadLevel()
    {
        $config = $this->getConfigFactory();

        $config->load(['e' => 3]);

        $this->assertSame(3, $config->get('e'));
    }

    public function testWholeTree()
    {
        $config = $this->getConfigFactory();

        $this->assertSame($this->getConfig(), $config->get());
    }

    public function testWholeModifiedTree()
    {
        $config = $this->getConfigFactory();

        $config->load(['e' => 3]);

        $this->assertSame($this->getConfig() + ['e' => 3], $config->get());
    }

    public function testCacheableTree()
    {
        $config = $this->getConfigFactory(true);

        $this->assertSame(1, $config->get('a'));

        $config->load(['a' => 'modified']);

        $this->assertSame(1, $config->get('a'));
    }

    public function testCacheableNullTree()
    {
        $config = $this->getConfigFactory(true);

        $this->assertSame(null, $config->get('b.d', "NOTNULL"));

        $config->load(['b' => ['d' => 'modified']]);

        $this->assertSame(null, $config->get('b.d', "NOTNULL"));
    }

    public function testCacheableUndefinedTree()
    {
        $config = $this->getConfigFactory(true);

        $this->assertSame(null, $config->get('undefined'));

        $config->load(['undefined' => 'modified']);

        $this->assertSame(null, $config->get('undefined'));
    }
}