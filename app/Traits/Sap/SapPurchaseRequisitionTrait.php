<?php

namespace App\Traits\Sap;
use App\Models\Sap\PurchaseRequest\PurchaseRequestModel;
use App\Models\Sap\PurchaseRequest\SapPurchaseRequestModel;
use App\Models\Sap\PurchaseRequest\SapPurchaseRequestItemModel;
use Exception, Auth;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Http;

trait SapPurchaseRequisitionTrait
{
    use ResponseTrait;


    public function createSapPurchaseRequest($decodedPurchaseReqData)
    {
        try {

            // dd($decodedPurchaseReqData['purchase_request_header']);
            // exit;
            $storeCodeResponse = Http::withHeaders([
                'x-api-key' => config('apikeys.sds_api_key')
            ])->get(config('apiurls.sds.url') . config('apiurls.sds.public_store_list_get') . '');
            $storeCodeData = $storeCodeResponse->json();
            $sapPurchaseItems = $decodedPurchaseReqData['purchase_request_items'];
            $headerRemarks = $decodedPurchaseReqData['purchase_request_header']['remarks'];
            $headerAttachment = $decodedPurchaseReqData['purchase_request_header']['attachment'];
            $definitionId = 'jp10.com-mgfi-dev.mgiossupplierdirectdeliveryinboundpurchaserequestcreate.purchaseRequisitionProcess';

            $sapPurReqArr = [];
            $purchaseItemLine = 10;

            foreach ($sapPurchaseItems as $item) {
                $sapPurReqArr[] = [
                    'PurchaseRequisitionItem' => (string) $purchaseItemLine,
                    'Material' => $item['item_code'],
                    'MaterialGroup' => $item['item_category_code'],
                    'Plant' => $item['store_code'],
                    'CompanyCode' => $item['store_company_code'],
                    'PurchasingOrganization' => $item['purchasing_organization'],
                    'PurchasingGroup' => $item['purchasing_group'],
                    'BaseUnitISOCode' => 'PCE',
                    'RequestedQuantity' => (int) $item['requested_quantity'],
                    'PurchaseRequisitionPrice' => (int) $item['price'],
                    'PurReqnItemCurrency' => $item['currency'],
                    'DeliveryDate' => $item['delivery_date'],
                    'StorageLocation' => $item['storage_location'],
                    'PurchaseRequisitionItemText' => $item['remarks'],
                ];
                $purchaseItemLine += 10;
            }
            //* Save all to database after loop
            $this->saveSapPurchaseRequest($definitionId, $headerAttachment, $headerRemarks, $sapPurReqArr);

            //* Call SAP API with prepared payload
            $this->purchaseRequisitionApiCall($definitionId, $headerAttachment, $headerRemarks, $sapPurReqArr);
        } catch (Exception $exception) {
            throw new Exception("Error creating SAP Purchase Requisition: " . $exception->getMessage());
        }
    }

    public function purchaseRequisitionApiCall($definitionId, $headerAttachment, $headerRemarks, $sapPurReqArr)
    {
        try {
            $purchaseRequisitionFormatting = $payload ?? [
                'definitionId' => $definitionId,
                'context' => [
                    'purchaseRequisitionDataType' => [
                        'PurchaseRequisitionType' => 'NB',
                        'PurReqnDescription' => $headerRemarks,
                        'PurReqnHeaderNote' => $headerAttachment,
                        '_PurchaseRequisitionItem' => $sapPurReqArr
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

    private function saveSapPurchaseRequest($definitionId, $headerAttachment, $headerRemarks, $sapPurReqArr)
    {
        $createdBy = Auth::user()->id;
        try {
            // Insert header and get ID
            $header = SapPurchaseRequestModel::create([
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
            foreach ($sapPurReqArr as $item) {
                SapPurchaseRequestItemModel::create([
                    'purchase_request_id' => $headerId,
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
            throw new Exception("Error saving SAP Good Receipt: " . $exception->getMessage());
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
