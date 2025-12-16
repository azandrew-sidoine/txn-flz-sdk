<?php

namespace Drewlabs\Txn\Flz;

use Drewlabs\Flz\Contracts\MerchantInterface;
use Drewlabs\Flz\Contracts\TransactionClientInterface;
use Drewlabs\Flz\Debit;
use Drewlabs\Flz\DebitStatusResult;
use Drewlabs\Txn\OneWayTransactionProcessorInterface;
use Drewlabs\Txn\ProcessorLibraryInterface;
use Drewlabs\Txn\TransactionPaymentInterface;
use Drewlabs\Txn\TransactionResultListener;

class Client implements ProcessorLibraryInterface, OneWayTransactionProcessorInterface
{

    /** @var TransactionClientInterface */
    private $client;

    /** @var MerchantInterface */
    private $merchant;

    /** @var array<TransactionResultListener> list of transaction response listeners. */
    private $responseListeners = [];

    /**
     * creates new flz txn client instance
     * 
     * @param MerchantInterface $merchant 
     * @param TransactionClientInterface $client 
     * @return void 
     */
    public function __construct(MerchantInterface $merchant, TransactionClientInterface $client)
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

        $value = $this->client->checkTransaction($result->getOrderRef(), $this->merchant->getAddress());
        if (is_null($value)) {
            return null;
        }

        return new TransactionResult(
            $value->getOrderRef() ?? $result->getOrderRef(),
            $value->isProcessed(),
            $value->getStatus(),
            $value->getReasonPhrase(),
            $value->getPaymentRef(),
            $metadata->getDate()
        );
    }

    public function processTransaction(TransactionPaymentInterface $transaction)
    {
        $ref = $transaction->getReference();
        $from = str_replace(['+', '-', '_'], '', $transaction->getFrom());

        $debit = Debit::new()
            ->withAmount($transaction->getValue())
            ->withCustomerId($from)
            ->withMerchantId($this->merchant->getAddress())
            ->withMerchantKey($this->merchant->getCode())
            ->withMerchantName($this->merchant->getName())
            ->withTxnReference($ref);

        $response = $this->client->sendRequest($debit);

        // invoke response listeners with a pending transaction result
        if (!empty($this->responseListeners)) {
            foreach ($this->responseListeners as $callback) {
                $callback(new TransactionResult(
                    $ref,
                    false,
                    4,
                    'Pending transaction',
                    null,
                    null
                ));
            }
        }

        return $response->isOk();
    }

    public function addTransactionResponseLister($callback)
    {
        if ($callback instanceof \Closure || $callback instanceof TransactionResultListener) {
            $this->responseListeners[] = $callback;
        }
    }
}
