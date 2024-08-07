<?php

namespace App\Http\Controllers\v1\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Models\Admin\Asset\AssetListModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;

class AssetListController extends Controller
{
    use ResponseTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'folder_name' => 'required',
            'file' => 'required',
            'keyword' => 'required|string|unique:admin_asset_lists,keyword',
        ]);
        try {
            $createdById = $fields['created_by_id'];
            $folderName = $fields['folder_name'];
            $filePath = 'public/' . $folderName;

            $file = $request->file('file')->store($filePath);
            $originalFileName = $request->file('file')->getClientOriginalName();
            $alteredFileName = basename($file);
            $fileLink = env('APP_URL') . '/storage/' . substr($file, 7);
            $keyword = $fields['keyword'];

            $assetListModel = new AssetListModel();
            $assetListModel->file = $fileLink;
            $assetListModel->keyword = $keyword;
            $assetListModel->file_path = $filePath;
            $assetListModel->original_file_name = $originalFileName;
            $assetListModel->altered_file_name = $alteredFileName;
            $assetListModel->created_by_id = $createdById;
            $assetListModel->save();

            return $this->dataResponse('success', 201, 'Asset ' . __('msg.create_success'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Asset ' . $exception->getMessage());
        }
    }

    public function onGetAssetListByKeyword(Request $request)
    {
        $fields = $request->validate([
            'keyword' => 'required|string'
        ]);

        try {
            $assetListModel = AssetListModel::whereIn('keyword', json_decode($fields['keyword'], true))->get();
            $transformedData = $assetListModel->keyBy('keyword');

            if ($transformedData->isEmpty()) {
                return $this->dataResponse('error', 404, 'Asset ' . __('msg.record_not_found'));
            }
            return $this->dataResponse('success', 201, 'Asset ' . __('msg.record_found'), $transformedData);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Asset ' . $exception->getMessage());
        }
    }

    public function onGetAssetBykeyword($keyword)
    {
        try {
            $assetListModel = AssetListModel::where('keyword', $keyword)->first();
            if ($assetListModel) {
                return $this->dataResponse('success', 201, 'Asset ' . __('msg.record_found'), $assetListModel);
            }
            return $this->dataResponse('error', 404, 'Asset ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Asset ' . $exception->getMessage());
        }
    }
}
