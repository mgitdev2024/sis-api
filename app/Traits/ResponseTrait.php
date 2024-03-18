<?php
namespace App\Traits;

trait ResponseTrait
{
    public static function dataResponse($status, $httpCode, $statusMessage, $data = null)
    {
        $data_arr = ['message' => $statusMessage];
        if ($data !== null) {
            $key = $status === 'success' ? 'data' : 'error_thrown';
            $data_arr[$key] = $data;
        }
        $responseKey = $status === 'success' ? 'success' : 'error';
        $response = [$responseKey => $data_arr];
        return response($response, $httpCode);
    }
}
?>