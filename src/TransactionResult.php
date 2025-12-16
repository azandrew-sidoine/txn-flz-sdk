<?php

namespace Drewlabs\Txn\Flz;

use Drewlabs\Txn\ProcessTransactionResultInterface;

class TransactionResult implements ProcessTransactionResultInterface
{
    /** @var string */
    private $ref;

    /** @var bool */
    private $processed;

    /** @var string */
    private $status;

    /** @var ?string */
    private $message;

    /** @var ?string */
    private $payment_ref;

    /** @var ?string */
    private $at;
    
    /**
     * transaction result constructor
     * 
     * @param string $ref 
     * @param bool $processed 
     * @param int|string $status 
     * @param null|string $message 
     * @param null|string $payment_ref 
     * @param null|string $at
     * 
     */
    public function __construct(string $ref, bool $processed, int|string $status, ?string $message, ?string $payment_ref, ?string $at = null) {
        if ($processed === true && (is_null($payment_ref) || is_null($at))) {
            throw new \RuntimeException('transaction result with processed property equals true, requires payment reference an date, None given');
        }
        $this->ref = $ref;
        $this->processed = $processed;
        $this->status = (string)$status;
        $this->message = $message;
        $this->payment_ref = $payment_ref;
        $this->at = $at;
    }

    public function isValidated()
    {
        return $this->processed;
    }

    public function getReference()
    {
        return $this->ref;
    }

    public function getProcessorReference()
    {
        return $this->payment_ref;
    }

    public function getStatusText()
    {
        return $this->message;
    }

    public function processedAt()
    {
        $timestamp = isset($this->at) ? strtotime($this->at) : time();
        if ($timestamp !== false) {
            $dateTime = new \DateTimeImmutable;
            $dateTime->setTimestamp($timestamp);
            return $dateTime;
        }

        return new \DateTimeImmutable;
    }

    public function getResponse()
    {
        return [
            'code' => $this->status,
            'message' => $this->message,
            'transactionId' => $this->payment_ref,
        ];
    }
}