<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Generated\OpenApi;

final class Operations
{
    public const DATA = [
            'CurrenciesV1Controller_getNetworkTokens_v1' => [
                'method' => 'GET',
                'path' => '/v1/currencies',
                'tags' => [
                    'Currencies',
                    'V1/Currencies'
                ],
                'summary' => 'Get currencies',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    404,
                    429
                ]
            ],
            'CurrenciesV1Controller_getConversionRoutes_v1' => [
                'method' => 'GET',
                'path' => '/v1/currencies/{currencyId}/conversion-routes',
                'tags' => [
                    'Currencies',
                    'V1/Currencies'
                ],
                'summary' => 'Get conversion routes for a specific currency',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    404,
                    429
                ]
            ],
            'CurrenciesV2Controller_getNetworkTokens_v2' => [
                'method' => 'GET',
                'path' => '/v2/currencies',
                'tags' => [
                    'Currencies',
                    'V2/Currencies'
                ],
                'summary' => 'Get currencies',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    404,
                    429
                ]
            ],
            'CurrenciesV2Controller_getConversionRoutes_v2' => [
                'method' => 'GET',
                'path' => '/v2/currencies/{currencyId}/conversion-routes',
                'tags' => [
                    'Currencies',
                    'V2/Currencies'
                ],
                'summary' => 'Get conversion routes for a specific currency',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    404,
                    429
                ]
            ],
            'ClientIdV2Controller_findAll_v2' => [
                'method' => 'GET',
                'path' => '/v2/client-ids',
                'tags' => [
                    'Client IDs',
                    'V2/Client IDs'
                ],
                'summary' => 'List all client IDs',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    401,
                    429
                ]
            ],
            'ClientIdV2Controller_create_v2' => [
                'method' => 'POST',
                'path' => '/v2/client-ids',
                'tags' => [
                    'Client IDs',
                    'V2/Client IDs'
                ],
                'summary' => 'Create a new client ID',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    201
                ],
                'errorStatuses' => [
                    400,
                    401,
                    429
                ]
            ],
            'ClientIdV2Controller_findOne_v2' => [
                'method' => 'GET',
                'path' => '/v2/client-ids/{id}',
                'tags' => [
                    'Client IDs',
                    'V2/Client IDs'
                ],
                'summary' => 'Get a specific client ID',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    401,
                    404,
                    429
                ]
            ],
            'ClientIdV2Controller_update_v2' => [
                'method' => 'PUT',
                'path' => '/v2/client-ids/{id}',
                'tags' => [
                    'Client IDs',
                    'V2/Client IDs'
                ],
                'summary' => 'Update a client ID',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    401,
                    404,
                    429
                ]
            ],
            'ClientIdV2Controller_delete_v2' => [
                'method' => 'DELETE',
                'path' => '/v2/client-ids/{id}',
                'tags' => [
                    'Client IDs',
                    'V2/Client IDs'
                ],
                'summary' => 'Revoke a client ID',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    401,
                    404,
                    429
                ]
            ],
            'RequestControllerV1_createRequest_v1' => [
                'method' => 'POST',
                'path' => '/v1/request',
                'tags' => [
                    'Request',
                    'V1/Request'
                ],
                'summary' => 'Create a new request',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    201
                ],
                'errorStatuses' => [
                    400,
                    401,
                    404,
                    429
                ]
            ],
            'RequestControllerV1_getRequestStatus_v1' => [
                'method' => 'GET',
                'path' => '/v1/request/{paymentReference}',
                'tags' => [
                    'Request',
                    'V1/Request'
                ],
                'summary' => 'Get request status',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    401,
                    404,
                    429
                ]
            ],
            'RequestControllerV1_stopRecurrenceRequest_v1' => [
                'method' => 'PATCH',
                'path' => '/v1/request/{paymentReference}/stop-recurrence',
                'tags' => [
                    'Request',
                    'V1/Request'
                ],
                'summary' => 'Stop a recurring request',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    401,
                    404,
                    429
                ]
            ],
            'RequestControllerV1_getPaymentCalldata_v1' => [
                'method' => 'GET',
                'path' => '/v1/request/{paymentReference}/pay',
                'tags' => [
                    'Request',
                    'V1/Request'
                ],
                'summary' => 'Get payment calldata',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    401,
                    404,
                    429
                ]
            ],
            'RequestControllerV1_getRequestPaymentRoutes_v1' => [
                'method' => 'GET',
                'path' => '/v1/request/{paymentReference}/routes',
                'tags' => [
                    'Request',
                    'V1/Request'
                ],
                'summary' => 'Get payment routes',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    401,
                    404,
                    429
                ]
            ],
            'RequestControllerV1_sendPaymentIntent_v1' => [
                'method' => 'POST',
                'path' => '/v1/request/{paymentIntentId}/send',
                'tags' => [
                    'Request',
                    'V1/Request'
                ],
                'summary' => 'Send a payment intent',
                'hasJsonRequest' => true,
                'successStatuses' => [],
                'errorStatuses' => [
                    401,
                    404,
                    429
                ]
            ],
            'RequestControllerV2_createRequest_v2' => [
                'method' => 'POST',
                'path' => '/v2/request',
                'tags' => [
                    'Request',
                    'V2/Request'
                ],
                'summary' => 'Create a new request',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    201
                ],
                'errorStatuses' => [
                    400,
                    404,
                    429
                ]
            ],
            'RequestControllerV2_getRequestStatus_v2' => [
                'method' => 'GET',
                'path' => '/v2/request/{requestId}',
                'tags' => [
                    'Request',
                    'V2/Request'
                ],
                'summary' => 'Get request status',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    404,
                    429
                ]
            ],
            'RequestControllerV2_updateRequest_v2' => [
                'method' => 'PATCH',
                'path' => '/v2/request/{requestId}',
                'tags' => [
                    'Request',
                    'V2/Request'
                ],
                'summary' => 'Update a recurring request',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    404,
                    429
                ]
            ],
            'RequestControllerV2_getPaymentCalldata_v2' => [
                'method' => 'GET',
                'path' => '/v2/request/{requestId}/pay',
                'tags' => [
                    'Request',
                    'V2/Request'
                ],
                'summary' => 'Get payment calldata',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    404,
                    429
                ]
            ],
            'RequestControllerV2_getRequestPaymentRoutes_v2' => [
                'method' => 'GET',
                'path' => '/v2/request/{requestId}/routes',
                'tags' => [
                    'Request',
                    'V2/Request'
                ],
                'summary' => 'Get payment routes',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    404,
                    429
                ]
            ],
            'RequestControllerV2_sendPaymentIntent_v2' => [
                'method' => 'POST',
                'path' => '/v2/request/payment-intents/{paymentIntentId}',
                'tags' => [
                    'Request',
                    'V2/Request'
                ],
                'summary' => 'Send a payment intent',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    404,
                    429
                ]
            ],
            'PayerV1Controller_getComplianceData_v1' => [
                'method' => 'POST',
                'path' => '/v1/payer',
                'tags' => [
                    'Payer',
                    'V1/Payer'
                ],
                'summary' => 'Create compliance data for a user',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    401,
                    404,
                    429
                ]
            ],
            'PayerV1Controller_getComplianceStatus_v1' => [
                'method' => 'GET',
                'path' => '/v1/payer/{clientUserId}',
                'tags' => [
                    'Payer',
                    'V1/Payer'
                ],
                'summary' => 'Get compliance status for a user',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    401,
                    404,
                    429
                ]
            ],
            'PayerV1Controller_updateComplianceStatus_v1' => [
                'method' => 'PATCH',
                'path' => '/v1/payer/{clientUserId}',
                'tags' => [
                    'Payer',
                    'V1/Payer'
                ],
                'summary' => 'Update agreement status',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    401,
                    404,
                    429
                ]
            ],
            'PayerV1Controller_getPaymentDetails_v1' => [
                'method' => 'GET',
                'path' => '/v1/payer/{clientUserId}/payment-details',
                'tags' => [
                    'Payer',
                    'V1/Payer'
                ],
                'summary' => 'Get payment details for a user',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    401,
                    404,
                    429
                ]
            ],
            'PayerV1Controller_createPaymentDetails_v1' => [
                'method' => 'POST',
                'path' => '/v1/payer/{clientUserId}/payment-details',
                'tags' => [
                    'Payer',
                    'V1/Payer'
                ],
                'summary' => 'Create payment details',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    201
                ],
                'errorStatuses' => [
                    400,
                    401,
                    404,
                    429
                ]
            ],
            'PayerV2Controller_getComplianceData_v2' => [
                'method' => 'POST',
                'path' => '/v2/payer',
                'tags' => [
                    'Payer',
                    'V2/Payer'
                ],
                'summary' => 'Create compliance data for a user',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    401,
                    404,
                    429
                ]
            ],
            'PayerV2Controller_getComplianceStatus_v2' => [
                'method' => 'GET',
                'path' => '/v2/payer/{clientUserId}',
                'tags' => [
                    'Payer',
                    'V2/Payer'
                ],
                'summary' => 'Get compliance status for a user',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    401,
                    404,
                    429
                ]
            ],
            'PayerV2Controller_updateComplianceStatus_v2' => [
                'method' => 'PATCH',
                'path' => '/v2/payer/{clientUserId}',
                'tags' => [
                    'Payer',
                    'V2/Payer'
                ],
                'summary' => 'Update agreement status',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    401,
                    404,
                    429
                ]
            ],
            'PayerV2Controller_getPaymentDetails_v2' => [
                'method' => 'GET',
                'path' => '/v2/payer/{clientUserId}/payment-details',
                'tags' => [
                    'Payer',
                    'V2/Payer'
                ],
                'summary' => 'Get payment details for a user',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    401,
                    404,
                    429
                ]
            ],
            'PayerV2Controller_createPaymentDetails_v2' => [
                'method' => 'POST',
                'path' => '/v2/payer/{clientUserId}/payment-details',
                'tags' => [
                    'Payer',
                    'V2/Payer'
                ],
                'summary' => 'Create payment details',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    201
                ],
                'errorStatuses' => [
                    400,
                    401,
                    404,
                    429
                ]
            ],
            'PayV1Controller_payRequest_v1' => [
                'method' => 'POST',
                'path' => '/v1/pay',
                'tags' => [
                    'Pay',
                    'V1/Pay'
                ],
                'summary' => 'Initiate a payment',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    201
                ],
                'errorStatuses' => [
                    401,
                    404,
                    429
                ]
            ],
            'PaymentV2Controller_searchPayments_v2' => [
                'method' => 'GET',
                'path' => '/v2/payments',
                'tags' => [
                    'V2/Payments'
                ],
                'summary' => 'Search payments with advanced filtering',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    401,
                    429
                ]
            ],
            'PayoutV2Controller_payRequest_v2' => [
                'method' => 'POST',
                'path' => '/v2/payouts',
                'tags' => [
                    'Pay',
                    'V2/Payouts'
                ],
                'summary' => 'Initiate a payment',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    201
                ],
                'errorStatuses' => [
                    404,
                    429
                ]
            ],
            'PayoutV2Controller_payBatchRequest_v2' => [
                'method' => 'POST',
                'path' => '/v2/payouts/batch',
                'tags' => [
                    'Pay',
                    'V2/Payouts'
                ],
                'summary' => 'Pay multiple requests in one transaction',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    201
                ],
                'errorStatuses' => [
                    400,
                    429
                ]
            ],
            'PayoutV2Controller_getRecurringPaymentStatus_v2' => [
                'method' => 'GET',
                'path' => '/v2/payouts/recurring/{id}',
                'tags' => [
                    'Pay',
                    'V2/Payouts'
                ],
                'summary' => 'Get the status of a recurring payment',
                'hasJsonRequest' => false,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    404,
                    429
                ]
            ],
            'PayoutV2Controller_submitRecurringPaymentSignature_v2' => [
                'method' => 'POST',
                'path' => '/v2/payouts/recurring/{id}',
                'tags' => [
                    'Pay',
                    'V2/Payouts'
                ],
                'summary' => 'Submit a recurring payment signature',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    201
                ],
                'errorStatuses' => [
                    400,
                    404,
                    429
                ]
            ],
            'PayoutV2Controller_updateRecurringPayment_v2' => [
                'method' => 'PATCH',
                'path' => '/v2/payouts/recurring/{id}',
                'tags' => [
                    'Pay',
                    'V2/Payouts'
                ],
                'summary' => 'Update a recurring payment',
                'hasJsonRequest' => true,
                'successStatuses' => [
                    200
                ],
                'errorStatuses' => [
                    400,
                    404,
                    429
                ]
            ]
        ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::DATA;
    }
}
