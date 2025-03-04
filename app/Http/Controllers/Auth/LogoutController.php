<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Livewire\Actions\Logout;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout)
    {
        $logout();

        return redirect('/');
    }
}
