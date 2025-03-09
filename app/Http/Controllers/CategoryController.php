<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\CustomField;
use App\Models\CustomFieldCategory;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\FileService;
use App\Services\HelperService;
use App\Services\ResponseService;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;
use function compact;
use function view;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller {
    private string $uploadFolder;

    public function __construct() {
        $this->uploadFolder = "category";
    }

    public function index() {
        ResponseService::noAnyPermissionThenRedirect(['category-list', 'category-create', 'category-update', 'category-delete']);
        return view('category.index');
    }

    public function create(Request $request) {
        $languages = CachingService::getLanguages()->where('code', '!=', 'en')->values();
        ResponseService::noPermissionThenRedirect('category-create');
        return view('category.create', compact('languages'));
    }

    public function store(Request $request) {
        ResponseService::noPermissionThenSendJson('category-create');
        $request->validate([
            'name'               => 'required',
            'image'              => 'required|mimes:jpg,jpeg,png|max:6144',
            'parent_category_id' => 'nullable|integer',
            'description'        => 'nullable',
            'slug'               => 'required',
            'status'             => 'required|boolean',
            'translations'       => 'nullable|array',
            'translations.*'     => 'nullable|string',
            'type'               => 'required|in:service_experience,providers',
        ]);

        try {
            $data = $request->all();
            $data['slug'] = HelperService::generateUniqueSlug(new Category(), $request->slug);

            if ($request->hasFile('image')) {
                $data['image'] = FileService::compressAndUpload($request->file('image'), $this->uploadFolder);
            }

            $category = Category::create($data);

            if (!empty($request->translations)) {
                foreach ($request->translations as $key => $value) {
                    if (!empty($value)) {
                        $category->translations()->create([
                            'name'        => $value,
                            'language_id' => $key,
                        ]);
                    }
                }
            }

            ResponseService::successRedirectResponse("Category Added Successfully");
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorRedirectResponse();
        }
    }


    public function show(Request $request, $category) {
        ResponseService::noPermissionThenSendJson('category-list');
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'sequence');
        $order = $request->input('order', 'ASC');
        $type = $request->input('type', 'service_experience');
        
        $sql = Category::withCount('subcategories')
            ->orderBy($sort, $order)
            ->withCount('custom_fields')
            ->with('subcategories')
            ->where('type', $type);
            
        if ($category == "0") {
            $sql->whereNull('parent_category_id');
        } else {
            $sql->where('parent_category_id', $category);
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

        foreach ($result as $key => $row) {
            $operate = '';
            // Check if user has category-update permission
            try {
                ResponseService::noPermissionThenSendJson('category-update');
                $operate .= BootstrapTableService::editButton(route('category.edit', $row->id));
            } catch (\Exception $e) {
                // User doesn't have permission, do nothing
            }

            // Check if user has category-delete permission
            try {
                ResponseService::noPermissionThenSendJson('category-delete');
                $operate .= BootstrapTableService::deleteButton(route('category.destroy', $row->id));
            } catch (\Exception $e) {
                // User doesn't have permission, do nothing
            }
            
            if ($row->subcategories_count > 1) {
                $operate .= BootstrapTableService::button('fa fa-list-ol',route('sub.category.order.change', $row->id),['btn-secondary']);
            }
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $tempRow['items_count'] = $row->all_items_count;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function edit($id) {
        ResponseService::noPermissionThenRedirect('category-update');
        $category_data = Category::findOrFail($id);
        // Fetch translations for the category
        $translations = $category_data->translations->pluck('name', 'language_id')->toArray();

        // Fetch all languages
        $languages = CachingService::getLanguages()->where('code', '!=', 'en')->values();

        return view('category.edit', compact('category_data', 'translations', 'languages'));
    }

    public function update(Request $request, $id) {
        ResponseService::noPermissionThenSendJson('category-update');
        try {
            $request->validate([
                'name'            => 'nullable',
                'image'           => 'nullable|mimes:jpg,jpeg,png|max:6144',
                'parent_category' => 'nullable|integer',
                'description'     => 'nullable',
                'slug'            => 'nullable',
                'status'          => 'required|boolean',
                'translations'    => 'nullable|array',
                'translations.*'  => 'nullable|string',
                'type'            => 'required|in:service_experience,providers',
            ]);

            $category = Category::find($id);

            $data = $request->all();
            if ($request->hasFile('image')) {
                $data['image'] = FileService::compressAndReplace($request->file('image'), $this->uploadFolder, $category->getRawOriginal('image'));
            }
            $data['slug'] = HelperService::generateUniqueSlug(new Category(), $request->slug, $category->id);
            $category->update($data);

            if (!empty($request->translations)) {
                $categoryTranslations = [];
                foreach ($request->translations as $key => $value) {
                    $categoryTranslations[] = [
                        'category_id' => $category->id,
                        'language_id' => $key,
                        'name'        => $value,
                    ];
                }

                if (count($categoryTranslations) > 0) {
                    CategoryTranslation::upsert($categoryTranslations, ['category_id', 'language_id'], ['name']);
                }
            }

            ResponseService::successRedirectResponse("Category Updated Successfully", route('category.index'));
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorRedirectResponse('Something Went Wrong');
        }
    }

    public function destroy($id) {
        ResponseService::noPermissionThenSendJson('category-delete');
        try {
            $category = Category::find($id);
            if ($category->items_count > 0) {
                ResponseService::errorResponse('Cannot delete category. It has associated items.');
            }
            if ($category->delete()) {
                ResponseService::successResponse('Category delete successfully');
            }
        } catch (QueryException $th) {
            ResponseService::logErrorResponse($th, 'Failed to delete category', 'Cannot delete category. Remove associated subcategories and custom fields first.');
            ResponseService::errorResponse('Something Went Wrong');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "CategoryController -> delete");
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function getSubCategories($id) {
        ResponseService::noPermissionThenRedirect('category-list');
        $subcategories = Category::where('parent_category_id', $id)
            ->with('subcategories')
            ->withCount('custom_fields')
            ->withCount('subcategories')
            ->withCount('items')
            ->orderBy('sequence')
            ->get()
            ->map(function ($subcategory) {
                $operate = '';
                // Check if user has category-update permission
                try {
                    ResponseService::noPermissionThenSendJson('category-update');
                    $operate .= BootstrapTableService::editButton(route('category.edit', $subcategory->id));
                } catch (\Exception $e) {
                    // User doesn't have permission, do nothing
                }

                // Check if user has category-delete permission
                try {
                    ResponseService::noPermissionThenSendJson('category-delete');
                    $operate .= BootstrapTableService::deleteButton(route('category.destroy', $subcategory->id));
                } catch (\Exception $e) {
                    // User doesn't have permission, do nothing
                }
                
                if ($subcategory->subcategories_count > 1) {
                    $operate .= BootstrapTableService::button('fa fa-list-ol',route('sub.category.order.change',$subcategory->id),['btn-secondary']);
                }
                $subcategory->operate = $operate;
                return $subcategory;
            });

        return response()->json($subcategories);
    }

    public function customFields($id) {
        ResponseService::noPermissionThenRedirect('custom-field-list');
        $category = Category::find($id);
        $p_id = $category->parent_category_id;
        $cat_id = $category->id;
        $category_name = $category->name;

        return view('category.custom-fields', compact('cat_id', 'category_name', 'p_id'));
    }

    public function getCategoryCustomFields(Request $request, $id) {
        ResponseService::noPermissionThenSendJson('custom-field-list');
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'ASC');

        $sql = CustomField::whereHas('categories', static function ($q) use ($id) {
            $q->where('category_id', $id);
        })->orderBy($sort, $order);

        if (isset($request->search)) {
            $sql->search($request->search);
        }

        $sql->take($limit);
        $total = $sql->count();
        $res = $sql->skip($offset)->take($limit)->get();
        $bulkData = array();
        $rows = array();
        $tempRow['type'] = '';


        foreach ($res as $row) {
            $tempRow = $row->toArray();
//            $operate = BootstrapTableService::editButton(route('custom-fields.edit', $row->id));
            $operate = BootstrapTableService::deleteButton(route('category.custom-fields.destroy', [$id, $row->id]));
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        $bulkData['total'] = $total;
        return response()->json($bulkData);
    }

    public function destroyCategoryCustomField($categoryID, $customFieldID) {
        try {
            ResponseService::noPermissionThenRedirect('custom-field-delete');
            CustomFieldCategory::where(['category_id' => $categoryID, 'custom_field_id' => $customFieldID])->delete();
            ResponseService::successResponse("Custom Field Deleted Successfully");
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "CategoryController -> destroyCategoryCustomField");
            ResponseService::errorResponse('Something Went Wrong');
        }

    }

    public function categoriesReOrder(Request $request) {
        $categories = Category::whereNull('parent_category_id')->orderBy('sequence')->get();
        return view('category.categories-order', compact('categories'));
    }

    public function subCategoriesReOrder(Request $request ,$id) {
        $categories = Category::with('subcategories')->where('parent_category_id', $id)->orderBy('sequence')->get();
        return view('category.sub-categories-order', compact('categories'));
    }

    public function updateOrder(Request $request) {
        $request->validate([
           'order' => 'required|json'
        ]);
        try {

            $order = json_decode($request->input('order'), true);
            $data = [];
        foreach ($order as $index => $id) {
            $data[] = [
                'id' => $id,
                'sequence' => $index + 1,
            ];
        }
        Category::upsert($data, ['id'], ['sequence']);
        ResponseService::successResponse("Order Updated Successfully");
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    /**
     * Get parent categories based on type
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getParentCategories(Request $request)
    {
        try {
            $type = $request->input('type', 'service_experience');
            
            // Get all categories of the selected type
            $categories = Category::where('type', $type)
                ->orderBy('sequence')
                ->get();
            
            // Build a hierarchical structure with level information
            $result = [];
            $this->buildCategoryHierarchy($categories, $result);
            
            return response()->json([
                'success' => true,
                'categories' => $result,
                'count' => count($result),
                'type' => $type
            ]);
        } catch (Throwable $th) {
            Log::error("CategoryController -> getParentCategories: " . $th->getMessage() . ' --> ' . $th->getFile() . ' At Line : ' . $th->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $th->getMessage(),
                'error' => $th->getMessage()
            ]);
        }
    }
    
    /**
     * Build a hierarchical structure of categories with level information
     * 
     * @param \Illuminate\Database\Eloquent\Collection $categories
     * @param array $result
     * @param int|null $parentId
     * @param int $level
     * @return void
     */
    private function buildCategoryHierarchy($categories, &$result, $parentId = null, $level = 0)
    {
        $filteredCategories = $categories->filter(function ($category) use ($parentId) {
            return $category->parent_category_id == $parentId;
        });
        
        foreach ($filteredCategories as $category) {
            $categoryData = $category->toArray();
            $categoryData['level'] = $level;
            $result[] = $categoryData;
            
            // Process children
            $this->buildCategoryHierarchy($categories, $result, $category->id, $level + 1);
        }
    }
}
