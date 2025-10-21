<?php

namespace App\Traits\Sap;

use App\Models\Sap\GoodReceipt\GoodReceiptItemModel;
use App\Models\Sap\GoodReceipt\GoodReceiptModel;
use App\Models\Store\StoreReceivingGoodsIssueItemModel;
use Exception;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Http;

trait SapGoodReceiptTrait
{
    use ResponseTrait;

    public function createSapGoodReceipt($decodedData, $createdById)
    {
        /* [ Sample Json
            'reference_number' => $storeInventoryModel->reference_number,
            'delivery_date' => $storeInventoryModel->delivery_date,
            'warehouse_code' => $storeInventoryModel->warehouse_code,
            'plant' => $storeReceivingGoodsIssueModel->plant_code,
            'goods_receipt_items' => [
                'item_code' => [
                    'item_code' => $item->item_code,
                    'quantity' => $item->received_quantity
                ],
                'item_code' => [
                    'item_code' => $item->item_code,
                    'quantity' => $item->received_quantity
                ],
            ]
        ] */

        try {
            $referenceNumber = $decodedData['reference_number'] ?? null;
            $postingDate = $decodedData['posting_date'] ?? null;
            $goodsReceiptItems = $decodedData['goods_receipt_items'] ?? [];
            $plant = $decodedData['plant'] ?? null;
            $storeCodeResponse = Http::withHeaders([
                'x-api-key' => config('apikeys.sds_api_key')
            ])->get(config('apiurls.sds.url') . config('apiurls.sds.public_store_get_by_code') . $plant);
            $storeCodeData = $storeCodeResponse->json();
            $warehouseCode = $storeCodeData['success']['data']['company_code'] ?? null;
            $definitionId = 'jp10.com-mgfi-dev.mgiosstorereplenishmentinboundgoodsreceiptpostgr.materialDocumentProcess';
            $exists = GoodReceiptModel::where([
                'reference_document' => $referenceNumber,
                'upload_status' => 1
            ])->exists();
            $sapGoodReceiptModel = null;
            if (!$exists) {
                $sapGoodReceiptModel = GoodReceiptModel::create([
                    'definition_id' => $definitionId,
                    'posting_date' => $postingDate,
                    'reference_document' => $referenceNumber,
                    'GoodsMovementCode' => '01',
                    'created_by_id' => $createdById
                ]);
            } else {
                throw new Exception("SAP Good Receipt with reference number $referenceNumber already exists.");
            }
            $goodReceiptId = $sapGoodReceiptModel->id;

            $toMaterialDocumentItem = [];
            $materialDocumentLine = 1;

            $storeItemIds = array_keys($goodsReceiptItems);
            $issuedItems = StoreReceivingGoodsIssueItemModel::whereIn('sr_inventory_item_id', $storeItemIds)->get()->keyBy('sr_inventory_item_id');
            foreach ($goodsReceiptItems as $id => $item) {
                $storeReceivingGoodsIssueItemModel = $issuedItems->get($id);
                $batch = $storeReceivingGoodsIssueItemModel?->gi_batch ?? null;
                $purchaseOrder = $storeReceivingGoodsIssueItemModel?->gi_purchase_order;
                $purchaseOrderItem = $storeReceivingGoodsIssueItemModel?->gi_purchase_order_item;
                $manufactureDate = $storeReceivingGoodsIssueItemModel?->gi_manu_date;
                $entryUnit = $storeReceivingGoodsIssueItemModel?->gi_entry_unit;

                $itemCode = $item['item_code'];
                $quantity = $item['quantity'];
                // Consolidate to material document item
                $toMaterialDocumentItem[] = [
                    // 'MaterialDocumentLine' => (string) $materialDocumentLine,
                    'Plant' => $plant,
                    'Material' => $itemCode,
                    'StorageLocation' => $warehouseCode,
                    'Batch' => $batch,
                    "PurchaseOrder" => $purchaseOrder,
                    "PurchaseOrderItem" => $purchaseOrderItem,
                    'GoodsMovementType' => '101',
                    'ManufactureDate' => $manufactureDate,
                    'GoodsMovementRefDocType' => 'B',
                    'QuantityInEntryUnit' => (string) $quantity,
                    'EntryUnit' => $entryUnit,
                ];

                $materialDocumentLine++;
                // Save to database
                $this->saveGoodReceiptItem($goodReceiptId, $plant, $itemCode, $warehouseCode, $batch, '101', $purchaseOrder, $purchaseOrderItem, 'B', (string) $quantity, $entryUnit, $manufactureDate, $createdById);

            }
            // Call SAP API after all items are prepared
            $this->goodReceiptApiCall($definitionId, $postingDate, $referenceNumber, $sapGoodReceiptModel, $toMaterialDocumentItem);
        } catch (Exception $exception) {
            if ($sapGoodReceiptModel) {
                $sapGoodReceiptModel->error_message = $exception->getMessage();
                $sapGoodReceiptModel->save();
            }
        }
    }

