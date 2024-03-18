<?php
namespace App\Traits;

use Illuminate\Support\Facades\Mail;

use App\Mail\CreatePassword;
use App\Mail\LockAccount;
use App\Mail\ResetPassword;

trait MailTrait
{
    public function onSendSignedUrl($email, $type, $name, $url)
    {
        switch ($type) {
            case 'create':
                $mailClass = CreatePassword::class;
                break;
            case 'reset':
                $mailClass = ResetPassword::class;
                break;
            case 'lock':
                $mailClass = LockAccount::class;
                break;
        }
        if ($mailClass) {
            Mail::to($email)->send(
                new $mailClass(
                    $name,
                    $url
                )
            );
        }
    }

}
?>