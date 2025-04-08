<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use JsonSerializable;
use Throwable;

class UserCollection extends ResourceCollection {
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     * @throws Throwable
     */
    public function toArray(Request $request) {
        try {
            $response = [];
            foreach ($this->collection as $key => $collection) {
                $response[$key] = $collection->toArray();
                
                // Add featured status flag
                if ($collection->relationLoaded('featured_users')) {
                    $response[$key]['is_featured'] = count($collection->featured_users) > 0;
                } else {
                    $response[$key]['is_featured'] = false;
                }
                
                // Process roles into a simpler format
                if ($collection->relationLoaded('roles')) {
                    $response[$key]['roles'] = $collection->roles->pluck('name')->toArray();
                }
                
                // Process items count
                if ($collection->relationLoaded('items')) {
                    $response[$key]['items_count'] = $collection->items->count();
                }
            }
            
            return $response;
        } catch (Throwable $e) {
            return [];
        }
    }
} 