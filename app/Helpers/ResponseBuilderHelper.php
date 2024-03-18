<?php
namespace App\Helpers;

class ResponseBuilderHelper
{
    public static function dataResponse($status, $httpCode, $statusMessage, $data)
    {
        if ($status == 'success') {
            $data_arr = [
                'message' => $statusMessage,
            ];

            if ($data != null) {
                $data_arr = ['data' => $data] + $data_arr;
            }
            $response = [
                'success' => $data_arr
            ];
        } else {
            $data_arr = [
                'message' => $statusMessage,
            ];

            if ($data != null) {
                $data_arr = ['error_thrown' => $data] + $data_arr;
            }
            $response = [
                'error' => $data_arr
            ];
        }
        return response($response, $httpCode);
    }

}
