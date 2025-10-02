<?php

namespace App\Traits\Sap;

use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\Sap\GoodReceipt\GoodReceiptItemModel;
use App\Models\Sap\GoodReceipt\GoodReceiptModel;
use Exception;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Http;

trait SapGoodReceiptTrait
{
    use ResponseTrait;

    public function createSapGoodReceipt($decodedData, $createdById)
    {
        /* [ Sample Json
            'reference_number' => 'FI-000002',
            'posting_date' => '2025-08-19',
            'good_receipt_items' => [
                2  => [ // (Batch Id)
                    'batch_id' => 1,
                    'quantity' => 19,
                ],
                3 => [
                    'batch_id' => 1,
                    'quantity' => 29,
                ]
            ]
        ] */

        try {
            $referenceNumber = $decodedData['reference_number'] ?? null;
            $postingDate = $decodedData['posting_date'] ?? null;
            $goodReceiptItems = $decodedData['good_receipt_items'] ?? [];
            $inventoryStockType = $decodedData['inventory_stock_type'] ?? 'F';
            $definitionId = 'us10.80ff573dtrial.goodsreceiptmgios.materialDocumentIntegrationProcess';
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
                    'created_by_id' => $createdById
                ]);
            } else {
                throw new Exception("SAP Good Receipt with reference number $referenceNumber already exists.");
            }
            $goodReceiptId = $sapGoodReceiptModel->id;

            $toMaterialDocumentItem = [];
            $materialDocumentLine = 1;
            foreach ($goodReceiptItems as $batchId => $item) {
                $batchModel = ProductionBatchModel::find($batchId);
                if ($batchModel) {
                    $itemMasterDataModel = $batchModel->itemMasterData;
                    $plantCode = $itemMasterDataModel->plant->code;
                    $itemCode = $itemMasterDataModel->item_code;

                    $productionDate = $batchModel->productionOrder->production_date ?? '';
                    $productionToBakeAssemble = $batchModel->productionOta ?? $batchModel->productionOtb;
                    $isBatchManagementRequired = $productionToBakeAssemble->gr_is_batch_required;
                    $storageLocation = $productionToBakeAssemble->gr_storage_location ?? '';
                    $manufacturingOrder = $productionToBakeAssemble->gr_manu_order; // To be received from SAP, add soon
                    $quantity = $item['quantity'];
                    $uom = $itemMasterDataModel->uom->code;
                    $ambientExpDate = $batchModel->ambient_exp_date ?? '';
                    $frozenExpDate = $batchModel->frozen_exp_date ?? '';
                    $chilledExpDate = $batchModel->chilled_exp_date ?? '';
                    $batchCode = $isBatchManagementRequired === 1 ? $this->batchFormatter($batchModel->batch_code, $batchModel->batch_type) : '';

                    // Consolidate sticker numbers
                    $stickerNumbers = json_encode($item['sticker_numbers'] ?? []);
                    // Consolidate to material document item
                    $toMaterialDocumentItem[] = [
                        'MaterialDocumentLine' => (string) $materialDocumentLine,
                        'Plant' => $plantCode,
                        'Material' => $itemCode,
                        'StorageLocation' => $storageLocation,
                        'Batch' => $batchCode, // 5-252-001-R
                        'GoodsMovementType' => '101',
                        'ManufacturingOrder' => $manufacturingOrder,
                        'ManufactureDate' => $productionDate,
                        'GoodsMovementRefDocType' => 'F',
                        'QuantityInEntryUnit' => (string) $quantity,
                        'EntryUnit' => $uom,
                        'YY1_Ambient_Exp_Date_MMI' => $ambientExpDate,
                        'YY1_Frozen_Exp_Date_MMI' => $frozenExpDate,
                        'YY1_Chilled_Exp_Date_MMI' => $chilledExpDate,
                        'InventoryUsabilityCode' => $inventoryStockType, // 'F' - Unrestricted, '2' - Quality Inspection
                        'YY1_StickerNumber_MMI' => "$batchModel->id-$stickerNumbers"
                    ];

                    $materialDocumentLine++;
                    // Save to database
                    $this->saveGoodReceiptItem($goodReceiptId, $plantCode, $itemCode, $batchCode, $manufacturingOrder, $quantity, $uom, $ambientExpDate, $frozenExpDate, $chilledExpDate, $createdById, $productionDate);
                }
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

    private function batchFormatter($batchCode, $isReprocessed)
    {
        $alteredBatchCode = explode('-', $batchCode);
        if (count($alteredBatchCode) > 3) {
            unset($alteredBatchCode[3]);
        }
        unset($alteredBatchCode[0]); // removes item code
        $alteredBatchCode = array_values($alteredBatchCode); // reset array keys
        $alteredBatchCode[0] = substr($alteredBatchCode[0], -1);
        return implode('', $alteredBatchCode) . ($isReprocessed === 1 ? 'R' : '');
    }

    private function goodReceiptApiCall($definitionId, $postingDate, $referenceNumber, $sapGoodReceiptModel, $toMaterialDocumentItem)
    {
        try {
            $goodReceiptFormatting = [
                'definitionId' => $definitionId,
                'context' => [
                    'materialDocumentDataType' => [
                        'GoodsMovementCode' => '02',
                        'PostingDate' => $postingDate,
                        'DocumentDate' => '',
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

    private function saveGoodReceiptItem($sapGoodReceiptId, $plantCode, $itemCode, $batchCode, $manufacturingOrder, $quantity, $uom, $ambientExpDate, $frozenExpDate, $chilledExpDate, $createdById, $productionDate)
    {
        try {
            GoodReceiptItemModel::insert([
                'sap_good_receipt_id' => $sapGoodReceiptId,
                'plant' => $plantCode,
                'material' => $itemCode,
                'batch' => $batchCode,
                // sticker number if possible
                'manufacturing_order' => $manufacturingOrder,
                'manufacture_date' => $productionDate,
                'quantity_in_entry_unit' => $quantity,
                'entry_unit' => $uom,
                'yy1_ambient_exp_date_mmi' => $ambientExpDate,
                'yy1_frozen_exp_date_mmi' => $frozenExpDate,
                'yy1_chilled_exp_date_mmi' => $chilledExpDate,
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

