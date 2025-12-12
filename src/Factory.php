<?php

namespace Drewlabs\Txn\Flooz;

use Drewlabs\Flz\ApiKeyTokenFactory;
use Drewlabs\Flz\BasicAuthTokenFactory;
use Drewlabs\Flz\Flooz;
use Drewlabs\Flz\Merchant;
use Drewlabs\Libman\Contracts\AuthBasedLibraryConfigInterface;
use Drewlabs\Libman\Contracts\LibraryConfigInterface;
use Drewlabs\Libman\Contracts\LibraryFactoryInterface;
use Drewlabs\Libman\Contracts\WebServiceLibraryConfigInterface;
use Drewlabs\Flz\Contracts\TokenFactoryInterface;

class Factory implements LibraryFactoryInterface
{
    public static function createInstance(LibraryConfigInterface $config)
    {
        $configuration = $config->getConfiguration();
        $host = ($config instanceof WebServiceLibraryConfigInterface) ? ($config->getHost() ?? '') : ($config->getConfiguration()->get('api.host', '') ?? '');
        $hostname = sprintf('%s://%s', parse_url($host, \PHP_URL_SCHEME), parse_url($host, \PHP_URL_HOST));

        // create new client instance
        [$user, $password, $token] = [
            $configuration->get('credentials.user') ?? $configuration->get('api.credentials.user'), 
            $configuration->get('credentials.password') ?? $configuration->get('api.credentials.password'),
            $configuration->get('credentials.token') ?? $configuration->get('api.credentials.token')
        ];

        if (is_null($token)) {
            throw new \RuntimeException('missing crendentials.token configuration value'); 
        }

        if ((is_null($user) || is_null($password)) && $config instanceof AuthBasedLibraryConfigInterface && ($auth = $config->getAuth())) {
            $user = $auth->id();
            $password = $auth->secret();
        }

        /** @var TokenFactoryInterface|null */
        $factory = null;
        
        if (!is_null($user) && !is_null($password) && !is_null($token)) {
            $factory = new BasicAuthTokenFactory($user, $password, $token);
        } else if ((is_null($user) || is_null($password)) && !is_null($token)) {
            $factory = new ApiKeyTokenFactory($token);
        }

        if (is_null($factory)) {
            throw new \RuntimeException('Invalid configuration missing api key, or basic auth credentials.');
        }
        
        $merchantName = $configuration->get('merchant.name', $configuration->get('api.merchant.name'));
        $merchantRef = $configuration->get('merchant.ref', $configuration->get('api.merchant.ref'));
        $merchantMsisdn = $configuration->get('merchant.msisdn', $configuration->get('api.merchant.msisdn'));
        $merchant = new Merchant($merchantMsisdn, $merchantRef, $merchantName);
        $debitAPI  = Flooz::NewDebit($hostname, $factory);

        // returns the contructed client
        return new Client($merchant, $debitAPI);
    }

}