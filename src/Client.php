<?php

namespace Drewlabs\Txn\Flooz;

use Drewlabs\Flz\Contracts\MerchantInterface;
use Drewlabs\Flz\Contracts\RequestClientInterface;
use Drewlabs\Flz\Debit;
use Drewlabs\Flz\DebitStatusResult;
use Drewlabs\Txn\OneWayTransactionProcessorInterface;
use Drewlabs\Txn\ProcessorLibraryInterface;
use Drewlabs\Txn\TransactionalProcessorLibraryInterface;
use Drewlabs\Txn\TransactionPaymentInterface;
use Drewlabs\Txn\TransactionResultListener;

class Client implements ProcessorLibraryInterface, OneWayTransactionProcessorInterface, TransactionalProcessorLibraryInterface
{

    /** @var RequestClientInterface */
    private $client;

    /** @var MerchantInterface */
    private $merchant;

    /** @var array list of transaction response listeners. */
    private $responseListeners = [];

    /**
     * creates new flz txn client instance
     * 
     * @param MerchantInterface $merchant 
     * @param RequestClientInterface $client 
     * @return void 
     */
    public function __construct(MerchantInterface $merchant, RequestClientInterface $client)
    {
        $this->merchant = $merchant;
        $this->client = $client;
    }

    public function toProcessTransactionResult($response)
    {

        $result = DebitStatusResult::fromJson($response);

        if (is_null($metadata = $result->getMetadata())) {
            return null;
        }

        return new TransactionResult(
            $metadata->getTxnReference(),
            $result->getStatus(),
            $result->getMessage(),
            $metadata->getFloozReference(),
            $metadata->getDate()
        );
    }

    public function processTransaction(TransactionPaymentInterface $transaction)
    {
        $debit = Debit::new()
            ->withAmount($transaction->getValue())
            ->withCustomerId($transaction->getFrom())
            ->withMerchantId($this->merchant->getAddress())
            ->withMerchantKey($this->merchant->getCode())
            ->withMerchantName($this->merchant->getName())
            ->withTxnReference($transaction->getReference());

        $response = $this->client->sendRequest($debit);
        return $response->isOk();
    }

    public function addTransactionResponseLister($callback)
    {
        if ($callback instanceof \Closure || $callback instanceof TransactionResultListener) {
            $this->responseListeners[] = $callback;
        }
    }

    public function requestOTP(string $payeerid)
    {
        throw new \BadFunctionCallException('Flz:Client does not support otp request implementation');
    }
}
