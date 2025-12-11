<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Validation;

/**
 * Source-of-truth for OpenAPI operation IDs implemented by the PHP client.
 *
 * Parity scripts will read this registry and compare against the shared
 * OpenAPI spec in the contracts package to ensure we cover every operation.
 *
 * During domain implementation, add operation IDs here and reference them from
 * the corresponding facade methods via constants.
 */
final class Operations
{
    public const CLIENT_ID_V2_CREATE = 'ClientIdV2Controller_create_v2';
    public const CLIENT_ID_V2_DELETE = 'ClientIdV2Controller_delete_v2';
    public const CLIENT_ID_V2_FIND_ALL = 'ClientIdV2Controller_findAll_v2';
    public const CLIENT_ID_V2_FIND_ONE = 'ClientIdV2Controller_findOne_v2';
    public const CLIENT_ID_V2_UPDATE = 'ClientIdV2Controller_update_v2';
    public const CURRENCIES_V1_CONVERSION_ROUTES = 'CurrenciesV1Controller_getConversionRoutes_v1';
    public const CURRENCIES_V1_NETWORK_TOKENS = 'CurrenciesV1Controller_getNetworkTokens_v1';
    public const CURRENCIES_V2_CONVERSION_ROUTES = 'CurrenciesV2Controller_getConversionRoutes_v2';
    public const CURRENCIES_V2_NETWORK_TOKENS = 'CurrenciesV2Controller_getNetworkTokens_v2';
    public const PAY_V1_PAY_REQUEST = 'PayV1Controller_payRequest_v1';
    public const PAYER_V1_CREATE_PAYMENT_DETAILS = 'PayerV1Controller_createPaymentDetails_v1';
    public const PAYER_V1_GET_COMPLIANCE_DATA = 'PayerV1Controller_getComplianceData_v1';
    public const PAYER_V1_GET_COMPLIANCE_STATUS = 'PayerV1Controller_getComplianceStatus_v1';
    public const PAYER_V1_GET_PAYMENT_DETAILS = 'PayerV1Controller_getPaymentDetails_v1';
    public const PAYER_V1_UPDATE_COMPLIANCE_STATUS = 'PayerV1Controller_updateComplianceStatus_v1';
    public const PAYER_V2_CREATE_PAYMENT_DETAILS = 'PayerV2Controller_createPaymentDetails_v2';
    public const PAYER_V2_GET_COMPLIANCE_DATA = 'PayerV2Controller_getComplianceData_v2';
    public const PAYER_V2_GET_COMPLIANCE_STATUS = 'PayerV2Controller_getComplianceStatus_v2';
    public const PAYER_V2_GET_PAYMENT_DETAILS = 'PayerV2Controller_getPaymentDetails_v2';
    public const PAYER_V2_UPDATE_COMPLIANCE_STATUS = 'PayerV2Controller_updateComplianceStatus_v2';
    public const PAYMENTS_V2_SEARCH = 'PaymentV2Controller_searchPayments_v2';
    public const PAYOUT_V2_GET_RECURRING_STATUS = 'PayoutV2Controller_getRecurringPaymentStatus_v2';
    public const PAYOUT_V2_PAY_BATCH = 'PayoutV2Controller_payBatchRequest_v2';
    public const PAYOUT_V2_PAY_REQUEST = 'PayoutV2Controller_payRequest_v2';
    public const PAYOUT_V2_SUBMIT_RECURRING_SIGNATURE = 'PayoutV2Controller_submitRecurringPaymentSignature_v2';
    public const PAYOUT_V2_UPDATE_RECURRING = 'PayoutV2Controller_updateRecurringPayment_v2';
    public const REQUEST_V1_CREATE = 'RequestControllerV1_createRequest_v1';
    public const REQUEST_V1_GET_PAYMENT_CALLDATA = 'RequestControllerV1_getPaymentCalldata_v1';
    public const REQUEST_V1_GET_PAYMENT_ROUTES = 'RequestControllerV1_getRequestPaymentRoutes_v1';
    public const REQUEST_V1_GET_STATUS = 'RequestControllerV1_getRequestStatus_v1';
    public const REQUEST_V1_SEND_PAYMENT_INTENT = 'RequestControllerV1_sendPaymentIntent_v1';
    public const REQUEST_V1_STOP_RECURRENCE = 'RequestControllerV1_stopRecurrenceRequest_v1';
    public const REQUEST_V2_CREATE = 'RequestControllerV2_createRequest_v2';
    public const REQUEST_V2_GET_PAYMENT_CALLDATA = 'RequestControllerV2_getPaymentCalldata_v2';
    public const REQUEST_V2_GET_PAYMENT_ROUTES = 'RequestControllerV2_getRequestPaymentRoutes_v2';
    public const REQUEST_V2_GET_STATUS = 'RequestControllerV2_getRequestStatus_v2';
    public const REQUEST_V2_SEND_PAYMENT_INTENT = 'RequestControllerV2_sendPaymentIntent_v2';
    public const REQUEST_V2_UPDATE = 'RequestControllerV2_updateRequest_v2';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CLIENT_ID_V2_CREATE,
            self::CLIENT_ID_V2_DELETE,
            self::CLIENT_ID_V2_FIND_ALL,
            self::CLIENT_ID_V2_FIND_ONE,
            self::CLIENT_ID_V2_UPDATE,
            self::CURRENCIES_V1_CONVERSION_ROUTES,
            self::CURRENCIES_V1_NETWORK_TOKENS,
            self::CURRENCIES_V2_CONVERSION_ROUTES,
            self::CURRENCIES_V2_NETWORK_TOKENS,
            self::PAY_V1_PAY_REQUEST,
            self::PAYER_V1_CREATE_PAYMENT_DETAILS,
            self::PAYER_V1_GET_COMPLIANCE_DATA,
            self::PAYER_V1_GET_COMPLIANCE_STATUS,
            self::PAYER_V1_GET_PAYMENT_DETAILS,
            self::PAYER_V1_UPDATE_COMPLIANCE_STATUS,
            self::PAYER_V2_CREATE_PAYMENT_DETAILS,
            self::PAYER_V2_GET_COMPLIANCE_DATA,
            self::PAYER_V2_GET_COMPLIANCE_STATUS,
            self::PAYER_V2_GET_PAYMENT_DETAILS,
            self::PAYER_V2_UPDATE_COMPLIANCE_STATUS,
            self::PAYMENTS_V2_SEARCH,
            self::PAYOUT_V2_GET_RECURRING_STATUS,
            self::PAYOUT_V2_PAY_BATCH,
            self::PAYOUT_V2_PAY_REQUEST,
            self::PAYOUT_V2_SUBMIT_RECURRING_SIGNATURE,
            self::PAYOUT_V2_UPDATE_RECURRING,
            self::REQUEST_V1_CREATE,
            self::REQUEST_V1_GET_PAYMENT_CALLDATA,
            self::REQUEST_V1_GET_PAYMENT_ROUTES,
            self::REQUEST_V1_GET_STATUS,
            self::REQUEST_V1_SEND_PAYMENT_INTENT,
            self::REQUEST_V1_STOP_RECURRENCE,
            self::REQUEST_V2_CREATE,
            self::REQUEST_V2_GET_PAYMENT_CALLDATA,
            self::REQUEST_V2_GET_PAYMENT_ROUTES,
            self::REQUEST_V2_GET_STATUS,
            self::REQUEST_V2_SEND_PAYMENT_INTENT,
            self::REQUEST_V2_UPDATE,
        ];
    }
}