    private function goodReceiptApiCall($definitionId, $postingDate, $referenceNumber, $sapGoodReceiptModel, $toMaterialDocumentItem)
    {
        try {
            $goodReceiptFormatting = [
                'definitionId' => $definitionId,
                'context' => [
                    'materialDocumentDataType' => [
                        'GoodsMovementCode' => '01',
                        'PostingDate' => $postingDate,
                        'DocumentDate' => $postingDate,
                        'MaterialDocumentHeaderText' => '',
                        'ReferenceDocument' => '',
                        'to_MaterialDocumentItem' => $toMaterialDocumentItem
                    ]
                ]
            ];

            // CALL SAP API
            $response = Http::timeout(30)
                ->withToken($this->getSapAccessTokenOAuth2())
                ->post(config('sap.endpoints.inbound_good_receipt'), $goodReceiptFormatting);

            if ($response->successful()) {
                $json = $response->json();
                $sapGoodReceiptModel->bpa_response_id = $json['id'] ?? null;
                $sapGoodReceiptModel->upload_status = 1; // Mark as uploaded
                $sapGoodReceiptModel->save();
            }
        } catch (Exception $exception) {
            throw new Exception("Error creating SAP Good Receipt: " . $exception->getMessage());
        }
    }

    private function saveGoodReceiptItem($sapGoodReceiptId, $plantCode, $itemCode, $storageLocation, $batchCode, $goodsMovementType, $purchaseOrder, $purchaseOrderItem, $goodsMovementRefDocType, $quantityInEntryUnit, $entryUnit, $manufactureDate, $createdById)
    {
        try {
            GoodReceiptItemModel::insert([
                'sap_good_receipt_id' => $sapGoodReceiptId,
                'plant' => $plantCode,
                'material' => $itemCode,
                'storage_location' => $storageLocation,
                'batch' => $batchCode,
                'goods_movement_type' => $goodsMovementType,
                'purchase_order' => $purchaseOrder,
                'purchase_order_item' => $purchaseOrderItem,
                'goods_movement_ref_doc_type' => $goodsMovementRefDocType,
                'quantity_in_entry_unit' => $quantityInEntryUnit,
                'entry_unit' => $entryUnit,
                'manufacture_date' => $manufactureDate,
                'created_by_id' => $createdById
            ]);
        } catch (Exception $exception) {
            throw new Exception("Error saving SAP Good Receipt: " . $exception->getMessage());
        }
    }

    protected function getSapAccessTokenOAuth2()
    {
        try {
            $response = Http::asForm()->post(config('sap.oauth2.access_token_url'), [
                'grant_type' => 'client_credentials',
                'client_id' => config('sap.oauth2.client_id'),
                'client_secret' => config('sap.oauth2.client_secret'),
            ]);
            return $response->json()['access_token'] ?? null;
        } catch (Exception $exception) {
            throw new Exception("Error getting SAP access token: " . $exception->getMessage());
        }
    }
}

