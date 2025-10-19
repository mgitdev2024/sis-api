<?php

namespace App\Http\Controllers\v1\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreConsolidationCacheModel;
use Exception;
use Illuminate\Http\Request;

class StoreConsolidationCacheController extends Controller
{
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'created_by_name' => 'required',
            'created_by_id' => 'required',
            'consolidated_data' => 'required',
            'consolidated_order_id' => 'required',
        ]);
        try {
            if (StoreConsolidationCacheModel::where('consolidated_order_id', $fields['consolidated_order_id'])->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consolidation cache with the same order ID already exists',
                ], 409);
            }
            $consolidationCache = StoreConsolidationCacheModel::create([
                'created_by_name' => $fields['created_by_name'],
                'created_by_id' => $fields['created_by_id'],
                'consolidated_data' => $fields['consolidated_data'],
                'consolidated_order_id' => $fields['consolidated_order_id'],
                'status' => 0,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Consolidation cache created successfully',
                'data' => $consolidationCache
            ], 201);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create consolidation cache',
                'error' => $exception->getMessage()
            ], 500);
        }
    }
}
