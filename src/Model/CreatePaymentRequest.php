<?php

namespace PhpConnector\Model;

/**
 * Create Payment Request Class validates that the request body contains all the necessary
 * parameters with the expected type.
 */
class CreatePaymentRequest
{
    private $reference;
    private $orderId;
    private $shopperInteraction;
    private $verificationOnly;
    private $transactionId;
    private $paymentId;
    private $paymentMethod;
    private $paymentMethodCustomCode; // not fully camel case on documentation
    private $merchantName;
    private $value;
    private $currency;
    private $installments;
    private $installmentsInterestRate; // typo on documentation key
    private $installmentsValue;
    private $deviceFingerprint;
    private $ipAddress;
    private $card;
    private $shippingValue;
    private $taxValue;
    private $buyer;
    private $shippingAddress;
    private $billingAddress;
    private $items;
    private $recipients; // why docs says this argument is optional? does it apply only to split payments?
    private $merchantSettings;
    private $url;
    private $inboundRequestUrl;
    private $secureProxyUrl;
    private $sandboxMode;
    private $totalCartValue;
    private $callbackUrl;
    private $returnUrl;
    private $requestArray;

    private $creditCardList = [
        "Visa",
        "Mastercard",
        "American Express",
        "Discover",
        "JCB",
        "Diners",
        "Elo",
        "Hipercard",
        "Aura",
        "Benricompras",
        "Credz",
        "Cabal",
    ];


    private function __construct(
        string $reference,
        string $orderId,
        string $shopperInteraction,
        ?bool $verificationOnly,
        string $transactionId,
        string $paymentId,
        string $paymentMethod,
        ?string $paymentMethodCustomCode, // mandatory only for co-branded and private labels
        string $merchantName,
        float $value,
        string $currency,
        int $installments,
        ?float $installmentsInterestRate,
        ?float $installmentsValue,
        string $deviceFingerprint,
        ?string $ipAddress,
        ?Card $card,
        ?float $shippingValue,
        ?float $taxValue,
        Buyer $buyer,
        Address $shippingAddress,
        Address $billingAddress,
        array $items,
        ?array $recipients,
        MerchantSettings $merchantSettings,
        string $url,
        ?string $inboundRequestUrl,
        ?string $secureProxyUrl,
        ?bool $sandboxMode,
        ?float $totalCartValue,
        string $callbackUrl,
        string $returnUrl,
        array $requestArray
    ) {
        $this->reference = $reference;
        $this->orderId = $orderId;
        $this->shopperInteraction = $shopperInteraction;
        $this->verificationOnly = $verificationOnly;
        $this->transactionId = $transactionId;
        $this->paymentId = $paymentId;
        $this->paymentMethod = $paymentMethod;
        $this->paymentMethodCustomCode = $paymentMethodCustomCode;
        $this->merchantName = $merchantName;
        $this->value = $value;
        $this->currency = $currency;
        $this->installments = $installments;
        $this->installmentsInterestRate = $installmentsInterestRate;
        $this->installmentsValue = $installmentsValue;
        $this->deviceFingerprint = $deviceFingerprint;
        $this->ipAddress = $ipAddress;
        $this->card = $card;
        $this->shippingValue = $shippingValue;
        $this->taxValue = $taxValue;
        $this->buyer = $buyer;
        $this->shippingAddress = $shippingAddress;
        $this->billingAddress = $billingAddress;
        $this->items = $items;
        $this->recipients = $recipients;
        $this->merchantSettings = $merchantSettings;
        $this->url = $url;
        $this->inboundRequestUrl = $inboundRequestUrl;
        $this->secureProxyUrl = $secureProxyUrl;
        $this->sandboxMode = $sandboxMode ?? false;
        $this->totalCartValue = $totalCartValue;
        $this->callbackUrl = $callbackUrl;
        $this->returnUrl = $returnUrl;
        $this->requestArray = $requestArray;

    }

