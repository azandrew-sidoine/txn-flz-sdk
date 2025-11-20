<?php

namespace Drewlabs\Txn\Flooz;

use Drewlabs\Txn\OneWayTransactionProcessorInterface;
use Drewlabs\Txn\ProcessorLibraryInterface;
use Drewlabs\Txn\TransactionalProcessorLibraryInterface;
use Drewlabs\Txn\TransactionPaymentInterface;

class Client implements ProcessorLibraryInterface, OneWayTransactionProcessorInterface, TransactionalProcessorLibraryInterface
{
    public function toProcessTransactionResult($response)
    {
        throw new \Exception('Not implemented');
    }

    public function processTransaction(TransactionPaymentInterface $transaction)
    {
        throw new \Exception('Not implemented');
    }

    public function addTransactionResponseLister($callback)
    {
        throw new \Exception('Not implemented');
    }

    public function requestOTP(string $payeerid)
    {
        throw new \Exception('Not implemented');
    }
    
}