# Innbucks API Integration (Non-Redirect)

Dischub provides an easy-to-use integration for receiving payments between Dischub and a merchant's online platform. This guide explains how to:

- create a new payment order
- complete payments without redirecting the customer away from your platform
- receive payment status updates

If you do not have programming experience, you may need to work with a developer.

This integration does not support the test environment.

This integration does not support `callback_url` or `redirect_url` in the request data.

## 1. Configure Your Account

Follow these steps to configure your account:

1. Visit the Finance page to manage settlement bank accounts and wallets under `Gateway > Finance`.
2. Visit `Gateway > Configurations`.
3. Click **Generate** to create your API key.
4. Enter your business name as it should appear to clients during payment.
5. Attach your business logo.
6. Add your website URL if applicable.

## 2. Create an Order or Initiate Payment

To create a payment order, send a `POST` request to the Dischub order-creation endpoint. The request includes the endpoint URL, headers, and request data.

### Endpoint

```text
https://dischub.co.zw/api/orders/create/
```

### Request Headers

The API uses `application/json`. Your API key must be included in the headers, not in the request body.

```json
{
  "Content-Type": "application/json",
  "X-Api-Key": "6945f57cdbf14b00812cb15df1933f2a"
}
```

### Request Data

Replace the example values with your own payment details:

```json
{
  "order_id": "344245663",
  "sender": "+263780070488",
  "recipient": "softwarez@gmail.com",
  "amount": 14.58,
  "currency": "USD",
  "payment_method": "innbucks"
}
```

### Required Fields

- `order_id`: A unique string used to track the transaction. It must be a string, unique across all orders, and no longer than 30 characters.
- `sender`: The customer's phone number. Accepted formats include `+263780070488`, `0263780070488`, and `0780070488`.
- `recipient`: The email address used to create the Dischub merchant account.
- `amount`: The payment amount. Must be a valid number greater than `0` and less than `100000`.
- `currency`: Supported values are `USD` and `ZWG`.
- `payment_method`: The payment method to use. Accepted value: `"innbucks"`.

### Responses

After making the request, you receive a JSON response indicating the request status.

#### Successful Response

```json
{
  "status": "success",
  "message": "order created successfully",
  "response_code": 201,
  "innbucks_status": "Innbucks payment processed successfully.",
  "payment_code_status": "Innbucks payment code generated successfully.",
  "code": "815510797",
  "qrcode": "iVBORw0KGgoAAAANSUhEUgAAASwAAAEsAQAAAABRBrPYAAABJklEQVR4Xu3XQRLCIAwFUFz1GBy1HLVHcMlK5CcRbdRRZyTd/L9gIDxWGYY2tW9yTr7yMmQuZC5kLmQuZC5kLr+xmixZZ6dWrHAii2O6agVs3cDy4wZZECvSotwuvUOLHei9IzuIWbPIjmNSx5LsCCYR1juG2eMGWQhLFmUYrEAWx+6RA4srkrniHIZNeb/zaJv0aXvuKdk0dnu6e10O7GpkQaxiVU2UMYyQRTAJrHbnfkpmZGGspJGMJQY7RRbGLBVfUcvtPzuRBTJcD0lu1rZV+7S/MmRzma76Rdk1iyyaFVyPkvUDSmI1smOYlPBBm7JNyWJZHZcHw9tmkc1httkfklWXGLZF62QhLFmUyQFcGd8sspnsU8hcyFzIXMhcyFzIXP7MrugWXnITw+VqAAAAAElFTkSuQmCC"
}
```

#### Error Responses

```json
{
  "status": "error",
  "message": "missing api key in request headers",
  "response_code": 401
}
```

```json
{
  "status": "error",
  "message": "missing or invalid required keys in request data",
  "response_code": 422
}
```

```json
{
  "status": "error",
  "message": "order_id must be posted as a string",
  "response_code": 400
}
```

```json
{
  "status": "error",
  "message": "duplicate order_id detected, please create a new unique order_id",
  "response_code": 409
}
```

```json
{
  "status": "error",
  "message": "order_id must be 30 characters or less",
  "response_code": 422
}
```

```json
{
  "status": "error",
  "message": "amount must be greater than 0 and less than 100000",
  "response_code": 422
}
```

```json
{
  "status": "error",
  "message": "invalid sender phone number format",
  "response_code": 422
}
```

```json
{
  "status": "error",
  "message": "invalid or unsupported currency. Accepted values: USD, ZWG",
  "response_code": 400
}
```

```json
{
  "status": "error",
  "message": "authorization failed: wrong api key or recipient identifier",
  "response_code": 401
}
```

```json
{
  "status": "error",
  "message": "merchant account has no business name configured",
  "response_code": 403
}
```

```json
{
  "status": "error",
  "message": "payment gateway charges are not configured, please contact support",
  "response_code": 503
}
```

```json
{
  "status": "error",
  "message": "order could not be created due to an internal error",
  "response_code": 500
}
```

## 3. Complete Payment

After the order is successfully created, the response includes a `qrcode` value. Convert the QR code data to an image and display it to the customer so they can scan it and complete the payment.

## 4. Receive Payment Status

After the payment is processed, you must send a `POST` request to the Dischub payment status endpoint to track whether the transaction succeeded or failed.

### Steps to Handle Payment Status

1. Make a `POST` request to the Dischub payment status endpoint with the required arguments.
2. Receive the payment status in a JSON payload.
3. Update your database or application based on the returned data.

### Endpoint

```text
https://dischub.co.zw/api/payment/status/3/step/
```

### Request Headers

```json
{
  "Content-Type": "application/json",
  "X-Api-Key": "6945f57cdbf14b00812cb15df1933f2a"
}
```

### Request Data

Replace the values below with your own credentials and the order you want to check:

```json
{
  "order_id": "344245663",
  "recipient": "softwarez@gmail.com"
}
```

### Required Fields

- `order_id`: The order ID used when creating the order in step 2.
- `recipient`: The email address used to create the Dischub merchant account.

### Responses

#### Successful Response

```json
{
  "order_id": "344245663",
  "recipient": "Your Business Name",
  "sender": "+263780070488",
  "status": "success",
  "status_reason": "Reason...",
  "currency": "USD",
  "amount": "14.58",
  "timestamp": "2025-01-09 04:20:08.702591+00:00"
}
```

#### Error Responses

```json
{
  "status": "error",
  "message": "missing api key in request headers",
  "response_code": 401
}
```

```json
{
  "status": "error",
  "message": "missing or invalid required keys in request data",
  "response_code": 422
}
```

```json
{
  "status": "error",
  "message": "authorization failed: wrong api key or recipient identifier",
  "response_code": 401
}
```

```json
{
  "status": "error",
  "message": "no order found matching the provided order_id and recipient",
  "response_code": 404
}
```

```json
{
  "status": "error",
  "message": "an unexpected error occurred while retrieving the order",
  "response_code": 500
}
```

The `status` field in the success response can contain the following values:

- `success` — payment completed.
- `pending` — payment not yet confirmed.
- `failed` — payment failed or timed out.

Use this information to update your system or database accordingly.
