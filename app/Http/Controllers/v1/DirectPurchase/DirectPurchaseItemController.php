<?php

namespace App\Http\Controllers\v1\DirectPurchase;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class DirectPurchaseItemController extends Controller
{
    use ResponseTrait;
    public function onCreate(Request $request)
    {
        try {
            $directPurchaseItemsData = $request->input('direct_purchase_items', []);
            $createdItems = [];

            foreach ($directPurchaseItemsData as $itemData) {
                $createdItem = \App\Models\DirectPurchase\DirectPurchaseItemModel::create($itemData);
                $createdItems[] = $createdItem;
            }

            return $this->dataResponse('success', 200, 'Direct Purchase Items Created Successfully.', $createdItems);
        } catch (\Exception $exception) {
            return $this->dataResponse('error', 400, 'An error occurred: ' . $exception->getMessage());
        }
    }
}
