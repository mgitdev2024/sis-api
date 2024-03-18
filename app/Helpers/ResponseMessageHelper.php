<?php
namespace App\Helpers;

class ResponseMessageHelper
{
    public static function onCreate($text, $status)
    {
        switch ($status) {
            case 1:
                return $text . ' successfully added.';
            case 0:
                return 'Error encountered while adding ' . $text . '.';
            default:
                break;
        }
    }

    public static function onUpdate($text, $status)
    {
        switch ($status) {
            case 1:
                return $text . ' successfully updated.';
            case 0:
                return 'Error encountered while updating ' . $text . '.';
            default:
                break;
        }
    }

    public static function onDelete($text, $status)
    {
        switch ($status) {
            case 1:
                return $text . ' successfully deleted.';
            case 0:
                return 'Error encountered while deleting ' . $text . '.';
            default:
                break;
        }
    }

    public static function onGet($text, $status)
    {
        switch ($status) {
            case 1:
                return $text . ' successfully retrieved.';
            case 0:
                return 'Error encountered while retrieving ' . $text . '.';
            default:
                break;
        }
    }

    public static function onChangeStatus($text, $status)
    {
        switch ($status) {
            case 1:
                return $text . ' status successfully updated.';
            case 0:
                return $text . ' status has encountered an issue.';
            default:
                break;
        }
    }

    public static function onExist($text, $status)
    {
        switch ($status) {
            case 2:
                return $text . ' already taken.';
            case 1:
                return $text . ' already exist.';
            case 0:
                return $text . ' status has encountered an issue.';
            default:
                return $text . ' not found.';
        }
    }

    public static function onPaginate($text, $status)
    {
        switch ($status) {
            case 2:
                return $text . ' paginate was successful.';
            case 1:
                return 'No ' . $text . ' found to paginate.';
            case 0:
                return $text . ' pagination has encountered an issue.';
            default:
                break;
        }
    }

    public static function onAuthenticate($text, $status)
    {
        switch ($status) {
            case 2:
                return $text . ' locked.';
            case 1:
                return $text . ' successful.';
            case 0:
                return $text . ' attempt was unsuccessful.';
            default:
                break;
        }
    }

    public static function onCheckToken($text, $status)
    {
        switch ($status) {
            case 1:
                return $text . ' successfully validated.';
            case 0:
                return $text . ' validation attempt was unsuccessful.';
            default:
                break;
        }
    }

}
