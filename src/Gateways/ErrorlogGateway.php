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

class ErrorlogGateway extends Gateway
{
    /**
     * Verify.
     *
     * @param string $realName
     * @param string $idCard
     * @param Config $config
     *
     * @return array
     */
    public function verify($realName, $idCard, Config $config)
    {
        $message = sprintf(
            "[%s] %s\n",
            date('Y-m-d H:i:s'),
            json_encode([
                'idcard'   => $idCard,
                'realname' => $realName,
            ])
        );

        $file = $this->config->get('file', ini_get('error_log'));
        $status = error_log($message, 3, $file);

        return compact('status', 'file');
    }
}
