<?php

namespace App\Services;

use App\Models\User;
use App\Models\Item;
use App\Models\UserPurchasedPackage;
use App\Models\PaymentTransaction;
use App\Models\ItemImages;
use App\Models\ItemCustomFieldValue;
use App\Models\FeaturedItems;
use App\Models\FeaturedUsers;
use App\Models\UserReports;
use App\Models\Favourite;
use App\Models\ItemOffer;
use App\Models\ServiceReview;
use App\Models\UserFcmToken;
use App\Models\Notifications;
use App\Models\SellerRating;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserDeletionService
{
    /**
     * Delete a user and all their related data
     *
     * @param int $userId
     * @return bool
     */
    public static function deleteUser(int $userId): bool
    {
        try {
            // Begin transaction to ensure all-or-nothing deletion
            DB::beginTransaction();
            
            // Find the user
            $user = User::withTrashed()->findOrFail($userId);
            
            // Log the user roles for debugging purposes
            $userRoles = $user->getRoleNames()->toArray();
            Log::info('Starting comprehensive deletion for user', [
                'user_id' => $userId,
                'roles' => $userRoles,
                'email' => $user->email,
                'business_name' => $user->business_name ?? null,
                'deletion_by' => auth()->id() ?? 'system'
            ]);
            
            try {
                // 1. Delete all user's listings (items)
                Log::info("Starting to delete user's items", ['user_id' => $userId]);
                self::deleteUserItems($userId);
                Log::info("Finished deleting user's items", ['user_id' => $userId]);
                
                // 2. Delete user's purchased packages
                Log::info("Starting to delete user's packages", ['user_id' => $userId]);
                self::deleteUserPackages($userId);
                Log::info("Finished deleting user's packages", ['user_id' => $userId]);
                
                // 3. Delete payment transactions
                Log::info("Starting to delete user's payment transactions", ['user_id' => $userId]);
                self::deletePaymentTransactions($userId);
                Log::info("Finished deleting user's payment transactions", ['user_id' => $userId]);
                
                // 4. Delete featured statuses (both items and user)
                Log::info("Starting to delete user's featured statuses", ['user_id' => $userId]);
                self::deleteFeaturedStatus($userId);
                Log::info("Finished deleting user's featured statuses", ['user_id' => $userId]);
                
                // 5. Delete user reports
                Log::info("Starting to delete user's reports", ['user_id' => $userId]);
                self::deleteUserReports($userId);
                Log::info("Finished deleting user's reports", ['user_id' => $userId]);
                
                // 6. Delete user favorites
                Log::info("Starting to delete user's favorites", ['user_id' => $userId]);
                self::deleteUserFavorites($userId);
                Log::info("Finished deleting user's favorites", ['user_id' => $userId]);
                
                // 7. Delete notifications
                Log::info("Starting to delete user's notifications", ['user_id' => $userId]);
                self::deleteUserNotifications($userId);
                Log::info("Finished deleting user's notifications", ['user_id' => $userId]);
                
                // 8. Delete FCM tokens
                Log::info("Starting to delete user's FCM tokens", ['user_id' => $userId]);
                self::deleteUserFcmTokens($userId);
                Log::info("Finished deleting user's FCM tokens", ['user_id' => $userId]);
                
                // Special handling for business users
                if (in_array('Business', $userRoles)) {
                    Log::info("User is a Business, performing special business data cleanup", ['user_id' => $userId]);
                    self::deleteBusinessData($userId);
                }
                
                // 9. Delete user role and permission assignments from Spatie tables
                Log::info("Starting to delete user's roles and permissions", ['user_id' => $userId]);
                $roleCount = DB::table('model_has_roles')
                    ->where('model_id', $userId)
                    ->where('model_type', 'App\\Models\\User')
                    ->count();
                Log::info("Found {$roleCount} roles to delete", ['user_id' => $userId]);
                
                $roleDeleteResult = DB::table('model_has_roles')
                    ->where('model_id', $userId)
                    ->where('model_type', 'App\\Models\\User')
                    ->delete();
                Log::info("Role deletion result: {$roleDeleteResult}", ['user_id' => $userId]);
                
                $permCount = DB::table('model_has_permissions')
                    ->where('model_id', $userId)
                    ->where('model_type', 'App\\Models\\User')
                    ->count();
                Log::info("Found {$permCount} permissions to delete", ['user_id' => $userId]);
                
                $permDeleteResult = DB::table('model_has_permissions')
                    ->where('model_id', $userId)
                    ->where('model_type', 'App\\Models\\User')
                    ->delete();
                Log::info("Permission deletion result: {$permDeleteResult}", ['user_id' => $userId]);
                
                // 10. Check for any other relationships that might be blocking deletion
                Log::info("Checking for other potential relationships", ['user_id' => $userId]);
                
                // Log tables that might have foreign key relationships to users
                $tables = [
                    'personal_access_tokens',
                    'oauth_access_tokens',
                    'password_resets',
                    'model_has_roles',
                    'model_has_permissions',
                    'sessions'
                ];
                
                // Add business-specific tables that might have relations
                if (in_array('Business', $userRoles)) {
                    Log::info("User is a Business, checking business-specific relations", ['user_id' => $userId]);
                    $businessTables = [
                        'business_profiles',
                        'business_services',
                        'business_categories',
                        'business_hours',
                        'business_media',
                        'business_reviews',
                        'business_employees',
                        'business_locations'
                    ];
                    
                    // Add business tables to the list of tables to check
                    $tables = array_merge($tables, $businessTables);
                }
                
                foreach ($tables as $table) {
                    try {
                        if (DB::connection()->getSchemaBuilder()->hasTable($table)) {
                            // Check if the table has a user_id column or similar
                            $hasUserIdColumn = false;
                            $columns = DB::connection()->getSchemaBuilder()->getColumnListing($table);
                            
                            foreach ($columns as $column) {
                                if (in_array($column, ['user_id', 'model_id'])) {
                                    $hasUserIdColumn = true;
                                    $count = DB::table($table)
                                        ->where($column, $userId)
                                        ->count();
                                    
                                    if ($count > 0) {
                                        Log::info("Found {$count} records in {$table} for user {$userId}", ['column' => $column]);
                                        
                                        // Try to delete these records
                                        $deleteResult = DB::table($table)
                                            ->where($column, $userId)
                                            ->delete();
                                        
                                        Log::info("Deleted {$deleteResult} records from {$table}", ['user_id' => $userId]);
                                    }
                                }
                            }
                            
                            if (!$hasUserIdColumn) {
                                Log::info("Table {$table} does not have a user_id or model_id column");
                            }
                        } else {
                            Log::info("Table {$table} does not exist");
                        }
                    } catch (Throwable $e) {
                        Log::warning("Error checking table {$table}", [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // 11. Directly delete the user record using DB facade to bypass soft deletes
                Log::info("Attempting to delete user record with ID {$userId}");
                
                try {
                    $result = DB::statement('DELETE FROM users WHERE id = ?', [$userId]);
                    Log::info("User deletion SQL result: " . ($result ? 'Success' : 'Failed'), ['user_id' => $userId]);
                    
                    // Double-check if user still exists
                    $stillExists = DB::table('users')->where('id', $userId)->exists();
                    Log::info("User still exists after deletion: " . ($stillExists ? 'Yes' : 'No'), ['user_id' => $userId]);
                    
                    if ($stillExists) {
                        // Last resort - try alternative deletion approaches
                        Log::info("Trying alternative deletion approach", ['user_id' => $userId]);
                        
                        // Try Eloquent force delete one more time
                        if ($user) {
                            try {
                                $user->forceDelete();
                                Log::info("Eloquent forceDelete result", ['user_id' => $userId]);
                            } catch (Throwable $e) {
                                Log::error("Error in Eloquent forceDelete", [
                                    'user_id' => $userId,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                        
                        // Final check if user exists
                        $finalExists = DB::table('users')->where('id', $userId)->exists();
                        Log::info("User exists after all deletion attempts: " . ($finalExists ? 'Yes' : 'No'), ['user_id' => $userId]);
                    }
                } catch (Throwable $e) {
                    Log::error("Error during final user deletion", [
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            } catch (Throwable $e) {
                Log::error("Error during deletion process", [
                    'user_id' => $userId,
                    'stage' => 'User Data Deletion',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
            
            // If all operations are successful, commit the transaction
            DB::commit();
            
            Log::info('User and all related data deleted successfully', [
                'user_id' => $userId
            ]);
            
            return true;
        } catch (Throwable $e) {
            // If any operation fails, rollback the entire transaction
            DB::rollBack();
            
            Log::error('Error deleting user and related data', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Delete all items (listings) created by a user
     *
     * @param int $userId
     * @return void
     */
    private static function deleteUserItems(int $userId): void
    {
        // Get all user's items
        $items = Item::where('user_id', $userId)->get();
        
        foreach ($items as $item) {
            // Delete item images first
            ItemImages::where('item_id', $item->id)->delete();
            
            // Delete custom field values
            ItemCustomFieldValue::where('item_id', $item->id)->delete();
            
            // Delete item offers
            ItemOffer::where('item_id', $item->id)->delete();
            
            // Delete service reviews
            ServiceReview::where('service_id', $item->id)->delete();
            
            // Delete seller ratings for this item
            SellerRating::where('item_id', $item->id)->delete();
            
            // Delete the item itself
            $item->delete();
        }
    }
    
    /**
     * Delete user's purchased packages
     *
     * @param int $userId
     * @return void
     */
    private static function deleteUserPackages(int $userId): void
    {
        UserPurchasedPackage::where('user_id', $userId)->delete();
    }
    
    /**
     * Delete user's payment transactions
     *
     * @param int $userId
     * @return void
     */
    private static function deletePaymentTransactions(int $userId): void
    {
        PaymentTransaction::where('user_id', $userId)->delete();
    }
    
    /**
     * Delete user's featured status (both items and user)
     *
     * @param int $userId
     * @return void
     */
    private static function deleteFeaturedStatus(int $userId): void
    {
        // Get all user's items
        $itemIds = Item::where('user_id', $userId)->pluck('id')->toArray();
        
        // Delete featured items
        if (!empty($itemIds)) {
            FeaturedItems::whereIn('item_id', $itemIds)->delete();
        }
        
        // Delete featured user status
        FeaturedUsers::where('user_id', $userId)->delete();
    }
    
    /**
     * Delete user reports related to this user
     *
     * @param int $userId
     * @return void
     */
    private static function deleteUserReports(int $userId): void
    {
        try {
            // Delete reports made by this user
            UserReports::where('user_id', $userId)->delete();
            
            // Check if the reported_user_id column exists
            $columns = DB::getSchemaBuilder()->getColumnListing('user_reports');
            
            Log::info('User reports table columns:', ['columns' => $columns]);
            
            // Delete reports made against this user (only if the column exists)
            if (in_array('reported_user_id', $columns)) {
                UserReports::where('reported_user_id', $userId)->delete();
            } else {
                // Check for other possible column names
                $possibleColumns = ['reported_id', 'target_user_id', 'target_id'];
                
                foreach ($possibleColumns as $column) {
                    if (in_array($column, $columns)) {
                        Log::info("Found alternative column for reported user: {$column}", ['user_id' => $userId]);
                        UserReports::where($column, $userId)->delete();
                        break;
                    }
                }
            }
        } catch (Throwable $e) {
            Log::warning("Error deleting user reports", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete user favorites
     *
     * @param int $userId
     * @return void
     */
    private static function deleteUserFavorites(int $userId): void
    {
        Favourite::where('user_id', $userId)->delete();
    }
    
    /**
     * Delete user notifications
     *
     * @param int $userId
     * @return void
     */
    private static function deleteUserNotifications(int $userId): void
    {
        Notifications::where('user_id', $userId)->delete();
    }
    
    /**
     * Delete user FCM tokens
     *
     * @param int $userId
     * @return void
     */
    private static function deleteUserFcmTokens(int $userId): void
    {
        UserFcmToken::where('user_id', $userId)->delete();
    }
    
    /**
     * Delete business-specific data
     *
     * @param int $userId
     * @return void
     */
    private static function deleteBusinessData(int $userId): void
    {
        try {
            Log::info("Starting to delete business-specific data", ['user_id' => $userId]);
            
            // Try to find any business relations in the database
            $tables = DB::select("SHOW TABLES");
            
            // Format varies by database driver
            $prefix = 'Tables_in_' . env('DB_DATABASE');
            
            foreach ($tables as $table) {
                $tableName = $table->$prefix ?? (isset($table->table_name) ? $table->table_name : null);
                
                if (!$tableName) {
                    continue;
                }
                
                // If it looks like a business-related table
                if (strpos($tableName, 'business') !== false) {
                    Log::info("Checking business table: {$tableName}", ['user_id' => $userId]);
                    
                    // Get columns for this table
                    $columns = DB::getSchemaBuilder()->getColumnListing($tableName);
                    
                    // Look for user_id or similar columns
                    foreach ($columns as $column) {
                        if (in_array($column, ['user_id', 'owner_id', 'business_id'])) {
                            // Check if any records exist for this user
                            $count = DB::table($tableName)
                                ->where($column, $userId)
                                ->count();
                                
                            if ($count > 0) {
                                Log::info("Found {$count} records in {$tableName} for business user {$userId}", ['column' => $column]);
                                
                                // Delete these records
                                $deleted = DB::table($tableName)
                                    ->where($column, $userId)
                                    ->delete();
                                    
                                Log::info("Deleted {$deleted} records from {$tableName}", ['user_id' => $userId, 'column' => $column]);
                            }
                        }
                    }
                }
            }
            
            Log::info("Finished deleting business-specific data", ['user_id' => $userId]);
        } catch (Throwable $e) {
            Log::error("Error deleting business data", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
} 