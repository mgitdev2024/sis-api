<?php

namespace App\Traits\Sap;
use Exception;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Http;

trait SapGoodsIssueTrait
{
    use ResponseTrait;

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

    protected function getOutboundGoodsIssue()
    {
        try {
            $response = Http::withBasicAuth(config('sap.basic_auth.username'), config('sap.basic_auth.password'))
                ->withHeader('Accept', 'application/json')
                ->get(config('sap.endpoints.outbound_goods_issue'));

            if ($response->successful()) {
                return $response->json()['d']['results'];
            }

            return null;
        } catch (Exception $exception) {
            throw new Exception("Error getting data: " . $exception->getMessage());
        }
    }
}

