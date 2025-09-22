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
            'consolidated_data' => 'required'
        ]);
        try {
            $consolidationCache = StoreConsolidationCacheModel::create([
                'created_by_name' => $fields['created_by_name'],
                'created_by_id' => $fields['created_by_id'],
                'consolidated_data' => $fields['consolidated_data'],
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
