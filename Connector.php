<?php

require("CaptureRequest.php");
require("CancellationRequest.php");
require("RefundRequest.php");
require("Card.php");
require("Address.php");
require("Buyer.php");
require("Recipient.php");
require("Item.php");
require("PaymentRequest.php");
class Connector
{
    private $providerAPI = null;

    public function __construct(ProviderAPIInterface $providerAPI)
    {
        $this->providerAPI = $providerAPI;
    }

    function listPaymentMethods(): string
    {
        return json_encode([
            "paymentMethods" => [
                "Visa",
                "Mastercard",
                "American Express",
            ]
        ]);
    }

    // to test: are customFields and autoSettleDelay mandatory?
    function listPaymentProviderManifest(): string
    {
        return json_encode([
            "paymentMethods" => [
                [
                    "name" => "Visa",
                    "allowsSplit" => "onAuthorize"
                ],
                [
                    "name" => "Mastercard",
                    "allowsSplit" => "onCapture"
                ],
                [
                    "name" => "American Express",
                    "allowsSplit" => "disabled"
                ],
            ],
            "customFields" => [
                [
                    "name" => "Merchant's custom field",
                    "type" => "text"
                ],
                [
                    "name" => "Merchant's custom select field",
                    "type" => "select",
                    "options" => [
                        [
                            "text" => "Field option 1",
                            "value" => "1"
                        ],
                        [
                            "text" => "Field option 2",
                            "value" => "2"
                        ],
                        [
                            "text" => "Field option 3",
                            "value" => "3"
                        ]
                    ]
                ]
            ],
            "autoSettleDelay" => [
                "minimum" => "0",
                "maximum" => "720"
            ]
        ]);
    }

    /**
     * This function validates the request body and reaches out to the provider to process
     * the refund request and return the formatted response body.
     *
     * @param array $requestBody
     * @return string
     */
    function refundPayment(array $requestBody): array
    {
        try {
            $request = new RefundRequest(
                $requestBody['requestId'],
                $requestBody['settleId'],
                $requestBody['paymentId'],
                $requestBody['tid'],
                (float) $requestBody['value'],
                $requestBody['transactionId'],
                $requestBody['recipients'],
                $requestBody['sandboxMode']
            );
        } catch (\Throwable $th) {
            throw new Exception('Invalid Request Body', 400);
        }

        // assuming that provider expects an array as input
        $requestAsArray = $request->toArray();

        // call provider to process the request
        $providerResponseArray = $this->providerAPI->processRefund($requestAsArray);

        // format response according to PPP definitions
        $formattedResponse = [
            "paymentId" => $request->paymentId(),
            "requestId" => $request->requestId(),
            "refundId" => $providerResponseArray["refundId"],
            "value" => $providerResponseArray["value"],
        ];

        if (!is_null($providerResponseArray["code"])) {
            $formattedResponse["code"] = $providerResponseArray["code"];
        }

        if (!is_null($providerResponseArray["message"])) {
            $formattedResponse["message"] = $providerResponseArray["message"];
        }

        return [
            "responseCode" => $providerResponseArray["responseCode"],
            "responseData" => json_encode($formattedResponse)
        ];

    }

    public function cancelPayment(array $requestBody): array
    {
        try {
            $request = new CancellationRequest(
                $requestBody['paymentId'],
                $requestBody['requestId'],
                $requestBody['authorizationId'],
                $requestBody['sandboxMode']
            );
        } catch (\Throwable $th) {
            throw new Exception('Invalid Request Body', 400);
        }

        // format request info according to provider definition
        $requestAsArray = $request->toArray();

        // call provider to process the request
        $providerResponseArray = $this->providerAPI->processCancellation($requestAsArray);

        // format response according to PPP definitions
        $formattedResponse = [
            "paymentId" => $request->paymentId(),
            "requestId" => $request->requestId(),
            "cancellationId" => $providerResponseArray["cancellationId"],
        ];

        if (!is_null($providerResponseArray["code"])) {
            $formattedResponse["code"] = $providerResponseArray["code"];
        }

        if (!is_null($providerResponseArray["message"])) {
            $formattedResponse["message"] = $providerResponseArray["message"];
        }

        return [
            "responseCode" => $providerResponseArray["responseCode"],
            "responseData" => json_encode($formattedResponse)
        ];
    }

