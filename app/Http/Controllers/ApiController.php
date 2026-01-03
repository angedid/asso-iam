<?php

namespace App\Http\Controllers;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\Order;
use App\Domain\Model\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Nanorocks\LicenseManager\Services\LicenseService;

class ApiController extends Controller
{
    public $licenseService;
    public function __construct()
    {
        $this->middleware('rabbitmq.client');
        $this->licenseService = app(LicenseService::class);
    }

    public function makeServicePayment(Request $request)
    {
        //$requestBodyString = file_get_contents('php://input');
        $requestBodyString = $request->getContent(); //file_get_contents('php://input');

        $paymentData =  json_decode($requestBodyString, true);

        $payment = Payment::create(
            [
                'id'=>$paymentData['id'],
                'tenant_id'=>$paymentData['tenant_id'],
                'holder_id'=>$paymentData['holder_id'],
                'payment_service_provider_id'=>$paymentData['payment_service_provider_id'],
                'account_used'=> $paymentData['account_used'],
                'amount'=> $paymentData['amount'],
                'currency_id'=>$paymentData['currency_id'],
                'order_number'=>$paymentData['order_number'],
                'start_date'=> Carbon::parse($paymentData['start_date']),
                'end_date'=> $paymentData['end_date'] === null ? null : Carbon::parse($paymentData['end_date']),
                'status'=> $paymentData['status'],
                'details'=> $paymentData['details'],
            ]);

        $user = User::where('id', $paymentData['holder_id'])->first();
        $licenseGenerated = DomainServices::generateLicense($user, $payment);
        return response(array('success' => 1, 'failure' => 0, 'response' => ['message'=>'Payment Saved Successfully',
        'body'=>json_encode($licenseGenerated)]), 200);
    }

    public function placeOrder(Request $request)
    {
        $ordertoregisterArray = json_decode($request->getContent(), true);
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:tenants,id',
            'merchant_id' => 'required|exists:users,id',
            'license_type_id' => 'required|uuid',
            'amount' => 'required|numeric',
            'discount' => 'required|numeric:between:0,99.99',
            'final_amount'=>'required|numeric',
            'status' => 'required|in:PLACED',
            'placed_at'=>'required',
        ],
            [
                'tenant_id.required' => __("Le tenant est obligatoire"),
                'tenant_id.exists' => __("Tenant inconnu"),
                'merchant_id.required' => __("Le marchand est obligatoire"),
                'merchant_id.exists' => __("Merchant inconnu"),
                'license_type_id.required' => __("Le type de licence est obligatoire"),
                'license_type_id.exists' => __("Type de licence inconnu"),
                'amount.required' => __("Le montant est obligatoire"),
                'amount.numeric' => __("Le montant est un nombre"),
                'discount.required' => __("La reduction est obligatoire"),
                'discount.numeric' => __("La reduction est un nombre"),
                'discount.between' => __("La reduction est un nombre compris entre 0 et 99.99"),
            ]);

        if ($validator->fails()) {
            return response(GlobalResultHandler::buildFaillureReasonArray($validator->errors()->first()), 200);
        }

        Order::create([
            'id' => $ordertoregisterArray['id'],
            'tenant_id' => $ordertoregisterArray['tenant_id'],
            'merchant_id' => $ordertoregisterArray['merchant_id'],
            'license_type_id' => $ordertoregisterArray['license_type_id'],
            'amount' => $ordertoregisterArray['amount'],
            'discount' => $ordertoregisterArray['discount'],
            'final_amount' => $ordertoregisterArray['final_amount'],
            'placed_at' => Carbon::parse($ordertoregisterArray['placed_at']),//->format('Y-m-d H:i:s'),
            'status' => $ordertoregisterArray['status'],
            'paid_at' => null,
            'cancelled_at' => null,
            'active' => $ordertoregisterArray['active'],
            'items' => $ordertoregisterArray['items'],
        ]);

        return response(GlobalResultHandler::buildSuccesResponseArray('Order placed successfully'), 202);
    }

    public function updateOrderOrderPaid(Request $request, string $order_id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:COMPLETED',
        ]);
        if ($validator->fails()) {
            return response(GlobalResultHandler::buildFaillureReasonArray($validator->errors()->first()), 200);
        }
        $orderPaid = json_decode($request->getContent(), true);
        $status = $orderPaid['status'];
        $order = Order::where('id', $order_id)->first();
        if ($order === null) {
            return response(GlobalResultHandler::buildFaillureReasonArray('Order not found'), 200);
        }
        $order->status = $status;
        $order->save();

        return response(GlobalResultHandler::buildSuccesResponseArray('Order paid successfully'), 200);
    }

    public function updateOrderOrderCancelled(Request $request, string $order_id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:CANCELLED',
        ]);
        if ($validator->fails()) {
            return response(GlobalResultHandler::buildFaillureReasonArray($validator->errors()->first()), 200);
        }
        $orderCancelled = json_decode($request->getContent(), true);
        $status = $orderCancelled['status'];
        $order = Order::where('id', $order_id)->first();
        if ($order === null) {
            return response(GlobalResultHandler::buildFaillureReasonArray('Order not found'), 200);
        }
        $order->status = $status;
        $order->save();
        return response(GlobalResultHandler::buildSuccesResponseArray('Order paid successfully'), 200);
    }
}
