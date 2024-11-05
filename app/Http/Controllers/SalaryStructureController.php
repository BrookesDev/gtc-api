<?php

namespace App\Http\Controllers;

use App\Models\SalaryStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SalaryStructureController extends Controller
{
    // Fetch all records
    public function index()
    {
        try {
            $totalAmount = SalaryStructure::where('company_id', auth()->user()->company_id)->get();

            return response()->json([
                'success' => true,
                'message' => 'Salary structure fetched successfully',
                'data' => $totalAmount
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching salary structure: ' . $e->getMessage(),
            ], 500);
        }
    }

  // Add a new record
public function store(Request $request)
{
    try {
        // Validate the incoming request
        $validatedData = $request->validate([
            'level_id' => 'required|numeric|exists:levels,id',
            'grade_id' => 'required|numeric|exists:grades,id',
            'amount' => 'required|numeric'
        ]);

        // Check if salary structure with the same level and grade already exists
        $existing = SalaryStructure::where('level_id', $request->level_id)
            ->where('grade_id', $request->grade_id)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A salary structure with the same level and grade already exists',
            ], 422);
        }

        // Create a new salary structure
        $salaryStructure = SalaryStructure::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Salary structure created successfully',
            'data' => $salaryStructure
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors() // Returns the validation errors
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error creating salary structure: ' . $e->getMessage(),
        ], 500);
    }
}

    // Update a specific record
    public function update(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required|exists:salary_structures,id',
                'level_id' => 'required|numeric|exists:levels,id',
                'grade_id' => 'required|numeric|exists:grades,id',
                'amount' => 'required|numeric'
            ]);

            $salaryStructure = SalaryStructure::findOrFail($request->id);

            $salaryStructure->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Salary structure updated successfully',
                'data' => $salaryStructure
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating salary structure: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAmount(Request $request)
{
    try {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'level_id' => 'required|numeric|exists:levels,id',
            'grade_id' => 'required|numeric|exists:grades,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Fetch the specific salary structure for the given level and grade
        $salaryStructure = SalaryStructure::where('level_id', $request->level_id)
            ->where('grade_id', $request->grade_id)
            ->first();

        // Check if salary structure was found
        if (!$salaryStructure) {
            return response()->json([
                'success' => false,
                'message' => 'No salary structure found for the given level and grade',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Amount fetched successfully',
            'amount' => $salaryStructure->amount
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching amount: ' . $e->getMessage(),
        ], 500);
    }
}

    // Archive (soft-delete) a salary structure record
    public function deleteSalaryStructure(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:salary_structures,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->first(),
                ], 422);
            }

            $salary = SalaryStructure::find($request->id);

            if (!$salary) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salary structure not found',
                ], 404);
            }

            $salary->delete();

            return response()->json([
                'success' => true,
                'message' => 'Salary structure archived successfully!',
                'data' => $salary,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error archiving salary structure: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Permanently delete a salary structure (force delete)
    public function forceDeleteSalaryStructure(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:salary_structures,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->first(),
                ], 422);
            }

            $salary = SalaryStructure::withTrashed()->find($request->id);

            if (!$salary) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salary structure not found',
                ], 404);
            }

            if (!$salary->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot force delete a non-archived record',
                ], 400);
            }

            $salary->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Salary structure permanently deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error force deleting salary structure: ' . $e->getMessage(),
            ], 500);
        }
    }
}
