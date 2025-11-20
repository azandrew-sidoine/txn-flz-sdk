<?php

namespace Drewlabs\Txn\Flooz;

use Drewlabs\Txn\ProcessTransactionResultInterface;

class TransactionResult implements ProcessTransactionResultInterface
{
    /** @var string */
    private $ref;

    /** @var string|int */
    private $code;

    /** @var string */
    private $message;

    /** @var string|int */
    private $pTxnId;

    /** @var int|string */
    private $processedAt;

    /**
     * @param mixed       $code
     * @param mixed       $pTxnId
     * @param string|null $processedAt
     */
    public function __construct(
        string $ref,
        $code,
        string $message,
        $pTxnId,
        $processedAt = null
    ) {
        $this->ref = $ref;
        $this->code = $code;
        $this->message = $message;
        $this->pTxnId = $pTxnId;
        $this->processedAt = $processedAt;
    }

    public function isValidated()
    {
        return 0 === (int) $this->code;
    }

    public function getProcessorReference()
    {
        return $this->pTxnId;
    }

    public function getReference()
    {
        return $this->ref;
    }

    public function processedAt()
    {
        return \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', isset($this->processedAt) ? strtotime($this->processedAt) : time()));
    }

    public function getStatusText()
    {
        return $this->message;
    }

    public function getResponse()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'transactionId' => $this->pTxnId,
        ];
    }
}