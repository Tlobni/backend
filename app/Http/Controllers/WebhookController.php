<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PaymentConfiguration;
use App\Models\PaymentTransaction;
use App\Models\UserFcmToken;
use App\Models\UserPurchasedPackage;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;
use PhonePe\PhonePe;
use Throwable;


class WebhookController extends Controller {
    public function stripe() {
        $payload = @file_get_contents('php://input');
        try {
            // Verify webhook signature and extract the event.
            // See https://stripe.com/docs/webhooks/signatures for more information.
            // $data = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);

            $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

            // You can find your endpoint's secret in your webhook settings
            $paymentConfiguration = PaymentConfiguration::select('webhook_secret_key')->where('payment_method', 'Stripe')->first();
            $endpoint_secret = $paymentConfiguration['webhook_secret_key'] ;
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );

            $metadata = $event->data->object->metadata;

            // Use this lines to Remove Signature verification for debugging purpose
            // $event = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
            // $metadata = (array)$event->data->object->metadata;

            Log::info("Stripe Webhook : ", [$event]);
            // handle the events
            switch ($event->type) {
                case 'payment_intent.created':
                    //Do nothing
                    http_response_code(200);
                    break;
                case 'payment_intent.succeeded':
                    $response = $this->assignPackage($metadata['payment_transaction_id'], $metadata['user_id'], $metadata['package_id']);

                    if ($response['error']) {
                        Log::error("Stripe Webhook : ", [$response['message']]);
                    }
                    http_response_code(200);
                    break;
                case 'payment_intent.payment_failed':
                    $response = $this->failedTransaction($metadata['payment_transaction_id'], $metadata['user_id']);
                    if ($response['error']) {
                        Log::error("Stripe Webhook : ", [$response['message']]);
                    }
                    http_response_code(400);
                    break;
                default:
                    Log::error('Stripe Webhook : Received unknown event type', [$event->type]);
            }
        } catch (UnexpectedValueException) {
            // Invalid payload
            echo "Stripe Webhook : Payload Mismatch";
            Log::error("Stripe Webhook : Payload Mismatch");
            http_response_code(400);
            exit();
        } catch (SignatureVerificationException) {
            // Invalid signature
            echo "Stripe Webhook : Signature Verification Failed";
            Log::error("Stripe Webhook : Signature Verification Failed");
            http_response_code(400);
            exit();
        } catch
        (Throwable $e) {
            Log::error("Stripe Webhook : Error occurred", [$e->getMessage() . ' --> ' . $e->getFile() . ' At Line : ' . $e->getLine()]);
            http_response_code(400);
            exit();
        }
    }

    public function razorpay() {
        try {
            $paymentConfiguration = PaymentConfiguration::select('webhook_secret_key')->where('payment_method', 'razorpay')->first();
            $webhookSecret = $paymentConfiguration['webhook_secret_key'];
            $webhookPublic = $paymentConfiguration["webhook_public_key"];

            // get the json data of payment
            $webhookBody = file_get_contents('php://input');
            $data = json_decode($webhookBody, false, 512, JSON_THROW_ON_ERROR);
            Log::info("Razorpay Webhook : ", [$data]);

            $api = new Api($webhookPublic, $webhookSecret);

            $metadata = $data->payload->payment->entity->notes;

            if (isset($data->event) && $data->event == 'payment.captured') {

                //checks the signature
                $expectedSignature = hash_hmac("SHA256", $webhookBody, $webhookSecret);

                $api->utility->verifyWebhookSignature($webhookBody, $expectedSignature, $webhookSecret);

                $paymentTransactionData = PaymentTransaction::where('id', $metadata->payment_transaction_id)->first();
                if ($paymentTransactionData == null) {
                    Log::error("Stripe Webhook : Payment Transaction id not found");
                }

                if ($paymentTransactionData->status == "succeed") {
                    Log::info("Stripe Webhook : Transaction Already Succeed");
                }
                $response = $this->assignPackage($metadata->payment_transaction_id, $metadata->user_id, $metadata->package_id);

                if ($response['error']) {
                    Log::error("Razorpay Webhook : ", [$response['message']]);
                }
                http_response_code(200);
            } elseif (isset($data->event) && $data->event == 'payment.failed') {
                $response = $this->failedTransaction($metadata->payment_transaction_id, $metadata->user_id);
                if ($response['error']) {
                    Log::error("Razorpay Webhook : ", [$response['message']]);
                }
                http_response_code(400);
            } elseif (isset($data->event) && $data->event == 'payment.authorized') {
//                Log::error("Razorpay Webhook : Payment Captured for ", [$data->payload->payment->entity->id]);
                http_response_code(200);
            } else {
                Log::error('Unknown Event Type', [$data->event]);
            }
        } catch (Throwable $th) {
            Log::error($th);
            Log::error('Razorpay --> Webhook Error Occurred');
            http_response_code(400);
        }
    }

    public function paystack() {
        try {
            // only a post with paystack signature header gets our attention
            if (!array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER) || (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST')) {
                echo "Signature not found";
                http_response_code(400);
                exit(0);
            }

            // Retrieve the request's body
            $input = @file_get_contents("php://input");
            $paymentConfiguration = PaymentConfiguration::select('webhook_secret_key')->where('payment_method', 'paystack')->first();
            $endpoint_secret = $paymentConfiguration['webhook_secret_key'];

            if (hash_equals($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'], hash_hmac('sha512', $input, $endpoint_secret))) {
                echo "Signature does not match";
                http_response_code(400);
                exit(0);
            }
            // parse event (which is json string) as object
            // Do something - that will not take long - with $event
            $event = json_decode($input, false, 512, JSON_THROW_ON_ERROR);
            $metadata = $event->data->metadata;
            Log::info("Paystack Webhook event Called", [$event]);
            switch ($event->event) {
                case 'charge.success':
                    $response = $this->assignPackage($metadata->payment_transaction_id, $metadata->user_id, $metadata->package_id);
                    if ($response['error']) {
                        Log::error("Paystack Webhook : ", [$response['message']]);
                    }
                    http_response_code(200);
                    break;
                default:
                    Log::error('Paystack Webhook : Received unknown event type', [$event->event]);
            }
            http_response_code(200);
            exit();
        } catch (Throwable $e) {
            Log::error("Paystack Webhook : Error occurred", [$e->getMessage() . ' --> ' . $e->getFile() . ' At Line : ' . $e->getLine()]);
            http_response_code(400);
            exit();
        }
    }

    public function paystackSuccessCallback(){
        ResponseService::successResponse("Payment done successfully.");
    }

    public function phonePe()
    {
        try {
            if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
                Log::error("Invalid request method");
                return response('Invalid request method', 400);
            }

            $content = trim(file_get_contents("php://input"));
            $payload = json_decode($content, true);
            $base64Payload = $payload['response'];
            $jsonInput = json_decode(base64_decode($base64Payload), true);
            $merchantTransactionId = $jsonInput['data']['merchantTransactionId'];
            $parts = explode('-', $merchantTransactionId);
            $paymentConfiguration = PaymentConfiguration::where('payment_method', 'PhonePe')->first();
            $saltKey = $paymentConfiguration['api_key'];
            $saltIndex = 1;
            $xVerify=$_SERVER['HTTP_X_VERIFY'];

            $xVerify = explode("###", $_SERVER['HTTP_X_VERIFY']);
            $xVerify = $xVerify[0];
            $xVerify = $xVerify . "###" . 1;

            $xVerifyCalculated =  hash('sha256', $base64Payload . "" . $saltKey) . "###" . $saltIndex;
            if ($xVerify != $xVerifyCalculated) {
                echo "x-Verify does not match";
                http_response_code(400);
                exit(401);
            }

            $transaction_id = $parts[1];
            $package_id = $parts[3];
            $paymentTransaction=PaymentTransaction::findOrFail($transaction_id);

            if (!$jsonInput || empty($jsonInput)) {
                Log::error('Invalid webhook payload');
                return response()->json(['error' => 'Invalid payload'], 400);
            }
            if (!isset($jsonInput['response'])) {
                Log::error('No response field found in webhook payload');
                return response()->json(['error' => 'No response field'], 400);
            }

            $base64Response = $jsonInput['response'];
            $decodedResponse = json_decode(base64_decode($base64Response), true);

            if (!$decodedResponse || empty($decodedResponse)) {
                Log::error('Invalid Base64-decoded response payload');
                return response()->json(['error' => 'Invalid Base64 payload'], 400);
            }
            if (isset($jsonInput['success']) && $jsonInput['success'] == true) {
                $transactionId = $jsonInput['data']['transactionId'];
                $merchantTransactionId = $jsonInput['data']['merchantTransactionId'];
                $amount = $jsonInput['data']['amount'];
                $state = $jsonInput['data']['state'];
                $paymentInstrument = $jsonInput['data']['paymentInstrument'];

                Log::info('PhonePe Payment Successful', [
                    'transactionId' => $transactionId,
                    'merchantTransactionId' => $merchantTransactionId,
                    'amount' => $amount,
                    'state' => $state,
                    'paymentInstrument' => $paymentInstrument
                ]);
                $metadata = [
                    'payment_transaction_id' => $transaction_id,
                    'package_id' => $package_id,
                    'user_id' => $paymentTransaction->user_id,
                ];
                $response = $this->assignPackage($metadata['payment_transaction_id'], $metadata['user_id'], $metadata['package_id']);
                if ($response['error']) {
                    Log::error("PhonePe Webhook : ", [$response['message']]);
                }
                return response()->json(['status' => 'success'], 200);
            } else {
                Log::warning('PhonePe Payment Failed or Incomplete', [$jsonInput]);
                return response()->json(['status' => 'failure'], 400);
            }
        } catch (Throwable $e) {
            Log::error("PhonePe Webhook Error", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function phonePeSuccessCallback(){
        ResponseService::successResponse("Payment done successfully.");
    }

    /**
     * Success Business Login
     * @param $payment_transaction_id
     * @param $user_id
     * @param $package_id
     * @return array
     */
    private function assignPackage($payment_transaction_id, $user_id, $package_id) {
        try {
            $paymentTransactionData = PaymentTransaction::where('id', $payment_transaction_id)->first();
            if ($paymentTransactionData == null) {
                Log::error("Payment Transaction id not found");
                return [
                    'error'   => true,
                    'message' => 'Payment Transaction id not found'
                ];
            }

            if ($paymentTransactionData->status == "succeed") {
                Log::info("Transaction Already Succeed");
                return [
                    'error'   => true,
                    'message' => 'Transaction Already Succeed'
                ];
            }

            DB::beginTransaction();
            $paymentTransactionData->update(['payment_status' => "succeed"]);


            $package = Package::find($package_id);

            if (!empty($package)) {
                // Check if user already has an active package
                $existingPackage = UserPurchasedPackage::where('user_id', $user_id)
                    ->where('status', 1)
                    ->where('start_date', '<=', date('Y-m-d'))
                    ->where(function ($q) {
                        $q->whereDate('end_date', '>', date('Y-m-d'))->orWhereNull('end_date');
                    })
                    ->first();
                    
                if ($existingPackage) {
                    // Update existing package status to 0 (expired)
                    $existingPackage->update(['status' => 0]);
                    
                    // Log the replacement
                    Log::info("User ID {$user_id} had existing package ID {$existingPackage->package_id} replaced with new package ID {$package_id}");
                }
                
                UserPurchasedPackage::create([
                    'package_id'  => $package_id,
                    'user_id'     => $user_id,
                    'start_date'  => Carbon::now(),
                    'end_date'    => $package->duration == "unlimited" ? null : Carbon::now()->addDays($package->duration),
                    'total_limit' => $package->item_limit == "unlimited" ? null : $package->item_limit,
                    'used_limit'  => 0,
                    'status'      => 1,
                    'payment_transactions_id' => $payment_transaction_id,
                ]);
            }

            $title = "Package Purchased";
            $body = 'Amount :- ' . $paymentTransactionData->amount;
            $userTokens = UserFcmToken::where('user_id', $user_id)->pluck('fcm_token')->toArray();
            if (!empty($userTokens)) {
                NotificationService::sendFcmNotification($userTokens, $title, $body, 'payment');
            }
            DB::commit();

            return [
                'error'   => false,
                'message' => 'Transaction Verified Successfully'
            ];

        } catch (Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage() . "WebhookController -> assignPackage");
            return [
                'error'   => true,
                'message' => 'Error Occurred'
            ];
        }
    }

    /**
     * Failed Business Logic
     * @param $payment_transaction_id
     * @param $user_id
     * @return array
     */
    private function failedTransaction($payment_transaction_id, $user_id) {
        try {
            $paymentTransactionData = PaymentTransaction::find($payment_transaction_id);
            if (!$paymentTransactionData) {
                return [
                    'error'   => true,
                    'message' => 'Payment Transaction id not found'
                ];
            }

            $paymentTransactionData->update(['payment_status' => "failed"]);

            $body = 'Amount :- ' . $paymentTransactionData->amount;
            $userTokens = UserFcmToken::where('user_id', $user_id)->pluck('fcm_token')->toArray();
            NotificationService::sendFcmNotification($userTokens, 'Package Payment Failed', $body, 'payment');
            return [
                'error'   => false,
                'message' => 'Transaction Verified Successfully'
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage() . "WebhookController -> failedTransaction");
            return [
                'error'   => true,
                'message' => 'Error Occurred'
            ];
        }
    }
}

