<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ProductImage;
use App\Models\Stock;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Nette\Utils\Random;

class ItemController extends Controller
{
    public function index()
    {
        $id = getCompanyid();
        //$items = Item::where('company_id', $id)->get();
        $items = Item::with('measurement', 'account', 'stock', 'product_categories', 'sales', 'purchase_gl', 'account_receivables', 'account_payables', 'advance_payments', 'images', 'cost_of_good_gl', 'discount_gl')
            ->where('is_inventory', 0)->where('company_id', $id)->get();
        return respond(true, 'List of items fetched!', $items, 201);
    }
    public function stocks()
    {
        $id = getCompanyid();
        //$items = Item::where('company_id', $id)->get();
        $items = Item::where('company_id', $id)->select('id', 'name', 'cost_price', 'price')->get();
        return respond(true, 'List of products fetched!', $items, 201);
    }
    public function fetchProducts()
    {
        $id = getCompanyid();
        //$items = Item::where('company_id', $id)->get();
        $items = Item::where('type', 2)->where('company_id', $id)->select('id', 'name', 'purchase_gl', 'cost_price')->get();
        return respond(true, 'List of products fetched!', $items, 201);
    }
    public function fetchServices()
    {
        $id = getCompanyid();
        //$items = Item::where('company_id', $id)->get();
        $items = Item::where('type', 1)->where('company_id', $id)->select('id', 'name')->get();
        return respond(true, 'List of services fetched!', $items, 201);
    }
    public function getStock()
    {
        $id = getCompanyid();
        //$items = Item::where('company_id', $id)->get();
        $items = Item::with('measurement', 'account', 'stock', 'product_categories', 'sales', 'purchase_gl', 'account_receivables', 'account_payables', 'advance_payments', 'images', 'cost_of_good_gl', 'discount_gl')
            ->where('is_inventory', 1)->where('company_id', $id)->get();
        return respond(true, 'List of inventories fetched!', $items, 201);
    }
    public function getItem(Request $request)
    {
        $id = $request->id;

        $validator = validator(['id' => $id], [
            'id' => 'required|numeric|exists:items,id'
        ]);

        if ($validator->fails()) {
            return respond(false, 'Validation error!', $validator->errors(), 400);
        }
        $item = Item::with('measurement', 'account', 'stock', 'product_categories', 'sales', 'purchase_gl', 'account_receivables', 'account_payables', 'advance_payments', 'images')->find($id);

        return respond(true, 'Item fetched successfully!', $item, 200);
    }

    public function unit()
    {
        $id = getCompanyid();
        $units = Unit::where('company_id', $id)->get();
        return respond(true, 'List of units fetched!', $units, 201);
    }
    public function getunit(Request $request)
    {
        $id = $request->id;

        $validator = validator(['id' => $id], [
            'id' => 'required|numeric|exists:units,id'
        ]);

        if ($validator->fails()) {
            return respond(false, 'Validation error!', $validator->errors(), 400);
        }
        $unit = Unit::find($id);

        return respond(true, 'Item fetched successfully!', $unit, 200);
    }

