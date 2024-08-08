<?php

namespace Database\Seeders;

use App\Models\Admin\Asset\AssetListModel;
use Illuminate\Database\Seeder;

class AssetListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $assetLists = [
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/supply_planner/w1kCZreXESHXIi0wQIWu0C8TnaYCLsvljr2SHgWu.mp4",
                "keyword" => "PPIC - HOW TO IMPORT ORDERS",
                "file_path" => "public/assets/mos/video_tutorials/supply_planner",
                "original_file_name" => "PPIC - HOW TO IMPORT ORDERS.mp4",
                "altered_file_name" => "w1kCZreXESHXIi0wQIWu0C8TnaYCLsvljr2SHgWu.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/supply_planner/ffNIykvnlnIJSYmnKkDiaHvJdVunss6534YQ6dyJ.mp4",
                "keyword" => "PPIC - HOW TO BACKTRACK THE ORDERS",
                "file_path" => "public/assets/mos/video_tutorials/supply_planner",
                "original_file_name" => "PPIC - HOW TO BACKTRACK THE ORDERS.mp4",
                "altered_file_name" => "ffNIykvnlnIJSYmnKkDiaHvJdVunss6534YQ6dyJ.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/supply_planner/mREbqMv5DZCdatmeueKIqZ4gfIDx1gCSx9vMYcSy.mp4",
                "keyword" => "PPIC - HOW TO FILTER, SORT & SEARCH",
                "file_path" => "public/assets/mos/video_tutorials/supply_planner",
                "original_file_name" => "PPIC - HOW TO FILTER, SORT & SEARCH.mp4",
                "altered_file_name" => "mREbqMv5DZCdatmeueKIqZ4gfIDx1gCSx9vMYcSy.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/supply_planner/vi0eOWHadV5aBghahdw2XuafStZ9ZQgxlT6VvPWA.mp4",
                "keyword" => "PPIC - REOPEN AND TAG ORDER AS COMPLETED",
                "file_path" => "public/assets/mos/video_tutorials/supply_planner",
                "original_file_name" => "PPIC - REOPEN AND TAG ORDER AS COMPLETED.mp4",
                "altered_file_name" => "vi0eOWHadV5aBghahdw2XuafStZ9ZQgxlT6VvPWA.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/supply_planner/4S8DUwQEWmTPuaxBuBM5lJqIq1GxGeHaxnrT06nw.mp4",
                "keyword" => "PPIC - HOW TO MONITOR THE PRODUCTION ORDER",
                "file_path" => "public/assets/mos/video_tutorials/supply_planner",
                "original_file_name" => "PPIC - HOW TO MONITOR THE PRODUCTION ORDER.mp4",
                "altered_file_name" => "4S8DUwQEWmTPuaxBuBM5lJqIq1GxGeHaxnrT06nw.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/team_leader/l9504xk54PmFuRUp18WsquM9WJhAgyPsjAscImyL.mp4",
                "keyword" => "TL -HOW TO CREATE AND PRINT BATCH STICKERS",
                "file_path" => "public/assets/mos/video_tutorials/team_leader",
                "original_file_name" => "TL -HOW TO CREATE AND PRINT BATCH STICKERS.mp4",
                "altered_file_name" => "l9504xk54PmFuRUp18WsquM9WJhAgyPsjAscImyL.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/team_leader/qgeT5Awq6ZJQr6XFKEeyc697abKXzp5gaH8PicEY.mp4",
                "keyword" => "TL - HOW TO REPRINT BATCH STICKERS",
                "file_path" => "public/assets/mos/video_tutorials/team_leader",
                "original_file_name" => "TL - HOW TO REPRINT BATCH STICKERS.mp4",
                "altered_file_name" => "qgeT5Awq6ZJQr6XFKEeyc697abKXzp5gaH8PicEY.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/team_leader/k2aIkV9IH0crEdEAnPoiMq3N5qdPbnLIb7At8hHq.mp4",
                "keyword" => "TL - HOW TO PROCESS ITEMS ENDORSED BY QA",
                "file_path" => "public/assets/mos/video_tutorials/team_leader",
                "original_file_name" => "TL - HOW TO PROCESS ITEMS ENDORSED BY QA.mp4",
                "altered_file_name" => "k2aIkV9IH0crEdEAnPoiMq3N5qdPbnLIb7At8hHq.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/team_leader/cbqXuj8nLWL5C82r9N67x7xWqvTORQlwaptx4jnl.mp4",
                "keyword" => "TL - HOW TO DEACTIVATE STICKER",
                "file_path" => "public/assets/mos/video_tutorials/team_leader",
                "original_file_name" => "TL - HOW TO DEACTIVATE STICKER.mp4",
                "altered_file_name" => "cbqXuj8nLWL5C82r9N67x7xWqvTORQlwaptx4jnl.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/team_leader/bCa1YQU9jJt6JDMFJgFzNjRaTMHodCZ22k6ynJZA.mp4",
                "keyword" => "TL - HOW TO ADD ADDITIONAL STICKER QUANTITY",
                "file_path" => "public/assets/mos/video_tutorials/team_leader",
                "original_file_name" => "TL - HOW TO ADD ADDITIONAL STICKER QUANTITY.mp4",
                "altered_file_name" => "bCa1YQU9jJt6JDMFJgFzNjRaTMHodCZ22k6ynJZA.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/line_staff/CbLCiNoo4roTvEfgKliVZuUA6KD191TanLN6YJKy.mp4",
                "keyword" => "LINE STAFF - HOW TO DECLARE SUBSTANDARD ITEMS",
                "file_path" => "public/assets/mos/video_tutorials/line_staff",
                "original_file_name" => "LINE STAFF - HOW TO DECLARE SUBSTANDARD ITEMS.mp4",
                "altered_file_name" => "CbLCiNoo4roTvEfgKliVZuUA6KD191TanLN6YJKy.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/line_staff/JNSoMusRFkxN1Qm8Z8pVWPFKr10FmJ3sP5q7rtPH.mp4",
                "keyword" => "LINE STAFF - HOW TO TRANSFER SCANNED ITEMS TO WAREHOUSE",
                "file_path" => "public/assets/mos/video_tutorials/line_staff",
                "original_file_name" => "LINE STAFF - HOW TO TRANSFER SCANNED ITEMS TO WAREHOUSE.mp4",
                "altered_file_name" => "JNSoMusRFkxN1Qm8Z8pVWPFKr10FmJ3sP5q7rtPH.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/line_staff/idkFdywA71Q5gRrFEuTdfnNCvDlJyWk0Zh5GE0oO.mp4",
                "keyword" => "LINE STAFF - HOW TO TRANSFER THE PAHABOL ITEMS",
                "file_path" => "public/assets/mos/video_tutorials/line_staff",
                "original_file_name" => "LINE STAFF - HOW TO TRANSFER THE PAHABOL ITEMS.mp4",
                "altered_file_name" => "idkFdywA71Q5gRrFEuTdfnNCvDlJyWk0Zh5GE0oO.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/quality_assurance/CnI3I7zU2n6oAczZ3aLH3TzUNp2ohXeK9monBkD1.mp4",
                "keyword" => "QA - HOW TO TAG FOR DISPOSITION OF ITEMS",
                "file_path" => "public/assets/mos/video_tutorials/quality_assurance",
                "original_file_name" => "QA - HOW TO TAG FOR DISPOSITION OF ITEMS.mp4",
                "altered_file_name" => "CnI3I7zU2n6oAczZ3aLH3TzUNp2ohXeK9monBkD1.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/quality_assurance/EcKCkspRf7gLV8XahADR3ExjSlOlEUsogbnKNwDM.mp4",
                "keyword" => "QA - HOW TO TAG ITEMS AS FOR INVESTIGATION",
                "file_path" => "public/assets/mos/video_tutorials/quality_assurance",
                "original_file_name" => "QA - HOW TO TAG ITEMS AS FOR INVESTIGATION.mp4",
                "altered_file_name" => "EcKCkspRf7gLV8XahADR3ExjSlOlEUsogbnKNwDM.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/quality_assurance/YyZIMBb7TBev6lDP27bevsyfvvSB769snDto4Rlx.mp4",
                "keyword" => "QA - HOW TO TAG ITEMS AS FOR SAMPLING",
                "file_path" => "public/assets/mos/video_tutorials/quality_assurance",
                "original_file_name" => "QA - HOW TO TAG ITEMS AS FOR SAMPLING.mp4",
                "altered_file_name" => "YyZIMBb7TBev6lDP27bevsyfvvSB769snDto4Rlx.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/quality_assurance/zxust9GoTvoQeZIayaTFFi7r6pU9PqunV26mgwo3.mp4",
                "keyword" => "QA - TAGGING OF ITEMS ON HOLD AND FOR RELEASE",
                "file_path" => "public/assets/mos/video_tutorials/quality_assurance",
                "original_file_name" => "QA - TAGGING OF ITEMS ON HOLD AND FOR RELEASE.mp4",
                "altered_file_name" => "zxust9GoTvoQeZIayaTFFi7r6pU9PqunV26mgwo3.mp4"
            ],
            [
                "file" => "https://mos-api-test.onemarygrace.com/storage/assets/mos/video_tutorials/warehouse/f08OpPPmCpGidPJcx9AENFWtcIbLdQ2YtmIE2snL.mp4",
                "keyword" => "WAREHOUSE - HOW TO RECEIVE THE ITEMS",
                "file_path" => "public/assets/mos/video_tutorials/warehouse",
                "original_file_name" => "WAREHOUSE - HOW TO RECEIVE THE ITEMS.mp4",
                "altered_file_name" => "f08OpPPmCpGidPJcx9AENFWtcIbLdQ2YtmIE2snL.mp4"
            ]
        ];



        foreach ($assetLists as $value) {
            AssetListModel::create([
                'created_by_id' => '0000',
                'file' => $value['file'],
                'keyword' => $value['keyword'],
                'file_path' => $value['file_path'],
                'original_file_name' => $value['original_file_name'],
                'altered_file_name' => $value['altered_file_name'],
            ]);
        }
    }
}
