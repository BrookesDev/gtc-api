<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\CategoryAccount;
use App\Models\Classes;
use App\Models\Journal;
use App\Models\ProductCategories;
use App\Models\SubCategory;
use App\Models\SubSubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoriesController extends Controller
{
    public function index()
    {
        $category = Category::whereNull('category_id')->get();
        return respond(true, 'List of categories fetched!', $category, 201);
    }
    public function getAllPendingReciepts()
    {
        $receipts = allTransactions()->where('type', 3)->whereNull('bank_lodged')->orderBy('created_at', 'DESC')->with('mode')->paginate(100);
        return respond(true, 'Receipts fetched successfully', $receipts, 200);
    }

    public function createSubCategory(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'description' => 'required',
                'class_id' => 'required|exists:classes,id',
                'category_id' => 'required|exists:categories,id',

            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $data = $request->all();
            $rep = SubCategory::create($data);

            return respond(true, 'sub categories created successfully', $rep, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function createCategories(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'description' => 'required',

            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $input = $request->all();
            $productCategory = ProductCategories::create($input);
            return respond(true, 'Product category created successfully', $productCategory, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function updateCategories(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:product_categories,id',
                'description' => 'nullable',
                'category_id' => 'nullable|exists:product_categories,category_id',
                'parent_id' => 'nullable|exists:product_categories,id',

            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            //$input = $request->all();
            $productCategory = ProductCategories::find($request->id);
            $productCategory->update([
                'description' => $productCategory->description ?? $request->description,
                'category_id' => $productCategory->category_id ?? $request->category_id,
                'parent_id' => $productCategory->parent_id ?? $request->parent_id,
                'company_id' => $productCategory->company_id,
            ]);
            return respond(true, 'Product category updated successfully', $productCategory, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function deleteCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:product_categories,id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $productCategory = ProductCategories::find($request->id);
        $productCategory->delete();
        return respond(true, 'Product category archived successfully', $productCategory, 200);
    }

    public function getAllCategories(Request $request)
    {
        if ($request->has('description')) {
            $category = ProductCategories::whereNull('category_id')->where('company_id', auth()->user()->company_id)
                ->where('description', $request->description)
                ->with('company')->orderBy('created_at', 'DESC')->get();
        }
        $category = ProductCategories::whereNull('category_id')->where('company_id', auth()->user()->company_id)
            ->with('company')->orderBy('created_at', 'DESC')->get();

        return respond(true, 'Product category fetched successfully', $category, 200);
    }
    public function getSubCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:product_categories,category_id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $category = ProductCategories::where('category_id', $request->category_id)->where('company_id', auth()->user()->company_id)
            ->with('company', 'category')->orderBy('created_at', 'DESC')->get();
        return respond(true, 'Product category fetched successfully', $category, 200);
    }

    public function newCategories(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'description' => 'required',
                'category_id' => 'required|exists:product_categories,id',
                //'parent_id' => 'nullable|exists:product_categories,id',

            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $category_id = $request->category_id;
            $productCategory = ProductCategories::where('id', $category_id)->first();
            if ($productCategory->parent_id == null) {
                $Category = ProductCategories::create([
                    'description' => $request->description,
                    'category_id' => $category_id,
                    'parent_id' => $category_id,
                ]);
            } elseif ($productCategory->category_id != null) {
                $Category = ProductCategories::create([
                    'description' => $request->description,
                    'category_id' => $category_id,
                    'parent_id' => $productCategory->parent_id,
                ]);
            }
            return respond(true, 'Product category created successfully', $Category, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }


    public function getSubCategory(Request $request)
    {
        try {
            $user = auth()->user()->company_id;
            if ($user) {
                $info = SubCategory::where('company_id', $user)->orwhere('company_id', '')->orderBy('created_at', 'ASC')->get();
                // $info = SubCategory::orderBy('created_at', 'ASC')->get();
            }
            return respond(true, 'List of sub categories fetched!', $info, 201);
        } catch (\Exception $e) {
            return respond(false, 'Error!', $request->all(), 400);
        }
    }
    public function getClasess(Request $request)
    {
        try {

            $info = Classes::all();
            return respond(true, 'List of classes fetched!', $info, 201);
        } catch (\Exception $e) {
            return respond(false, 'Error!', $request->all(), 400);
        }
    }
    public function getCategories(Request $request)
    {
        try {

            $info = Category::all();
            return respond(true, 'List of categories fetched!', $info, 201);
        } catch (\Exception $e) {
            return respond(false, 'Error!', $request->all(), 400);
        }
    }

    public function getSubCategoryByCategoryID(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|exists:categories,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $categoryID = $request->category_id;
            $category = SubCategory::where('category_id', $categoryID)
            ->where(function ($query) {
                $query->where('company_id', auth()->user()->company_id)
                ->orwhere('company_id', '');  // Handles empty 'company_id' (null)
            })
            ->orderBy('created_at', 'ASC')
            ->get();
            return respond(true, 'List of Subcategories fetched!', $category, 200);
        } catch (\Exception $e) {
            return respond(false, 'Error!', $request->all(), 400);
        }
    }
    public function getCashAndBank(Request $request)
    {
        try {
            $id = getCompanyid();
            $categoryID = [1,10];
            $category = Account::where('company_id', $id)->wherein('sub_category_id', $categoryID)->select('id','gl_name')->get();
            return respond(true, 'Cash & Bank Accounts Fetched Successfully!', $category, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function getCategoryByClassID(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class_id' => 'required|exists:classes,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $classId = $request->class_id;
            $category = Category::where('class_id', $classId)->get();
            return respond(true, 'List of Categories fetched!', $category, 200);
        } catch (\Exception $e) {
            return respond(false, 'Error!', $request->all(), 400);
        }
    }

    public function getSubCategory1(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:categories,id',
            ]);
            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }
            $pp['data'] = $info = Category::where('category_id', $request->id)->get();
            $resonse = $pp;
            return respond(true, 'List of categories fetched!', $resonse, 201);
        } catch (\Exception $e) {
            return respond(false, 'Error!', $request->all(), 400);
        }
    }

    public function getCategoriesByID(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:classes,id',
            ]);
            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }
            $id = $request->id;
            $info = Category::where("class_id", $id)->get();
            return respond(true, 'Lists of categories!', $info, 200);
        } catch (\Exception $e) {
            return respond(false, 'Error!', $request->all(), 400);
        }
    }

    public function addCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class_id' => 'required|exists:classes,id',
                'description' => 'required',
                // 'category_id' => 'nullable|exists:categories,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $input = $request->all();
            $category = Category::create($input);
            return respond(true, 'category created successfully', $category, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function updateCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:categories,id',
                'class_id' => 'nullable|exists:classes,id',
                'description' => 'nullable',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $input = $request->all();
            $category = Category::find($request->id);
            $category->update($input);
            return respond(true, 'category updated successfully', $category, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function deleteCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:categories,id'
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $input = $request->all();
            $category = Category::find($request->id);
            $category->delete();
            return respond(true, 'category deleted successfully', $category, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function getDeletedCategories()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = Category::onlyTrashed()->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Data fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreCategory(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:categories,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = Category::onlyTrashed()->findOrFail($request->id);
            $invoice->restore();


            return respond(true, 'Data restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function restoreAllCategories(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = Category::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }


            return respond(true, 'Data restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeleteCategory(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:categories,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $account = Category::withTrashed()->findOrFail($id);
            if (!$account) {
                return respond(false, 'category not found', null, 404);
            }

            $account->forceDelete();

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }
    public function forceDeleteAllCategories()
    {

        try {

            $accounts = Category::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }

    public function AddSubCategory(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|exists:categories,id',
                'description' => 'required',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            // $input = $request->all();
            $category = Category::find($request->category_id);
            $categoryID = $category->id;
            $classID = $category->class_id;
            $subcategory = SubCategory::create([
                'category_id' => $categoryID,
                'class_id' => $classID,
                'description' => $request->description
            ]);
            return respond(true, 'sub-category created successfully', $subcategory, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function updateSubcategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sub_categories,id',
                'description' => 'nullable',
                'category_id' => 'nullable|exists:categories,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $input = $request->all();
            $subcategories = SubCategory::find($request->id);
            if ($request->filled("category_id")) {
                $category = Category::find($request->category_id);
                $subcategories->update([
                    'class_id' => $category->class_id,
                    'category_id' => $category->id,
                    'description' => $request->description
                ]);
            } else {
                $subcategories->update($input);
            }
            return respond(true, 'sub-category updated successfully', $subcategories, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function deleteSubcategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sub_categories,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $categories = SubCategory::find($request->id);
            $categories->delete();
            return respond(true, 'sub-category archived successfully', $categories, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function getDeletedSubCategories()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = SubCategory::where('company_id',auth()->user()->company_id)->onlyTrashed()->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Data fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreSubCategory(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sub_categories,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = SubCategory::onlyTrashed()->findOrFail($request->id);
            $invoice->restore();


            return respond(true, 'Data restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function restoreAllSubCategories(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = SubCategory::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }


            return respond(true, 'Data restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeleteSubCategory(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:sub_categories,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $account = SubCategory::withTrashed()->findOrFail($id);
            if (!$account) {
                return respond(false, 'Sub-category not found', null, 404);
            }

            $account->forceDelete();

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }
    public function forceDeleteAllSubCategories()
    {

        try {

            $accounts = SubCategory::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }


    public function getSubSubCategryBySubCategoryID(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'subcategory_id' => 'required|exists:sub_categories,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $id = $request->subcategory_id;
            $category = SubSubCategory::where('sub_category_id', $id)->get();
            return respond(true, 'List of Subcategories fetched!', $category, 200);
        } catch (\Exception $e) {
            return respond(false, 'Error!', $request->all(), 400);
        }
    }
    public function addSubSubCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sub_category_id' => 'required|exists:sub_categories,id',
                'description' => 'required',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            // $input = $request->all();
            $subcategory = SubCategory::find($request->sub_category_id);
            $subsubcategory = SubSubCategory::create([
                'sub_category_id' => $subcategory->id,
                'category_id' => $subcategory->category_id,
                'class_id' => $subcategory->class_id,
                'description' => $request->description,
            ]);
            return respond(true, 'Sub-sub-category created successfully', $subsubcategory, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function updateSubSubCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sub_sub_categories,id',
                'sub_category_id' => 'nullable|exists:sub_categories,id',
                'description' => 'nullable',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $input = $request->all();
            $id = $request->id;
            $category = SubSubCategory::find($id);

            if ($request->filled("sub_category_id")) {
                $subcategory = SubCategory::find($request->sub_category_id);
                $category->update([
                    'class_id' => $subcategory->class_id,
                    'sub_category_id' => $subcategory->id,
                    'category_id' => $subcategory->category_id,
                    'description' => $request->description
                ]);
            } else {
                $category->update($input);
            }
            $category->update($input);
            return respond(true, 'Sub-sub-category updated successfully', $category, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function deleteSubSubCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sub_sub_categories,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $id = $request->id;
            $category = SubSubCategory::find($id);
            $category->delete();
            return respond(true, 'Sub-sub-category archived successfully', $category, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function getDeletedSubSubCategory()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = SubSubCategory::where('company_id',auth()->user()->company_id)->onlyTrashed()->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Data fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreSubSubCategory(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sub_sub_categories,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = SubSubCategory::onlyTrashed()->findOrFail($request->id);
            $invoice->restore();


            return respond(true, 'Data restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function restoreAllSubSubCategories(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = SubSubCategory::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }


            return respond(true, 'Data restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeleteSubSubCategory(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:sub_sub_categories,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $account = SubSubCategory::withTrashed()->findOrFail($id);
            if (!$account) {
                return respond(false, 'Data not found', null, 404);
            }

            $account->forceDelete();

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }
    public function forceDeleteAllSubSubCategories()
    {

        try {

            $accounts = SubSubCategory::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }


    public function getCategoryAccountBySubSubCategoeryID(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'sub_sub_category_id' => 'required|exists:sub_sub_categories,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $id = $request->sub_sub_category_id;
            $category = CategoryAccount::where('sub_sub_category_id', $id)->get();
            return respond(true, 'List of Subcategories fetched!', $category, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function addCategoryAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sub_sub_category_id' => 'required|exists:sub_sub_categories,id',
                'description' => 'required',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $subsubcategory_id = SubSubCategory::find($request->sub_sub_category_id);
            $category = CategoryAccount::create([
                'class_id' => $subsubcategory_id->class_id,
                'category_id' => $subsubcategory_id->category_id,
                'sub_category_id' => $subsubcategory_id->sub_category_id,
                'sub_sub_category_id' => $subsubcategory_id->id,
                'description' => $request->description,
            ]);
            return respond(true, 'category account created successfully!', $category, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function updateCategoryAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:category_accounts,id',
                'sub_sub_category_id' => 'nullable|exists:sub_sub_categories,id',
                'description' => 'nullable',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $input = $request->all();
            $id = $request->id;
            $category = CategoryAccount::find($id);
            if ($request->filled("sub_sub_category_id")) {
                $subsubcategory = SubSubCategory::find($request->sub_sub_category_id);
                $category->update([
                    'class_id' => $subsubcategory->class_id,
                    'sub_sub_category_id' => $subsubcategory->id,
                    'sub_category_id' => $subsubcategory->sub_category_id,
                    'category_id' => $subsubcategory->category_id,
                    'description' => $request->description
                ]);
            } else {
                $category->update($input);
            }
            return respond(true, 'category account updated successfully!', $category, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function deleteCategoryAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:category_accounts,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $id = $request->id;
            $category = CategoryAccount::find($id);
            $category->delete();
            return respond(true, 'category account archived successfully!', $category, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function getDeletedCategoryAccounts()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = CategoryAccount::where('company_id',auth()->user()->company_id)->onlyTrashed()->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Data fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreCategoryAccount(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:category_accounts,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = CategoryAccount::onlyTrashed()->findOrFail($request->id);
            $invoice->restore();


            return respond(true, 'Data restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function restoreAllCategoryAccount(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = CategoryAccount::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }


            return respond(true, 'Data restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeleteCategoryAccount(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:category_accounts,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $account = CategoryAccount::withTrashed()->findOrFail($id);
            if (!$account) {
                return respond(false, 'Account not found', null, 404);
            }

            $account->forceDelete();

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }
    public function forceDeleteAllCategoryAccounts()
    {

        try {

            $accounts = CategoryAccount::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }
    public function doubleEntry(Request $request){
        DB::beginTransaction();
        try {
            set_time_limit(8000000);
            ini_set('max_execution_time', '5000');
            $data = $request->all();
            // Access the 'getDetails' and 'getInvoice' data
            $getDetails = $data['getDetails'];
            $getInvoice = $data['getInvoice'];
            // credit the accounts one by one
            foreach ($getDetails as $details) {
                $newJournal = new Journal();
                $newJournal->gl_code = $details['account_id'];
                $newJournal->debit = 0;
                $newJournal->credit = $details['amount'];
                $newJournal->details = $details['description'];
                $newJournal->uuid = $details['transaction_id'];
                $newJournal->save();
            }
            // debit receivable
            foreach ($getInvoice as $invoice) {
                $newJournal = new Journal();
                $newJournal->gl_code = 100;
                $newJournal->debit =  $invoice['amount'];
                $newJournal->credit = 0;
                $newJournal->details = $invoice['description'];
                $newJournal->uuid = $invoice['transaction_id'];
                $newJournal->save();
                //post as receivable
                insertReceivable($invoice['amount'], $invoice['amount'], 0, now(), $invoice['description'], $invoice['transaction_id'], 1, $invoice['transaction_id'], now(), "School Fees", $invoice['student_class']);
            }

            DB::commit();
            return respond("success", 'Posted to journal successfully!', null, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond("error", $exception->getMessage(), null, 400);
        }
    }


    public function fetchSoftdelete()
    {
        $deleted = ProductCategories::onlyTrashed()->get();
        return respond(true, 'Fetch archieved categories successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $Category = ProductCategories::withTrashed()->find($request->id);

        if ($Category && $Category->trashed()) {
            $Category->restore();
            return respond(true, 'Category restored successfully!', $Category, 200);
        } elseif ($Category) {
            return respond(false, 'Category is not deleted!', null, 400);
        } else {
            return respond(false, 'Category not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedCategorys = ProductCategories::onlyTrashed()->get();
        if ($deletedCategorys->isEmpty()) {
            return respond(false, 'No archived Categorys found to restore!', null, 404);
        }
        Category::onlyTrashed()->restore();

        return respond(true, 'All archived Categorys restored successfully!', $deletedCategorys, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $Category = ProductCategories::withTrashed()->find($request->id);

        if ($Category && $Category->trashed()) {
            $Category->forceDelete();
            return respond(true, 'Category permanently deleted successfully!', null, 200);
        } elseif ($Category) {
            return respond(false, 'Category is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Category not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedCategorys = ProductCategories::onlyTrashed()->get();
        if ($deletedCategorys->isEmpty()) {
            return respond(false, 'No archive Categorys found to permanently delete!', null, 404);
        }
        Category::onlyTrashed()->forceDelete();
        return respond(true, 'All archive Categorys permanently deleted successfully!', null, 200);
    }
}
