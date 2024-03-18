<?php

namespace App\Livewire\Auth;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class Login extends Component
{
    public $showToast = false;
    public $employeeId, $password;
    protected function rules()
    {
        return [
            'employeeId' => 'required|integer',
            'password' => 'required',
        ];
    }
    public function render()
    {
        return view('livewire.auth.login')->layout('components.layouts.guest');
    }
    public function onSave()
    {
        $this->validate();
        try {
            $response = Http::post(env('API_URL') . 'login', [
                'employee_id' => $this->employeeId,
                'password' => $this->password,
            ]);
            $data = $response->json();
            if (!isset($data['auth'])) {
                return $this->dispatch('show-toast', ['message' => $data['message'], 'type' => 'error']);
            }
            $this->dispatch('show-toast', ['message' => $data['message'], 'type' => 'success']);
            // Session::put('key', $data['value']);
            return redirect()->route('endpoint');
        } catch (RequestException $e) {
            $statusCode = $e->getCode(); // HTTP status code
            $errorData = $e->response->json(); // Get the JSON response from the error
            $this->dispatch('show-toast', ['message' => $errorData, 'type' => 'error']);
        }
    }
}