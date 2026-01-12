<?php

namespace App\Traits\Sap;

use App\Models\Sap\DirectPurchase\SapDirectPurchaseModel;
use App\Models\Sap\DirectPurchase\SapDirectPurchaseItemModel;
use Exception, Auth;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Http;

trait SapDirectPurchaseTrait
{
    use ResponseTrait;

    public function createSapDirectPurchase($decodedDirectPurchaseData)
    {
        try {
            // dd($decodedDirectPurchaseData['direct_purchase_items']);
            $storeCodeResponse = Http::withHeaders([
                'x-api-key' => config('apikeys.sds_api_key')
            ])->get(config('apiurls.sds.url') . config('apiurls.sds.public_store_list_get') . '');
            $storeCodeData = $storeCodeResponse->json();
            $sapDirectPurchaseItems = $decodedDirectPurchaseData['direct_purchase_items'];
            $headerRemarks = $decodedDirectPurchaseData['direct_purchase_header']['remarks'];
            $headerAttachment = $decodedDirectPurchaseData['direct_purchase_header']['attachment'];
            $definitionId = 'jp10.com-mgfi-dev.mgiossupplierdirectdeliveryinboundpurchaserequestcreate.purchaseRequisitionProcess';

            if (is_array($headerAttachment) && !empty($headerAttachment)) {
                //Implode for SAP requirement
                $headerAttachment = implode(',', array_column($headerAttachment, 'url'));
            }
            $sapDirectPurchaseArr = [];
            $directPurchaseItemLine = 10;
            // dd($sapPurchaseItems);
            foreach ($sapDirectPurchaseItems as $item) {
                $sapDirectPurchaseArr[] = [
                    'PurchaseRequisitionItem' => (string) $directPurchaseItemLine,
                    'Material' => $item['item_code'],
                    'MaterialGroup' => $item['item_category_code'],
                    'Plant' => '02BP',
                    'CompanyCode' => 'BMII',
                    'PurchasingOrganization' => 'MGPO',
                    'PurchasingGroup' => '001',
                    'BaseUnitISOCode' => $item['uom'],
                    'RequestedQuantity' => (int) $item['quantity'],
                    'PurchaseRequisitionPrice' => 1,
                    'PurReqnItemCurrency' => 'PHP',
                    'DeliveryDate' => "2026-01-08",
                    'StorageLocation' => 'BKRM',
                    'PurchaseRequisitionItemText' => $item['remarks'],
                ];
                $directPurchaseItemLine += 10;
            }
            //* Save all to database after loop
            $this->saveSapDirectPurchase($definitionId, $headerAttachment, $headerRemarks, $sapDirectPurchaseArr);

            //* Call SAP API with prepared payload
            $this->purchaseRequisitionApiCall($definitionId, $headerAttachment, $headerRemarks, $sapDirectPurchaseArr);
        } catch (Exception $exception) {
            throw new Exception("Error creating SAP Purchase Requisition: " . $exception->getMessage());
        }
    }

    public function purchaseRequisitionApiCall($definitionId, $headerAttachment, $headerRemarks, $sapDirectPurchaseArr)
    {
        try {
            $purchaseRequisitionFormatting = $payload ?? [
                'definitionId' => $definitionId,
                'context' => [
                    'purchaseRequisitionDataType' => [
                        'PurchaseRequisitionType' => 'NB',
                        'PurReqnDescription' => $headerRemarks,
                        'PurReqnHeaderNote' => $headerAttachment,
                        '_PurchaseRequisitionItem' => $sapDirectPurchaseArr
                    ]
                ]
            ];

            // CALL SAP API
            $response = Http::timeout(30)
                ->withToken($this->getSapAccessTokenOAuth2())
                ->post(config('sap.endpoints.inbound_purchase_requisition'), $purchaseRequisitionFormatting);

            if ($response->successful()) {
                $json = $response->json();
                return $json;
            }

            // if not successful, throw with response body for easier debugging
            $body = $response->body();
            throw new Exception('SAP API error: ' . $body);
        } catch (Exception $exception) {
            throw new Exception("Error calling purchase requisition api: " . $exception->getMessage());
        }
    }

    private function saveSapDirectPurchase($definitionId, $headerAttachment, $headerRemarks, $sapDirectPurchaseArr)
    {
        $createdBy = Auth::user()->id;
        try {
            // Insert header and get ID
            $header = SapDirectPurchaseModel::create([
                'definition_id' => $definitionId,
                'bpa_response_id' => '',
                'purchase_requisition_type' => 'NB',
                'remarks' => $headerRemarks,
                'attachment' => $headerAttachment,
                'status' => '3',
                'created_by_id' => $createdBy,
            ]);
            $headerId = $header->id;

            // Insert all items
            foreach ($sapDirectPurchaseArr as $item) {
                SapDirectPurchaseItemModel::create([
                    'direct_purchase_id' => $headerId,
                    'purchase_requisition_item' => $item['PurchaseRequisitionItem'],
                    'material' => $item['Material'],
                    'material_group' => $item['MaterialGroup'],
                    'plant' => $item['Plant'],
                    'company_code' => $item['CompanyCode'],
                    'purchasing_organization' => $item['PurchasingOrganization'],
                    'purchasing_group' => $item['PurchasingGroup'],
                    'requested_quantity' => $item['RequestedQuantity'],
                    'purchase_requisition_price' => $item['PurchaseRequisitionPrice'],
                    'purchase_requisition_item_currency' => $item['PurReqnItemCurrency'],
                    'delivery_date' => $item['DeliveryDate'],
                    'storage_location' => $item['StorageLocation'],
                    'purchase_requisition_item_text' => $item['PurchaseRequisitionItemText'],
                    'created_by_id' => $createdBy,
                ]);
            }
        } catch (Exception $exception) {
            throw new Exception("Error saving SAP Purchase Request: " . $exception->getMessage());
        }
    }
    protected function getSapAccessTokenOAuth2()
    {
        try {
            $response = Http::withoutVerifying()->asForm()->post(config('sap.oauth2.access_token_url'), [
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