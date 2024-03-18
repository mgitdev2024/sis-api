<?php

namespace App\Livewire;

use Livewire\Component;

class Endpoint extends Component
{
    public $endpointList = [
        [
            'endpoint_parent' =>'Auth',
            'endpoints' => [
                [
                    'endpoint' => 'http://localhost/api/login',
                    'endpoint_name' =>'Login',
                    'endpoint_description' => 'Login API',
                    'endpoint_action' => 'POST',
                    'endpoint_body' => '{
                        "id": "5358",
                        "password": "password"
                    }',
                ],[
                    'endpoint' => 'http://localhost/api/create_account',
                    'endpoint_name' =>'Create Account',
                    'endpoint_description' => 'Create Account API',
                    'endpoint_action' => 'POST',
                    'endpoint_body' => '',
                ]
            ]
        ],
        [
            'endpoint_parent' =>'Personal Information',
            'endpoints' => [
                [
                    'endpoint' => '',
                    'endpoint_name' =>'Personal Information',
                    'endpoint_description' => '',
                    'endpoint_action' => 'POST',
                    'endpoint_body' => '{
                        "id": "5358",
                        "password": "password"
                    }',
                ],[
                    'endpoint' => '',
                    'endpoint_name' =>'Create Accunt',
                    'endpoint_description' => '',
                    'endpoint_action' => 'POST',
                    'endpoint_body' => '',
                ]
            ]
        ],
        
    ];
    public $selectedTitle;
    public $selectedEndpoint;
    public $endpointURL = "http://localhost";
    public function render()
    {
        return view('livewire.endpoint');
    }
    public function onGetEndpoint($title,$data){
        $this->selectedTitle = $title;
        $this->selectedEndpoint = $data;
        $this->endpointURL = $data['endpoint'];
        // dd($this->endpointURL);
    }
}