    public static function fromArray(array $array): self
    {
        $card = null;
        if (isset($array['card']['number'])) {
            $card = new Card(
                $array['card']['holder'],
                $array['card']['number'],
                $array['card']['csc'],
                $array['card']['expiration']['month'],
                $array['card']['expiration']['year'],
                $array['card']['document']
            );
        }


        // docs says 'minicart' not in camel case, but example shows like this
        $shippingAddress = new Address(
            $array['miniCart']['shippingAddress']['country'],
            $array['miniCart']['shippingAddress']['street'],
            $array['miniCart']['shippingAddress']['number'],
            $array['miniCart']['shippingAddress']['complement'],
            $array['miniCart']['shippingAddress']['neighborhood'],
            $array['miniCart']['shippingAddress']['postalCode'],
            $array['miniCart']['shippingAddress']['city'],
            $array['miniCart']['shippingAddress']['state']
        );
        $billingAddress = new Address(
            $array['miniCart']['billingAddress']['country'],
            $array['miniCart']['billingAddress']['street'],
            $array['miniCart']['billingAddress']['number'],
            $array['miniCart']['billingAddress']['complement'],
            $array['miniCart']['billingAddress']['neighborhood'],
            $array['miniCart']['billingAddress']['postalCode'],
            $array['miniCart']['billingAddress']['city'],
            $array['miniCart']['billingAddress']['state']
        );
        $buyer = new Buyer(
            $array['miniCart']['buyer']['id'],
            $array['miniCart']['buyer']['firstName'],
            $array['miniCart']['buyer']['lastName'],
            $array['miniCart']['buyer']['document'],
            $array['miniCart']['buyer']['documentType'],
            $array['miniCart']['buyer']['email'],
            $array['miniCart']['buyer']['phone'],
            $array['miniCart']['buyer']['isCorporate'],
            $array['miniCart']['buyer']['corporateName'],
            $array['miniCart']['buyer']['tradeName'],
            $array['miniCart']['buyer']['corporateDocument'],
            $array['miniCart']['buyer']['createdDate']
        );

        $items = array_map(
            function ($item) {
                return new Item(
                    $item['id'],
                    $item['name'],
                    (float) $item['price'],
                    (int) $item['quantity'],
                    (int) $item['discount'],
                    $item['deliveryType'] ?? null,
                    $item['categoryId'] ?? null,
                    $item['sellerId'] ?? null,
                    isset($item['taxRate']) ? (float) $item['taxRate'] : null,
                    isset($item['taxValue']) ? (float) $item['taxValue'] : null,
                );
            },
            $array['miniCart']['items']
        );

        $recipients = [];

        if (isset($array['recipients'])) {
            $recipients = array_map(
                function ($recipient) {
                    return new Recipient(
                        $recipient['id'],
                        $recipient['name'],
                        $recipient['documentType'],
                        $recipient['document'],
                        $recipient['role'],
                        $recipient['amount'],
                        $recipient['chargeProcessingFee'],
                        $recipient['chargebackLiable']
                    );
                },
                $array['recipients']
            );
        }

        return new self(
            $array['reference'],
            $array['orderId'],
            $array['shopperInteraction'],
            $array['verificationOnly'] ?? false,
            $array['transactionId'],
            $array['paymentId'],
            $array['paymentMethod'],
            $array['paymentMethodCustomCode'],
            $array['merchantName'],
            (float) $array['value'],
            $array['currency'],
            $array['installments'],
            isset($array['installmentsInterestRate']) ? (float) $array['installmentsInterestRate'] : null,
            isset($array['installmentsValue']) ? (float) $array['installmentsValue'] : null,
            $array['deviceFingerprint'],
            isset($array['ipAddress']) ? $array['ipAddress'] : null,
            $card,
            isset($array['shippingValue']) ? (float) $array['shippingValue'] : null,
            isset($array['taxValue']) ? (float) $array['taxValue'] : null,
            $buyer,
            $shippingAddress,
            $billingAddress,
            $items,
            $recipients,
            isset($array['merchantSettings']) ? MerchantSettings::fromArray($array['merchantSettings']) : new MerchantSettings(),
            $array['url'] ?? null,
            $array['inboundRequestUrl'] ?? null,
            $array['secureProxyUrl'] ?? null,
            $array['sandboxMode'] ?? false,
            isset($array['totalCartValue']) ? (float) $array['totalCartValue'] : null,
            $array['callbackUrl'],
            $array['returnUrl'],
            $array
        );
    }

    public function paymentId(): string
    {
        return $this->paymentId;
    }

    public function card(): ?Card
    {
        return $this->card;
    }

    public function callbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function returnUrl(): string
    {
        return $this->returnUrl;
    }

    public function paymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function isCreditCardPayment(): bool
    {
        return in_array($this->paymentMethod, $this->creditCardList);
    }

    public function merchantSettings(): MerchantSettings
    {
        return $this->merchantSettings;
    }

    public function value(): float
    {
        return $this->value;
    }

    public function asArray(): array
    {
        return $this->requestArray;
    }
}
