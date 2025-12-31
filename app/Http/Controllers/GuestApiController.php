<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GuestApiController extends Controller
{
    //
    public function test()
    {
        return response(array('success' => 1, 'faillure' => 0, 'response' => 'test'), 200);
    }
}
