<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemCustomFieldValue;
use App\Models\UserFcmToken;
use App\Services\BootstrapTableService;
use App\Services\FileService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;
use App\Models\Notifications;

class ItemController extends Controller {

    // Service Items View
    public function serviceItems() {
        ResponseService::noAnyPermissionThenRedirect(['item-list', 'item-update', 'item-delete']);
        return view('items.service_index');
    }

    // Experience Items View
    public function experienceItems() {
        ResponseService::noAnyPermissionThenRedirect(['item-list', 'item-update', 'item-delete']);
        return view('items.experience_index');
    }

    // Original index method (can be kept for backward compatibility)
    public function index() {
        ResponseService::noAnyPermissionThenRedirect(['item-list', 'item-update', 'item-delete']);
        return view('items.index');
    }

    // Data for service items
    public function serviceItemsData(Request $request) {
        return $this->getItemsData($request, 'service');
    }

    // Data for experience items
    public function experienceItemsData(Request $request) {
        return $this->getItemsData($request, 'experience');
    }

    // Common method to get items data
    private function getItemsData(Request $request, $type) {
        try {
            ResponseService::noPermissionThenSendJson('item-list');
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);
            $sort = $request->input('sort', 'sequence');
            $order = $request->input('order', 'ASC');
            
            $sql = Item::with(['custom_fields', 'category:id,name', 'user:id,name', 'gallery_images'])
                      ->where('provider_item_type', $type)
                      ->withTrashed();
                      
            if (!empty($request->search)) {
                $sql = $sql->search($request->search);
            }

            if (!empty($request->filter)) {
                $sql = $sql->filter(json_decode($request->filter, false, 512, JSON_THROW_ON_ERROR));
            }

            $total = $sql->count();
            $sql = $sql->sort($sort, $order)->skip($offset)->take($limit);
            $result = $sql->get();

            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();

            $itemCustomFieldValues = ItemCustomFieldValue::whereIn('item_id', $result->pluck('id'))->get();
            foreach ($result as $row) {
                /* Merged ItemCustomFieldValue's data to main data */
                $itemCustomFieldValue = $itemCustomFieldValues->filter(function ($data) use ($row) {
                    return $data->item_id == $row->id;
                });

                $row->custom_fields = collect($row->custom_fields)->map(function ($customField) use ($itemCustomFieldValue) {
                    $customField['value'] = $itemCustomFieldValue->first(function ($data) use ($customField) {
                        return $data->custom_field_id == $customField->id;
                    });

                    if ($customField->type == "fileinput" && !empty($customField['value']->value)) {
                        if (!is_array($customField->value)) {
                            $customField['value'] = !empty($customField->value) ? [url(Storage::url($customField->value))] : [];
                        } else {
                            $customField['value'] = null;
                        }
                    }
                    return $customField;
                });
                $tempRow = $row->toArray();
                $operate = '';
                if (count($row->custom_fields) > 0 && Auth::user()->can('item-list')) {
                    // View Custom Field
                    $operate .= BootstrapTableService::button('fa fa-eye', '#', ['editdata', 'btn-light-danger  '], ['title' => __("View"), "data-bs-target" => "#editModal", "data-bs-toggle" => "modal",]);
                }

                if ($row->status !== 'sold out' && Auth::user()->can('item-update')) {
                    $operate .= BootstrapTableService::editButton(route('item.approval', $row->id), true, '#editStatusModal', 'edit-status', $row->id);
                }
                if (Auth::user()->can('item-delete')) {
                    $operate .= BootstrapTableService::deleteButton(route('item.destroy', $row->id));
                }
                $tempRow['active_status'] = empty($row->deleted_at);//IF deleted_at is empty then status is true else false
                $tempRow['operate'] = $operate;

                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;
            return response()->json($bulkData);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "ItemController --> getItemsData");
            ResponseService::errorResponse();
        }
    }

    // Original show method (can be kept for backward compatibility)
    public function show(Request $request) {
        try {
            ResponseService::noPermissionThenSendJson('item-list');
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);
            $sort = $request->input('sort', 'sequence');
            $order = $request->input('order', 'ASC');
            $sql = Item::with(['custom_fields', 'category:id,name', 'user:id,name', 'gallery_images'])->withTrashed();
            if (!empty($request->search)) {
                $sql = $sql->search($request->search);
            }

            if (!empty($request->filter)) {
                $sql = $sql->filter(json_decode($request->filter, false, 512, JSON_THROW_ON_ERROR));
            }

            $total = $sql->count();
            $sql = $sql->sort($sort, $order)->skip($offset)->take($limit);
            $result = $sql->get();

            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();

            $itemCustomFieldValues = ItemCustomFieldValue::whereIn('item_id', $result->pluck('id'))->get();
            foreach ($result as $row) {
                /* Merged ItemCustomFieldValue's data to main data */
                $itemCustomFieldValue = $itemCustomFieldValues->filter(function ($data) use ($row) {
                    return $data->item_id == $row->id;
                });

                $row->custom_fields = collect($row->custom_fields)->map(function ($customField) use ($itemCustomFieldValue) {
                    $customField['value'] = $itemCustomFieldValue->first(function ($data) use ($customField) {
                        return $data->custom_field_id == $customField->id;
                    });

                    if ($customField->type == "fileinput" && !empty($customField['value']->value)) {
                        if (!is_array($customField->value)) {
                            $customField['value'] = !empty($customField->value) ? [url(Storage::url($customField->value))] : [];
                        } else {
                            $customField['value'] = null;
                        }
                    }
                    return $customField;
                });
                $tempRow = $row->toArray();
                $operate = '';
                if (count($row->custom_fields) > 0 && Auth::user()->can('item-list')) {
                    // View Custom Field
                    $operate .= BootstrapTableService::button('fa fa-eye', '#', ['editdata', 'btn-light-danger  '], ['title' => __("View"), "data-bs-target" => "#editModal", "data-bs-toggle" => "modal",]);
                }

                if ($row->status !== 'sold out' && Auth::user()->can('item-update')) {
                    $operate .= BootstrapTableService::editButton(route('item.approval', $row->id), true, '#editStatusModal', 'edit-status', $row->id);
                }
                if (Auth::user()->can('item-delete')) {
                    $operate .= BootstrapTableService::deleteButton(route('item.destroy', $row->id));
                }
                $tempRow['active_status'] = empty($row->deleted_at);//IF deleted_at is empty then status is true else false
                $tempRow['operate'] = $operate;

                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;
            return response()->json($bulkData);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "ItemController --> show");
            ResponseService::errorResponse();
        }
    }

    // Method to get a single item (if needed)
    public function showItem($id) {
        try {
            ResponseService::noPermissionThenSendJson('item-list');
            $item = Item::with(['custom_fields', 'category:id,name', 'user:id,name', 'gallery_images'])->findOrFail($id);
            return response()->json($item);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "ItemController --> showItem");
            ResponseService::errorResponse();
        }
    }

    public function updateItemApproval(Request $request, $id) {
        try {
            ResponseService::noPermissionThenSendJson('item-update');
            $item = Item::with('user')->withTrashed()->findOrFail($id);
            $item->update([
                ...$request->all(),
                'rejected_reason' => ($request->status == "rejected") ? $request->rejected_reason : ''
            ]);
            $user_token = UserFcmToken::where('user_id', $item->user->id)->pluck('fcm_token')->toArray();
            
            // Create notification in the database
            $notificationTitle = 'About ' . $item->name;
            $notificationMessage = "Your Item is " . ucfirst($request->status);
            
            // Create notification record
            Notifications::create([
                'title' => $notificationTitle,
                'message' => $notificationMessage,
                'item_id' => $item->id,
                'user_id' => $item->user->id,
                'send_to' => 'selected'
            ]);
            
            // Send FCM notification
            if (!empty($user_token)) {
                NotificationService::sendFcmNotification($user_token, $notificationTitle, $notificationMessage, "item-update", ['id' => $item->id]);
            }
            ResponseService::successResponse('Item Status Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemController ->updateItemApproval');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function destroy($id) {
        ResponseService::noPermissionThenSendJson('item-delete');

        try {
            $item = Item::with('gallery_images')->withTrashed()->findOrFail($id);
            foreach ($item->gallery_images as $gallery_image) {
                FileService::delete($gallery_image->getRawOriginal('image'));
            }
            FileService::delete($item->getRawOriginal('image'));

            $item->forceDelete();

            ResponseService::successResponse('Item deleted successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse('Something went wrong');
        }
    }
}
