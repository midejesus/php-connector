<?php

namespace PhpConnector\Model;

use PhpConnector\Model\CreatePaymentRequest;

class CreatePaymentResponse
{
    private $paymentId;
    private $status;
    private $authorizationId;
    private $tid;
    private $nsu;
    private $acquirer;
    private $code;
    private $message;
    private $delayToAutoSettle;
    private $delayToAutoSettleAfterAntiFraud;
    private $delayToCancel;
    private $maxValue;
    private $retryResponse;

    public function __construct(
        ?string $paymentId,
        string $status,
        ?string $authorizationId,
        string $tid,
        ?string $nsu,
        ?string $acquirer,
        ?string $code,
        ?string $message,
        ?int $delayToAutoSettle,
        ?int $delayToAutoSettleAfterAntiFraud,
        ?int $delayToCancel,
        ?float $maxValue,
        ?self $retryResponse
    ) {
        $this->paymentId = $paymentId;
        $this->status = $status;
        $this->authorizationId = $authorizationId;
        $this->tid = $tid;
        $this->nsu = $nsu;
        $this->acquirer = $acquirer;
        $this->code = $code;
        $this->message = $message;
        $this->delayToAutoSettle = $delayToAutoSettle;
        $this->delayToAutoSettleAfterAntiFraud = $delayToAutoSettleAfterAntiFraud;
        $this->delayToCancel = $delayToCancel;
        $this->maxValue = $maxValue;
        $this->retryResponse = $retryResponse;
    }

    public static function approved(
        CreatePaymentRequest $request,
        $authorizationId,
        $tid,
        $nsu,
        $acquirer,
        $delayToAutoSettle
    ): self
    {
        return new self(
            $request->paymentId(),
            "approved",
            $authorizationId,
            $tid,
            $nsu,
            $acquirer,
            "OperationDeniedCode",
            "Credit card payment denied",
            $delayToAutoSettle,
            1800,
            21600,
            1000,
            null,
        );
    }

    public static function denied(CreatePaymentRequest $request, $tid): self
    {
        return new self(
            $request->paymentId(),
            "denied",
            null,
            $tid,
            null,
            null,
            "OperationApprovedCode",
            "Approved",
            null,
            null,
            null,
            null,
            null
        );
    }

    public static function pending(CreatePaymentRequest $request, $tid, $retryResponse): self
    {
        return new self(
            $request->paymentId(),
            "undefined",
            null,
            $tid,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $retryResponse
        );
    }



    public function asArray(): array
    {
        if ($this->status === 'approved') {
            $formattedResponse = [
                "paymentId" => $this->paymentId,
                "status" => $this->status,
                "authorizationId" => $this->authorizationId,
                "tid" => $this->tid,
                "nsu" => $this->nsu,
                "acquirer" => $this->acquirer,
                "code" => $this->code,
                "message" => $this->message,
                "delayToAutoSettle" => $this->delayToAutoSettle,
                "delayToAutoSettleAfterAntifraud" => $this->delayToAutoSettleAfterAntiFraud,
                "delayToCancel" => $this->delayToCancel,
                "maxValue" => $this->maxValue,
            ];
        } elseif ($this->status === 'denied') {
            $formattedResponse = [
                "paymentId" => $this->paymentId,
                "status" => $this->status,
                "tid" => $this->tid,
                "code" => $this->code,
                "message" => $this->message,
            ];
        } else {
            $formattedResponse = [
                "paymentId" => $this->paymentId,
                "status" => $this->status,
                "tid" => $this->tid,
            ];
        }

        return $formattedResponse;
    }

    public function responseCode(): int
    {
        return 200;
    }

    public function retryResponse(): ?self
    {
        return $this->retryResponse;
    }
}
