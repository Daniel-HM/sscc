<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Livewire\Actions\Logout;
use Illuminate\Http\Request;
use Session;

class LoginController extends Controller
{

    public function login(Request $request)
    {
        $request->validate();

        $this->form->authenticate();

        Session::regenerate();

        return redirect('dashboard');
    }
}