    public function capturePayment(array $requestBody): array
    {
        try {
            $request = new CaptureRequest(
                $requestBody['transactionId'],
                $requestBody['requestId'],
                $requestBody['paymentId'],
                (float) $requestBody['value'],
                $requestBody['authorizationId'],
                $requestBody['tid'],
                $requestBody['recipients'],
                $requestBody['sandboxMode']
            );
        } catch (\Throwable $th) {
            throw new Exception('Invalid Request Body', 400);
        }

        // assuming that provider expects an array as input
        $requestAsArray = $request->toArray();

        // call provider to process the request
        $providerResponseArray = $this->providerAPI->processCapture($requestAsArray);

        // format response according to PPP definitions
        $formattedResponse = [
            "paymentId" => $request->paymentId(),
            "requestId" => $request->requestId(),
            "settleId" => $providerResponseArray["settleId"],
            "value" => $providerResponseArray["value"],
        ];

        if (!is_null($providerResponseArray["code"])) {
            $formattedResponse["code"] = $providerResponseArray["code"];
        }

        if (!is_null($providerResponseArray["message"])) {
            $formattedResponse["message"] = $providerResponseArray["message"];
        }

        return [
            "responseCode" => $providerResponseArray["responseCode"],
            "responseData" => json_encode($formattedResponse)
        ];
    }

    public function createPayment(array $requestBody): array
    {
        try {
            $card = new Card(
                $requestBody['card']['holder'],
                $requestBody['card']['number'],
                $requestBody['card']['csc'],
                $requestBody['card']['expiration']['month'],
                $requestBody['card']['expiration']['year'],
                $requestBody['card']['document']
            );

            // docs says 'minicart' not in camel case, but example shows like this
            $shippingAddress = new Address(
                $requestBody['miniCart']['shippingAddress']['country'],
                $requestBody['miniCart']['shippingAddress']['street'],
                $requestBody['miniCart']['shippingAddress']['number'],
                $requestBody['miniCart']['shippingAddress']['complement'],
                $requestBody['miniCart']['shippingAddress']['neighborhood'],
                $requestBody['miniCart']['shippingAddress']['postalCode'],
                $requestBody['miniCart']['shippingAddress']['city'],
                $requestBody['miniCart']['shippingAddress']['state']
            );
            $billingAddress = new Address(
                $requestBody['miniCart']['billingAddress']['country'],
                $requestBody['miniCart']['billingAddress']['street'],
                $requestBody['miniCart']['billingAddress']['number'],
                $requestBody['miniCart']['billingAddress']['complement'],
                $requestBody['miniCart']['billingAddress']['neighborhood'],
                $requestBody['miniCart']['billingAddress']['postalCode'],
                $requestBody['miniCart']['billingAddress']['city'],
                $requestBody['miniCart']['billingAddress']['state']
            );
            $buyer = new Buyer(
                $requestBody['miniCart']['buyer']['id'],
                $requestBody['miniCart']['buyer']['firstName'],
                $requestBody['miniCart']['buyer']['lastName'],
                $requestBody['miniCart']['buyer']['document'],
                $requestBody['miniCart']['buyer']['documentType'],
                $requestBody['miniCart']['buyer']['email'],
                $requestBody['miniCart']['buyer']['phone'],
                $requestBody['miniCart']['buyer']['isCorporate'],
                $requestBody['miniCart']['buyer']['corporateName'],
                $requestBody['miniCart']['buyer']['tradeName'],
                $requestBody['miniCart']['buyer']['corporateDocument'],
                $requestBody['miniCart']['buyer']['createdDate']
            );

            $items = array_map(
                function ($item) {
                    return new Item(
                        $item['id'],
                        $item['name'],
                        (float) $item['price'],
                        (int) $item['quantity'],
                        (int) $item['discount'],
                        $item['deliveryType'],
                        $item['categoryId'],
                        $item['sellerId'],
                        (float) $item['taxRate'],
                        (float) $item['taxValue'],
                    );
                },
                $requestBody['miniCart']['items']
            );
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
                $requestBody['recipients']
            );

            $request = new PaymentRequest(
                $requestBody['reference'],
                $requestBody['orderId'],
                $requestBody['shopperInteraction'],
                $requestBody['verificationOnly'],
                $requestBody['transactionId'],
                $requestBody['paymentId'],
                $requestBody['paymentMethod'],
                $requestBody['paymentMethodCustomCode'],
                $requestBody['merchantName'],
                (float) $requestBody['value'],
                $requestBody['currency'],
                $requestBody['installments'],
                (float) $requestBody['installmentsInterestRate'],
                (float) $requestBody['installmentsValue'],
                $requestBody['deviceFingerprint'],
                $requestBody['ipAddress'],
                $card,
                (float) $requestBody['shippingValue'],
                (float) $requestBody['taxValue'],
                $buyer,
                $shippingAddress,
                $billingAddress,
                $items,
                $recipients,
                $requestBody['merchantSettings'],
                $requestBody['url'],
                $requestBody['inboundRequestUrl'],
                $requestBody['secureProxyUrl'],
                $requestBody['sandboxMode'],
                (float) $requestBody['totalCartValue'],
                $requestBody['callbackUrl'],
                $requestBody['returnUrl']
            );
        } catch (\Throwable $th) {
            throw new Exception('Invalid Request Body', 400);
        }
        return [];
    }
}
