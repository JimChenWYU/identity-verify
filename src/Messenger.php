<?php

/*
 * This file is part of the jimchen/identity-verify.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\Identity;

use JimChen\Identity\Exceptions\NoGatewayAvailableException;

/**
 * Class Messenger.
 */
class Messenger
{
    /**
     * @var Identity
     */
    protected $identity;

    /**
     * Messenger constructor.
     *
     * @param Identity $identity
     */
    public function __construct(Identity $identity)
    {
        $this->identity = $identity;
    }

    public function verify($realName, $idCard, array $gateways = [])
    {
        $results = [];
        $isSuccessful = false;

        foreach ($gateways as $gateway => $config) {
            try {
                $results[$gateway] = [
                    'gateway' => $gateway,
                    'status' => true,
                    'result' => $this->identity->gateway($gateway)->verify($realName, $idCard, $config),
                ];
                $isSuccessful = true;

                break;
            } catch (\Exception $e) {
                $results[$gateway] = [
                    'gateway' => $gateway,
                    'status' => false,
                    'exception' => $e,
                ];
            } catch (\Throwable $e) {
                $results[$gateway] = [
                    'gateway' => $gateway,
                    'status' => false,
                    'exception' => $e,
                ];
            }
        }

        if (!$isSuccessful) {
            throw new NoGatewayAvailableException($results);
        }

        return $results;
    }
}
