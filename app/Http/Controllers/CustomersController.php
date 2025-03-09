<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPurchasedPackage;
use App\Services\BootstrapTableService;
use App\Services\HelperService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CustomersController extends Controller {
    public function index() {
        ResponseService::noAnyPermissionThenRedirect(['customer-list', 'customer-update']);
        $packages = Package::all()->where('status', 1);
        $settings = Setting::whereIn('name', ['currency_symbol', 'currency_symbol_position','free_ad_listing'])
        ->pluck('value', 'name');
        $currency_symbol = $settings['currency_symbol'] ?? '';
        $currency_symbol_position = $settings['currency_symbol_position'] ?? '';
        $free_ad_listing = $settings['free_ad_listing'] ?? '';
        $itemListingPackage = $packages->filter(function ($data) {
            return $data->type == "item_listing";
        });
        $advertisementPackage = $packages->filter(function ($data) {
            return $data->type == "advertisement";
        });

        // Get counts for each role
        $expertCount = User::role('Expert')->count();
        $businessCount = User::role('Business')->count();
        $clientCount = User::role('Client')->count();

        return view('customer.index', compact(
            'packages', 
            'itemListingPackage', 
            'advertisementPackage',
            'currency_symbol',
            'currency_symbol_position',
            'free_ad_listing',
            'expertCount',
            'businessCount',
            'clientCount'
        ));
    }

    public function update(Request $request) {
        try {
            ResponseService::noPermissionThenSendJson('customer-update');
            User::where('id', $request->id)->update(['status' => $request->status]);
            $message = $request->status ? "Customer Activated Successfully" : "Customer Deactivated Successfully";
            ResponseService::successResponse($message);
        } catch (Throwable) {
            ResponseService::errorRedirectResponse('Something Went Wrong ');
        }
    }

    public function show(Request $request) {
        ResponseService::noPermissionThenSendJson('customer-list');
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';
        
        // Get the user role from the request, default to 'Client' if not specified
        $role = $request->role ?? 'Client';
        
        if ($request->notification_list) {
            $sql = User::role($role)->orderBy($sort, $order)->has('fcm_tokens')->where('notification', 1);
        } else {
            $sql = User::role($role);
        }

        if (!empty($request->search)) {
            $sql = $sql->search($request->search);
        }

        $total = $sql->count();
        $sql->skip($offset)->take($limit);
        $result = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($result as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['status'] = empty($row->deleted_at);
            $tempRow['is_verified'] = $row->is_verified;
            $tempRow['auto_approve_item'] = $row->auto_approve_item;
            $tempRow['role'] = $role;

            if (config('app.demo_mode')) {
                // Get the first two digits, Apply enough asterisks to cover the middle numbers ,  Get the last two digits;
                if (!empty($row->mobile)) {
                    $tempRow['mobile'] = substr($row->mobile, 0, 3) . str_repeat('*', (strlen($row->mobile) - 5)) . substr($row->mobile, -2);
                }

                if (!empty($row->email)) {
                    $tempRow['email'] = substr($row->email, 0, 3) . '****' . substr($row->email, strpos($row->email, "@"));
                }
            }

            $tempRow['operate'] = BootstrapTableService::button(
                'fa fa-cart-plus',
                route('customer.assign.package', $row->id),
                ['btn-outline-danger', 'assign_package'],
                [
                    'title'          => __("Assign Package"),
                    "data-bs-target" => "#assignPackageModal",
                    "data-bs-toggle" => "modal"
                ]
            );
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * List users by role
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listByRole(Request $request) {
        ResponseService::noPermissionThenSendJson('customer-list');
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';
        
        // Get the user role from the request, default to 'Client' if not specified
        $role = $request->role ?? 'Client';
        
        // Debug the request parameters
        Log::info('List by role request', [
            'role' => $role,
            'offset' => $offset,
            'limit' => $limit,
            'sort' => $sort,
            'order' => $order,
            'search' => $request->search
        ]);
        
        if ($request->notification_list) {
            $sql = User::role($role)->orderBy($sort, $order)->has('fcm_tokens')->where('notification', 1);
        } else {
            $sql = User::role($role)->orderBy($sort, $order);
        }

        if (!empty($request->search)) {
            $sql = $sql->search($request->search);
        }

        // We'll check for relationships in a safer way
        try {
            // Add relationships based on role if they exist
            if ($role === 'Expert' || $role === 'Business') {
                // For Experts and Business, include their services/experiences if the relationship exists
                $sql = $sql->with(['items']);
            }
        } catch (\Exception $e) {
            Log::error('Error loading relationships: ' . $e->getMessage());
        }

        $total = $sql->count();
        $sql->skip($offset)->take($limit);
        $result = $sql->get();
        
        // Debug the result
        Log::info('List by role result', [
            'total' => $total,
            'count' => count($result)
        ]);
        
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($result as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['status'] = empty($row->deleted_at);
            $tempRow['is_verified'] = $row->is_verified ?? 0;
            $tempRow['auto_approve_item'] = $row->auto_approve_item ?? 0;
            $tempRow['role'] = $role;
            
            // Add role-specific fields
            if ($role === 'Expert') {
                // Expert-specific fields
                $tempRow['gender'] = ''; // Not in database
                $tempRow['location'] = $row->address ?? '';
                $tempRow['categories'] = '';
                $tempRow['expertise'] = ''; // Not in database
                $tempRow['experience'] = ''; // Not in database
                $tempRow['services_count'] = isset($row->items) ? count($row->items) : 0;
                $tempRow['has_subscription'] = false; // Default to false since we can't check
            } elseif ($role === 'Business') {
                // Business-specific fields
                $tempRow['business_name'] = $row->name;
                $tempRow['location'] = $row->address ?? '';
                $tempRow['categories'] = '';
                $tempRow['services_count'] = isset($row->items) ? count($row->items) : 0;
                $tempRow['has_subscription'] = false; // Default to false since we can't check
            } elseif ($role === 'Client') {
                // Client-specific fields
                $tempRow['gender'] = ''; // Not in database
                $tempRow['location'] = $row->address ?? '';
                $tempRow['bookings_count'] = 0; // Default to 0 since we can't check
            }

            if (config('app.demo_mode')) {
                // Get the first two digits, Apply enough asterisks to cover the middle numbers ,  Get the last two digits;
                if (!empty($row->mobile)) {
                    $tempRow['mobile'] = substr($row->mobile, 0, 3) . str_repeat('*', (strlen($row->mobile) - 5)) . substr($row->mobile, -2);
                }

                if (!empty($row->email)) {
                    $tempRow['email'] = substr($row->email, 0, 3) . '****' . substr($row->email, strpos($row->email, "@"));
                }
            }

            $tempRow['operate'] = BootstrapTableService::button(
                'fa fa-cart-plus',
                route('customer.assign.package', $row->id),
                ['btn-outline-danger', 'assign_package'],
                [
                    'title'          => __("Assign Package"),
                    "data-bs-target" => "#assignPackageModal",
                    "data-bs-toggle" => "modal"
                ]
            );
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        
        // Debug the final response
        Log::info('List by role response', [
            'total' => $bulkData['total'],
            'rows_count' => count($bulkData['rows'])
        ]);
        
        return response()->json($bulkData);
    }

    public function assignPackage(Request $request) {
        $validator = Validator::make($request->all(), [
            'package_id'      => 'required',
            'payment_gateway' => 'required|in:cash,cheque',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            ResponseService::noPermissionThenSendJson('customer-list');
            $user = User::find($request->user_id);
            if (empty($user)) {
                ResponseService::errorResponse('User is not Active');
            }
            $package = Package::findOrFail($request->package_id);
            // Create a new payment transaction
            $paymentTransaction = PaymentTransaction::create([
                'user_id'         => $request->user_id,
                'package_id'      => $request->package_id,
                'amount'          => $package->final_price,
                'order_id'        => null,
                'payment_gateway' => $request->payment_gateway,
                'payment_status'  => 'succeed',
            ]);

            // Create a new user purchased package record
            UserPurchasedPackage::create([
                'user_id'                 => $request->user_id,
                'package_id'              => $request->package_id,
                'start_date'              => Carbon::now(),
                'end_date'                => $package->duration == "unlimited" ? null :Carbon::now()->addDays($package->duration),
                'total_limit'             => $package->item_limit == "unlimited" ? null : $package->item_limit,
                'used_limit'              => 0,
                'payment_transactions_id' => $paymentTransaction->id,
            ]);
            DB::commit();
            ResponseService::successResponse('Package assigned to user Successfully');
        } catch (Throwable $th) {
            DB::rollback();
            ResponseService::logErrorResponse($th, "CustomersController --> assignPackage");
            ResponseService::errorResponse();
        }
    }

    /**
     * Display the clients view
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function clients()
    {
        ResponseService::noPermissionThenRedirect('customer-list');
        return view('customer.clients');
    }

    /**
     * Display the business view
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function business()
    {
        ResponseService::noPermissionThenRedirect('customer-list');
        return view('customer.business');
    }

    /**
     * Display the experts view
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function experts()
    {
        ResponseService::noPermissionThenRedirect('customer-list');
        return view('customer.experts');
    }
}
