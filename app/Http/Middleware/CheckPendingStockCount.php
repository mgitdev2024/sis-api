<?php

namespace App\Http\Middleware;

use App\Models\Stock\StockInventoryCountModel;
use App\Traits\ResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class CheckPendingStockCount
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    use ResponseTrait;
    public function handle(Request $request, Closure $next): Response
    {
        $requestMethods = ['POST', 'DELETE', 'PUT'];
        $method = $request->getMethod();

        if (in_array($method, $requestMethods)) {
            $cache = Cache::get('store_' . auth()->id());
            $storeCode = $cache['store_code'] ?? null;
            $subUnit = $cache['sub_unit'] ?? null;

            if (!$storeCode) {
                \Log::info('Pending Stock Check', [
                    'user_id' => auth()->id(),
                    'store_code' => $storeCode,
                    'sub_unit' => $subUnit,
                    'has_pending_stock' => null
                ]);
                return $this->dataResponse('error', 400, 'Store not found in cache.');
            }

            $hasPendingStock = StockInventoryCountModel::where([
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $subUnit
            ])
                ->whereIn('status', [0, 1])
                ->exists();

            if ($hasPendingStock) {
                \Log::info('Pending Stock Check', [
                    'user_id' => auth()->id(),
                    'store_code' => $storeCode,
                    'sub_unit' => $subUnit,
                    'has_pending_stock' => $hasPendingStock
                ]);
                return $this->dataResponse('error', 400, 'Action blocked: A pending stock count is still open and must be completed first.');
            }
        }
        return $next($request);
    }
}
