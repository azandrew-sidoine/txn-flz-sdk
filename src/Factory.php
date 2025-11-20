<?php

namespace Drewlabs\Txn\Flooz;

use Drewlabs\Flz\AuthTokenFactory;
use Drewlabs\Flz\Flooz;
use Drewlabs\Flz\Merchant;
use Drewlabs\Libman\Contracts\AuthBasedLibraryConfigInterface;
use Drewlabs\Libman\Contracts\LibraryConfigInterface;
use Drewlabs\Libman\Contracts\LibraryFactoryInterface;
use Drewlabs\Libman\Contracts\WebServiceLibraryConfigInterface;

class Factory implements LibraryFactoryInterface
{
    public static function createInstance(LibraryConfigInterface $config)
    {
        $configuration = $config->getConfiguration();
        $host = ($config instanceof WebServiceLibraryConfigInterface) ? ($config->getHost() ?? '') : ($config->getConfiguration()->get('api.host', '') ?? '');
        $hostname = sprintf('%s://%s', parse_url($host, \PHP_URL_SCHEME), parse_url($host, \PHP_URL_HOST));

        // Create new client instance
        [$user, $password, $authToken] = [
            $configuration->get('credentials.user') ?? $configuration->get('api.credentials.user'), 
            $configuration->get('credentials.password') ?? $configuration->get('api.credentials.password'),
            $configuration->get('credentials.token') ?? $configuration->get('api.credentials.token')
        ];

        if (is_null($authToken)) {
            throw new \RuntimeException('missing crendentials.token configuration value'); 
        }

        $authTokenFactory = new AuthTokenFactory($authToken);
        if ((is_null($user) || is_null($password)) && $config instanceof AuthBasedLibraryConfigInterface && ($auth = $config->getAuth())) {
            $user = $auth->id();
            $password = $auth->secret();
        }

        $merchantName = $configuration->get('merchant.name', $configuration->get('api.merchant.name'));
        $merchantRef = $configuration->get('merchant.ref', $configuration->get('api.merchant.ref'));
        $merchantMsisdn = $configuration->get('merchant.msisdn', $configuration->get('api.merchant.msisdn'));
        $merchant = new Merchant($merchantMsisdn, $merchantRef, $merchantName);
        $debitAPI  = Flooz::NewDebit($hostname, $authTokenFactory->createToken($user, $password));

        // returns the contructed client
        return new Client($merchant, $debitAPI);
    }

}