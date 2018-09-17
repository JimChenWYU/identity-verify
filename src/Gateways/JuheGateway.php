<?php

/*
 * This file is part of the jimchen/identity-verify.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\Identity\Gateways;

use JimChen\Identity\Support\Config;
use JimChen\Identity\Traits\HasHttpRequest;
use JimChen\Identity\Exceptions\GatewayErrorException;
use JimChen\Identity\Exceptions\InvalidArgumentException;

class JuheGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_COMMON_URL = 'http://op.juhe.cn/idcard/query';

    const ENDPOINT_SIGN_URL = 'http://op.juhe.cn/idcard/verify';

    /**
     * Verify.
     *
     * @param string $realName
     * @param string $idCard
     * @param Config $config
     *
     * @return array
     *
     * @throws GatewayErrorException
     * @throws InvalidArgumentException
     */
    public function verify($realName, $idCard, Config $config)
    {
        $response = $this->request('POST', $this->getUrl(), [
            'form_params' => array_merge([
                'key'      => $config->get('key', ''),
                'realname' => $realName,
                'idcard'   => $idCard,
            ], $this->isSign() ? [
                'sign' => $this->generateSign([
                    'key'      => $config->get('key'),
                    'openid'   => $config->get('openid'),
                    'realname' => $realName,
                    'idcard'   => $idCard,
                ]),
            ] : []),
            'exceptions'  => false,
        ]);

        if (is_string($response)) {
            $response = json_decode($response, true);
        }

        if (0 != $response['error_code']) {
            throw new GatewayErrorException($response['reason'], $response['error_code'], $response);
        }

        return $response['result'];
    }

    protected function getUrl()
    {
        return $this->isSign() ? self::ENDPOINT_SIGN_URL : self::ENDPOINT_COMMON_URL;
    }

    protected function generateSign($params)
    {
        if (!isset($params['key']) || empty($params['key'])) {
            $params['key'] = $this->config->get('key');
        }

        if (!isset($params['openid']) || empty($params['openid'])) {
            $params['openid'] = $this->config->get('openid');
        }

        if (!isset($params['idcard']) || empty($params['idcard'])) {
            throw new InvalidArgumentException('Unknow idcard.');
        }

        if (!isset($params['realname']) || empty($params['realname'])) {
            throw new InvalidArgumentException('Unknow realname.');
        }

        return md5($params['openid'] . $params['key'] . $params['idcard'] . $params['realname']);
    }

    protected function isSign()
    {
        return $this->config->get('is_sign', true);
    }
}
