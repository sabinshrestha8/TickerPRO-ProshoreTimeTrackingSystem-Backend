<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Client;
use App\Http\Requests\ClientRequest;

class ClientService
{
    public static function addProject(array $validatedAddClient): bool
    {
        $log = Client::create($validatedAddClient);

        if (!is_object($log)) return false;
        return true;
    }
}
