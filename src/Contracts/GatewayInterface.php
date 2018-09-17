<?php

/*
 * This file is part of the jimchen/identity-verify.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\Identity\Contracts;

use JimChen\Identity\Support\Config;

interface GatewayInterface
{
    /**
     * Get gateway name.
     *
     * @return string
     */
    public function getName();

    /**
     * Verify.
     *
     * @param string $realName
     * @param string $idCard
     * @param Config $config
     *
     * @return array
     */
    public function verify($realName, $idCard, Config $config);
}
