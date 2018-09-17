<?php

/*
 * This file is part of the jimchen/identity-verify.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\Identity\Gateways;

use JimChen\Identity\Contracts\GatewayInterface;
use JimChen\Identity\Support\Config;

abstract class Gateway implements GatewayInterface
{
    const DEFAULT_TIMEOUT = 5.0;
    /**
     * @var \JimChen\identity\Support\Config
     */
    protected $config;
    /**
     * @var float
     */
    protected $timeout;

    /**
     * Gateway constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * Return timeout.
     *
     * @return int|mixed
     */
    public function getTimeout()
    {
        return $this->timeout ?: $this->timeout = $this->config->get('timeout', self::DEFAULT_TIMEOUT);
    }

    /**
     * Set timeout.
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = floatval($timeout);

        return $this;
    }

    /**
     * @return \JimChen\identity\Support\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param \JimChen\identity\Support\Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return \strtolower(str_replace([__NAMESPACE__.'\\', 'Gateway'], '', \get_class($this)));
    }
}
