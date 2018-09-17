<?php

/*
 * This file is part of the jimchen/identity-verify.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Tests\Gateways;

use JimChen\Identity\Exceptions\GatewayErrorException;
use JimChen\Identity\Gateways\JuheGateway;
use JimChen\Identity\Support\Config;
use PHPUnit\Framework\TestCase;

class JuheGatewayTest extends TestCase
{
    public function testVerify()
    {
        $config = [
            'openid' => 'juhe12345678',
            'key' => 'mock-api-key',
        ];

        $signParams = [
            'openid' => 'juhe12345678',
            'key' => 'mock-api-key',
            'idcard' => '440701199909091672',
            'realname' => '张三',
        ];

        $sign = md5(join('', $signParams));

        $gateway = \Mockery::mock(JuheGateway::class, [$config])->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertTrue($gateway->isVerifySignature());
        $this->assertSame($sign, $gateway->generateSign($signParams));

        $gateway = \Mockery::mock(JuheGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('getUrl')->withNoArgs()->andReturn(JuheGateway::ENDPOINT_SIGN_URL);
        $gateway->shouldReceive('isVerifySignature')->withNoArgs()->andReturn(true);
        $gateway->shouldReceive('generateSign')->with($signParams)->andReturn($sign);

        $gateway->shouldReceive('request')->with(
            'POST',
            JuheGateway::ENDPOINT_SIGN_URL,
            [
                'form_params' => [
                    'key' => 'mock-api-key',
                    'realname' => '张三',
                    'idcard' => '440701199909091672',
                    'sign' => $gateway->generateSign($signParams),
                ],
                'exceptions' => false,
            ]
        )->andReturn([
            'error_code' => 0,
            'reason' => '成功',
            'result' => [
                'realname' => '张三',
                'idcard' => '440701199909091672',
                'res' => 1,
                'orderid' => 'J103201712261751495244',
            ],
        ], [
            'error_code' => 10001,
            'reason' => '错误的请求KEY',
        ], [
            'error_code' => 0,
            'reason' => '成功',
            'result' => [
                'realname' => '张三',
                'idcard' => '440701199909091672',
                'res' => 2,
                'orderid' => 'J103201712261751495244',
            ],
        ])->times(2);

        $config = new Config($config);

        $this->assertSame([
            'realname' => '张三',
            'idcard' => '440701199909091672',
            'res' => 1,
            'orderid' => 'J103201712261751495244',
        ], $gateway->verify('张三', '440701199909091672', $config));

        $this->setExpectedException(GatewayErrorException::class, '错误的请求KEY');
        $gateway->verify('张三', '440701199909091672', $config);
    }
}