    public function itemCount()
    {
        $itemCount = item::where('company_id', auth()->user()->company_id)->count();

        $response = [
            'total_number_of_products' => $itemCount
        ];

        return respond(true, 'Data fetched successfully', $response, 200);
    }
    public function addNewItem(Request $request)
    {
        try {
            DB::beginTransaction();
            //type 1 is for service and type 2 is for product
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'type' => 'required',
                'is_inventory' => 'nullable',
                // 'display_image' => 'required',
                'description' => 'required',
                // 'price' => 'required|numeric',
                'gl_code' => 'nullable|numeric',
                'payable_gl' => 'nullable|numeric',
                'image' => 'nullable',
                'sales_gl' => 'nullable|exists:accounts,id',
                'purchase_gl' => 'nullable|exists:accounts,id',
                'account_receivable' => 'nullable|exists:accounts,id',
                'advance_payment_gl' => 'nullable|exists:accounts,id',
                'cost_of_good_gl' => 'nullable|exists:accounts,id',
                'discount_gl' => 'nullable|exists:accounts,id',
                'category_id' => 'nullable|exists:product_categories,id',
            ])->sometimes(['unit', 'quantity', 're_order_level', 'cost_price', 'price'], 'required|numeric', function ($input) {
                return $input->type == 2;
            });

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            $input = $request->except(['image', 'display_image']);
            if ($request->id != "") {
                $item = Item::find($request->id);
                return respond(true, 'New item saved successfully!', $item, 201);
            }
            $input['sku'] = Str::random(7);
            $file = "product";
            if ($request->has('display_image')) {
                if ($request->display_image->getSize() > 2 * 1024 * 1024) {
                    return response()->json(['error' => 'The display image size must not exceed 2MB.'], 400);
                }

                $mainImagePath = uploadImage($request->display_image, $file);
                $input['image'] = $mainImagePath;
            }


            $created_item = Item::create($input);

