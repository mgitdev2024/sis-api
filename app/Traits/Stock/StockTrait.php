<?php

namespace App\Traits\Stock;
use Exception;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;

trait StockTrait
{
    use ResponseTrait;

    public function onCreateStockLogs()
    {
        try {
            // **TODO:
            // Update Stock Logs
            // update stock Inventory
            // update stock received items to update location on each items
        } catch (Exception $exception) {

        }
    }
}

