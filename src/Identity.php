<?php

/*
 * This file is part of the jimchen/identity-verify.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\Identity;

use JimChen\Identity\Contracts\GatewayInterface;
use JimChen\Identity\Contracts\StrategyInterface;
use JimChen\Identity\Exceptions\InvalidArgumentException;
use JimChen\Identity\Strategies\OrderStrategy;
use JimChen\Identity\Support\Config;
use RuntimeException;

class Identity
{
    /**
     * @var \JimChen\Identity\Support\Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $defaultGateway;

    /**
     * @var GatewayInterface[]
     */
    protected $gateways = [];

    /**
     * @var StrategyInterface[]
     */
    protected $strategies;

    /**
     * @var \JimChen\Identity\Messenger
     */
    protected $messenger;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);

        if (!empty($config['default'])) {
            $this->setDefaultGateway($config['default']);
        }
    }

    /**
     * Send a message.
     *
     * @param string $realName
     * @param string $idCard
     * @param array  $gateway
     *
     * @return array
     *
     * @throws \JimChen\Identity\Exceptions\NoGatewayAvailableException
     * @throws \JimChen\Identity\Exceptions\InvalidArgumentException
     */
    public function verify($realName, $idCard, array $gateway = [])
    {
        if (empty($gateway)) {
            $gateway = $this->config->get('default.gateways', []);
        }

        return $this->getMessenger()->verify($realName, $idCard, $this->formatGateways($gateway));
    }

    /**
     * Create a gateway.
     *
     * @param string|null $name
     *
     * @return \JimChen\identity\Contracts\GatewayInterface
     *
     * @throws \JimChen\identity\Exceptions\InvalidArgumentException
     */
    public function gateway($name = null)
    {
        $name = $name ?: $this->getDefaultGateway();

        if (!isset($this->gateways[$name])) {
            $this->gateways[$name] = $this->createGateway($name);
        }

        return $this->gateways[$name];
    }

    /**
     * Get a strategy instance.
     *
     * @param string|null $strategy
     *
     * @return \JimChen\Identity\Contracts\StrategyInterface
     *
     * @throws \JimChen\Identity\Exceptions\InvalidArgumentException
     */
    public function strategy($strategy = null)
    {
        if (is_null($strategy)) {
            $strategy = $this->config->get('default.strategy', OrderStrategy::class);
        }

        if (!class_exists($strategy)) {
            $strategy = __NAMESPACE__ . '\Strategies\\' . ucfirst($strategy);
        }

        if (!class_exists($strategy)) {
            throw new InvalidArgumentException("Unsupported strategy \"{$strategy}\"");
        }

        if (empty($this->strategies[$strategy]) || !($this->strategies[$strategy] instanceof StrategyInterface)) {
            $this->strategies[$strategy] = new $strategy($this);
        }

        return $this->strategies[$strategy];
    }

    /**
     * @return \JimChen\identity\Support\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get default gateway name.
     *
     * @return string
     *
     * @throws \RuntimeException if no default gateway configured
     */
    public function getDefaultGateway()
    {
        if (empty($this->defaultGateway)) {
            throw new RuntimeException('No default gateway configured.');
        }

        return $this->defaultGateway;
    }

    /**
     * Set default gateway name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setDefaultGateway($name)
    {
        $this->defaultGateway = $name;

        return $this;
    }

    /**
     * @return \JimChen\identity\Messenger
     */
    public function getMessenger()
    {
        return $this->messenger ?: $this->messenger = new Messenger($this);
    }

    /**
     * Create a new driver instance.
     *
     * @param string $name
     *
     * @return GatewayInterface
     *
     * @throws \JimChen\identity\Exceptions\InvalidArgumentException
     */
    protected function createGateway($name)
    {
        $className = $this->formatGatewayClassName($name);
        $gateway = $this->makeGateway($className, $this->config->get("gateways.{$name}", []));

        if (!($gateway instanceof GatewayInterface)) {
            throw new InvalidArgumentException(sprintf('Gateway "%s" not inherited from %s.', $name,
                GatewayInterface::class));
        }

        return $gateway;
    }

    /**
     * Make gateway instance.
     *
     * @param string $gateway
     * @param array  $config
     *
     * @return \JimChen\identity\Contracts\GatewayInterface
     *
     * @throws \JimChen\identity\Exceptions\InvalidArgumentException
     */
    protected function makeGateway($gateway, $config)
    {
        if (!class_exists($gateway)) {
            throw new InvalidArgumentException(sprintf('Gateway "%s" not exists.', $gateway));
        }

        return new $gateway($config);
    }

    /**
     * Format gateway name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function formatGatewayClassName($name)
    {
        if (class_exists($name)) {
            return $name;
        }

        $name = ucfirst(str_replace(['-', '_', ''], '', $name));

        return __NAMESPACE__ . "\\Gateways\\{$name}Gateway";
    }

    /**
     * @param array $gateways
     *
     * @return array
     *
     * @throws \JimChen\Identity\Exceptions\InvalidArgumentException
     */
    protected function formatGateways(array $gateways = [])
    {
        $formatted = [];

        foreach ($gateways as $gateway => $setting) {
            if (is_int($gateway) && is_string($setting)) {
                $gateway = $setting;
                $setting = [];
            }

            $formatted[$gateway] = $setting;
            $globalSettings = $this->config->get("gateways.{$gateway}", []);

            if (is_string($gateway) && !empty($globalSettings) && is_array($setting)) {
                $formatted[$gateway] = new Config(array_merge($globalSettings, $setting));
            }
        }

        $result = [];

        foreach ($this->strategy()->apply($formatted) as $name) {
            $result[$name] = $formatted[$name];
        }

        return $result;
    }
}
