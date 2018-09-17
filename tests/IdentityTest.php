<?php

/*
 * This file is part of the jimchen/identity-verify.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Tests;

use JimChen\Identity\Contracts\GatewayInterface;
use JimChen\Identity\Identity;
use JimChen\Identity\Messenger;
use JimChen\Identity\Support\Config;
use PHPUnit\Framework\TestCase;

class IdentityTest extends TestCase
{
    /**
     * @expectedException \JimChen\Identity\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Gateway "JimChen\Identity\Gateways\NotExistsGatewayNameGateway" not exists.
     */
    public function testGateway()
    {
        $identity = new Identity([]);

        $this->assertInstanceOf(GatewayInterface::class, $identity->gateway('error-log'));

        $identity->gateway('NotExistsGatewayName');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No default gateway configured.
     */
    public function testGatewayWithoutDefaultSetting()
    {
        $identity = new Identity([]);

        $identity->gateway();
    }

    /**
     * @expectedException \JimChen\Identity\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Gateway "Tests\DummyInvalidGatewayForTest" not inherited from JimChen\Identity\Contracts\GatewayInterface.
     */
    public function testGatewayWithDefaultSetting()
    {
        $easySms = new Identity(['default' => DummyGatewayForTest::class]);
        $this->assertSame(DummyGatewayForTest::class, $easySms->getDefaultGateway());
        $this->assertInstanceOf(DummyGatewayForTest::class, $easySms->gateway());

        // invalid gateway
        $easySms->setDefaultGateway(DummyInvalidGatewayForTest::class);

        $easySms->gateway();
    }

    public function testVerify()
    {
        $messenger = \Mockery::mock(Messenger::class);
        $messenger->shouldReceive('verify')->with(
            '张三',
            '440701199909091672',
            []
        )->andReturn('verify-result');

        $identity = \Mockery::mock(Identity::class.'[getMessenger]', [['default' => DummyGatewayForTest::class]]);
        $identity->shouldReceive('getMessenger')->andReturn($messenger);

        $this->assertSame('verify-result', $identity->verify('张三', '440701199909091672'));
    }

    public function testGetMessenger()
    {
        $identity = new Identity([]);

        $this->assertInstanceOf(Messenger::class, $identity->getMessenger());
    }

    public function testFormatGateways()
    {
        $config = [
            'gateways' => [
                'foo' => [
                    'a' => 'b',
                ],
                'bar' => [
                    'c' => 'd',
                ],
            ],
        ];

        $identity = \Mockery::mock(Identity::class, [$config])->makePartial()->shouldAllowMockingProtectedMethods();

        // gateway names
        $gateways = $identity->formatGateways(['foo', 'bar']);

        $this->assertCount(2, $gateways);
        $this->arrayHasKey('foo', $gateways);
        $this->arrayHasKey('bar', $gateways);
        $this->assertSame('b', $gateways['foo']->get('a'));
        $this->assertSame('d', $gateways['bar']->get('c'));

        // gateway names && override config
        $gateways = $identity->formatGateways(['foo', 'bar' => ['c' => 'e']]);

        $this->assertCount(2, $gateways);
        $this->arrayHasKey('foo', $gateways);
        $this->arrayHasKey('bar', $gateways);
        $this->assertSame('b', $gateways['foo']->get('a'));
        $this->assertSame('e', $gateways['bar']->get('c'));

        // gateway names && append config
        $gateways = $identity->formatGateways(['foo' => ['f' => 'g'], 'bar' => ['c' => 'e']]);

        $this->assertCount(2, $gateways);
        $this->arrayHasKey('foo', $gateways);
        $this->arrayHasKey('bar', $gateways);
        $this->assertSame('b', $gateways['foo']->get('a'));
        $this->assertSame('g', $gateways['foo']->get('f'));
        $this->assertSame('e', $gateways['bar']->get('c'));
    }
}

class DummyGatewayForTest implements GatewayInterface
{
    public function getName()
    {
        return 'name';
    }

    public function verify($realName, $idCard, Config $config)
    {
        return 'verify-result';
    }
}

class DummyInvalidGatewayForTest
{
    // nothing
}
