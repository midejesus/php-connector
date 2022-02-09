<?php

namespace PhpConnector\Service;

use PhpConnector\Model\CreatePaymentRequest;

/**
 * CustomInstallmentsService will render the view for selecting the installments for a given
 * payment, and will also persist the selected option.
 * The number of installments available for selection is dynamic, based on the payment value.
 *
 * Having dynamic installments is not available in the SmartCheckout template, so it needs to be done
 * externally, and therefore this is a use case of *Purchase Redirect Flow*, as the
 * provider needs to redirect the customer to a different page in order to get extra information from
 * the customer before authorizing the purchase payment.
 *
 * The payment method "myRedirectPaymentMethod" will follow the redirect flow to use the
 * custom installments.
 */
class CustomInstallmentsService
{
    private $paymentId;
    private $paymentRequest;

    public function __construct(string $paymentId)
    {
        $this->paymentId = $paymentId;
        $this->paymentRequest = $this->getPersistedRequest($paymentId);
    }

    private function getPersistedRequest(string $paymentId): CreatePaymentRequest
    {
        $filename = "logs/requests/authorization-{$paymentId}.json";
        $content = file_get_contents($filename);
        $requestAsArray = json_decode($content, true);

        return CreatePaymentRequest::fromArray($requestAsArray);
    }

    /** The default minimum amount per installment is 50 */
    private function calculateNumberOfInstallmentsOptions($minimumAmountPerInstallment = 50): int
    {
        $value =  $this->paymentRequest->value();
        return floor($value / $minimumAmountPerInstallment) > 0 ? floor($value / 50) : 1;
    }

    public function renderHTML()
    {
        ob_start();
        $numberOfInstallments = $this->calculateNumberOfInstallmentsOptions();
        $paymentId = $this->paymentId;
        include __DIR__ . "/../View/installments.view.php";
        return ob_get_clean();
    }

    public function saveInstallmentsSelection(int $numberOfInstallments)
    {
        if (!is_dir("logs/custom-installments")) {
            mkdir("logs/custom-installments", 0777, true);
        }
        $content = json_encode(["installments" => $numberOfInstallments]);
        $filename = "logs/custom-installments/{$this->paymentId}.json";
        file_put_contents($filename, $content);
    }

    public function getReturnUrl()
    {
        return $this->paymentRequest->returnUrl();
    }
}