            if ($request->hasFile("image")) {

                $allImages = $request->file('image');

                foreach ($allImages as $pImage) {

                    // Upload each image separately and assign the path to $uploadedImage
                    $uploadedImage = uploadImage($pImage, $file);
                    $saveNew = new ProductImage;
                    $saveNew->product_id = $created_item->id;
                    $saveNew->image = $uploadedImage; // Assign the correct image
                    $saveNew->save();
                }
            }
            $item = Item::with('images')->find($created_item->id);
            if (isset($input['quantity'])) {
                $check = new Stock;
                $check->item_id = $item->id;
                $check->quantity = $input['quantity'];
                $check->save();
                $detail = "created $item->name ";
                stockInventory($item->id, 0, $input['quantity'], $input['quantity'], $check->id, $request->price, $detail);
            }
            DB::commit();
            return respond(true, 'New item saved successfully!', $item, 201);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', 'error creating item please try again', 400);
        }
    }
    public function createStockItem(Request $request)
    {
        try {
            DB::beginTransaction();
            //type 1 is for service and type 2 is for product
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'quantity' => 'required',
                'description' => 'required',
                'price' => 'nullable|numeric',
                're_order_level' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $input = $request->all();
            $input['sku'] = Str::random(7);
            $input['type'] = "2";
            $input['is_inventory'] = "1";
            $item = Item::create($input);
            $check = new Stock;
            $check->item_id = $item->id;
            $check->quantity = $input['quantity'];
            $check->save();
            $detail = "created $item->name ";
            stockInventory($item->id, 0, $input['quantity'], $input['quantity'], $check->id, $request->price, $detail);
            DB::commit();
            return respond(true, 'New Stock Item saved successfully!', $item, 201);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }

    public function updateStockItem(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:items,id',
                'name' => 'nullable|string',
                'quantity' => 'nullable|numeric',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $item = Item::find($request->id);
            if (!$item) {
                return respond(false, 'Error! Item not found', null, 404);
            }

            // Store the old quantity before updating the item
            $oldQuantity = $item->quantity;

            // Update item fields if provided
            $item->name = $request->name ?? $item->name;
            $item->description = $request->description ?? $item->description;
            $item->quantity = $request->quantity ?? $item->quantity;

            // Save the updated item
            $item->save();

            $newQuantity = $item->quantity; // Use updated quantity

            // Handle stock changes
            $stock = Stock::where('item_id', $item->id)->first();
            if ($oldQuantity != $newQuantity) {
                if ($stock) {
                    // Stock exists, update quantity
                    if ($newQuantity > $oldQuantity) {
                        $difference = $newQuantity - $oldQuantity;
                        $detail = auth()->user()->name . " increased stock by $difference";
                    } else {
                        $difference = $oldQuantity - $newQuantity;
                        $detail = auth()->user()->name . " reduced stock by $difference";
                    }
                    stockInventory($item->id, $oldQuantity, $newQuantity, $difference, $stock->id, $item->price, $detail);
                    $stock->update(['quantity' => $newQuantity]);
                } else {
                    // If stock doesn't exist, create a new stock entry
                    $stock = new Stock();
                    $stock->item_id = $item->id;
                    $stock->quantity = $newQuantity;
                    $stock->save();

                    $detail = auth()->user()->id . " set initial stock of $newQuantity";
                    stockInventory($item->id, 0, $newQuantity, $newQuantity, $stock->id, $item->price, $detail);
                }
            }

            DB::commit();
            return respond(true, 'Stock item updated successfully!', $item, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }


    function uploadImage($file, $path)
    {
        $image_name = $file->getClientOriginalName();
        $image_name_withoutextensions = pathinfo($image_name, PATHINFO_FILENAME);
        $name = str_replace(" ", "", $image_name_withoutextensions);
        $image_extension = $file->getClientOriginalExtension();
        $file_name_extension = trim($name . '.' . $image_extension);
        $uploadedFile = $file->move(public_path($path), $file_name_extension);
        // dd($uploadedFile);
        return $path . '/' . $file_name_extension;
    }
    public function create(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);
            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }
            $input = $request->all();
            if ($request->id != "") {
                $unit = Unit::find($request->id);
                return respond(true, 'New unit saved successfully!', $unit, 201);
            }
            ;
            $unit = Unit::create($input);
            DB::commit();
            return respond(true, 'New unit saved successfully!', $unit, 201);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }
    //
    public function deleteItem(Request $request)
    {
        try {

            $validator = validator($request->all(), [
                'id' => 'required|numeric|exists:items,id',
            ]);

            if ($validator->fails()) {
                return respond(false, 'Validation error!', $validator->errors(), 400);
            }

            DB::beginTransaction();
            $item = Item::find($request->id);

            if (!$item) {
                return respond(false, 'Error! Item not found', null, 404);
            }

            $item->delete();
            DB::commit();
            return respond(true, 'Item archived successfully!', null, 204);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }

    public function getDeletedItems()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = Item::where('company_id', auth()->user()->company_id)
                ->onlyTrashed()->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Items fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreDeletedItem(Request $request)
    {

        $department = Item::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archived item restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Item is not yet archived!', null, 400);
        } else {
            return respond(false, 'Item not found in archive!', null, 404);
        }

    }
    public function restoreAllDeletedItems(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = Item::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }


            return respond(true, 'Item restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeleteItem(Request $request)
    {
        $department = Item::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archived Item permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Item is not yet archived!', null, 400);
        } else {
            return respond(false, 'Item not found in archive!', null, 404);
        }
    }
    public function forceDeleteAllItems()
    {

        try {

            $accounts = Item::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Item permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function deleteUnit(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'id' => 'required|numeric|exists:units,id',
            ]);

            if ($validator->fails()) {
                return respond(false, 'Validation error!', $validator->errors(), 400);
            }

            DB::beginTransaction();

            $unit = Unit::find($request->id);

            if (!$unit) {

                return respond(false, 'Error! Unit not found', null, 404);
            }

            $unit->delete();
            DB::commit();
            return respond(true, 'Unit archived successfully!', null, 200);
        } catch (\Exception $exception) {
            // Rollback if an exception occurs
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }

    public function getDeletedUnits()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = Unit::where('company_id', auth()->user()->company_id)->onlyTrashed()->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Units fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreDeletedUnit(Request $request)
    {

        $department = Unit::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archived unit restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Unit is not yet archived!', null, 400);
        } else {
            return respond(false, 'Unit not found in archive!', null, 404);
        }
    }
    public function restoreAllDeletedUnits(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = Unit::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }


            return respond(true, 'Units restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeleteUnits(Request $request)
    {
        $department = Unit::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archived unit orders permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Unit is not yet archived!', null, 400);
        } else {
            return respond(false, 'Unit not found in archive!', null, 404);
        }
    }
    public function forceDeleteAllUnits()
    {

        try {

            $accounts = Unit::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Unit permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }

    public function updateUnit(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'id' => 'required|numeric',
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }

            $unit = Unit::find($request->id);

            if (!$unit) {
                return respond(false, 'Error! Unit not found', null, 404);
            }

            $unit->update($request->all());

            DB::commit();
            return respond(true, 'Unit updated successfully!', $unit, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }



    public function updateItem(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'id' => 'required|numeric',
                'name' => 'required',
                'description' => 'required',
                'type' => 'required', // Ensure 'type' is validated
                'gl_code' => 'nullable|numeric',
                'sales_gl' => 'nullable|exists:accounts,id',
                'purchase_gl' => 'nullable|exists:accounts,id',
                'account_receivable' => 'nullable|exists:accounts,id',
                'advance_payment_gl' => 'nullable|exists:accounts,id',
                'cost_of_good_gl' => 'nullable|exists:accounts,id',
                'discount_gl' => 'nullable|exists:accounts,id',
                'category_id' => 'nullable|exists:product_categories,id',
            ])->sometimes(['quantity', 'unit', 're_order_level', 'category_id', 'cost_price'], 'required|numeric', function ($input) {
                return $input->type == 2; // Adjust the condition as needed
            });

            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }

            $item = Item::find($request->id);

            if (!$item) {
                return respond(false, 'Error! Item not found', null, 404);
            }

            $old = $item->quantity;
            $incoming = $request->quantity ?? $old; // Use existing quantity if not provided

            // Handle stock
            $stock = Stock::where('item_id', $request->id)->first();
            if ($item->quantity != $incoming) {
                if ($stock) {
                    if ($item->quantity < $incoming) {
                        $new = $incoming - $old;
                        $detail = auth()->user()->name . " increased stock by $new";
                    } else {
                        $new = $old - $incoming;
                        $detail = auth()->user()->name . " reduced stock by $new";
                    }
                    stockInventory($item->id, $old, $incoming, $new, $stock->id, $item->price, $detail);
                    $stock->update(["quantity" => $incoming]);
                } else {
                    // Handle case where stock does not exist
                    $stock = new Stock;
                    $stock->item_id = $item->id;
                    $stock->quantity = $incoming;
                    $stock->save();
                    $detail = auth()->user()->name . " set initial stock of $incoming";
                    stockInventory($item->id, 0, $incoming, $incoming, $stock->id, $item->price, $detail);
                }
            }

            // Update the item
            $data = $request->except(['image', 'display_image']);
            $file = "product";
            if ($request->has('display_image')) {
                if ($item->image) {
                    Storage::disk('public')->delete($item->image); // Delete the main image file
                }
                if ($request->display_image->getSize() > 2 * 1024 * 1024) {
                    return response()->json(['error' => 'The image size must not exceed 2MB.'], 400);
                }
                $mainImagePath = uploadImage($request->display_image, $file);
                $data['image'] = $mainImagePath;
            }
            $item->update($data); // Exclude 'type' if not needed for updating
            if ($request->hasFile("image")) {

                $allImages = $request->file('image');

                foreach ($item->images as $existingImage) {
                    Storage::disk('public')->delete($existingImage->image); // Delete each image file
                    $existingImage->delete(); // Delete the record from the ProductImage table
                }

                // Handle the remaining images as additional images
                foreach ($allImages as $pImage) {

                    $uploadedImage = uploadImage($pImage, $file);
                    $saveNew = new ProductImage;
                    $saveNew->product_id = $item->id;
                    $saveNew->image = $uploadedImage;
                    $saveNew->save();
                }
            }
            $item = Item::with('images')->find($item->id);

            DB::commit();
            return respond(true, 'Item updated successfully!', $item, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }
}
