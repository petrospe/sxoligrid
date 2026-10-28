<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->managesSchool()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isTeacher()) {
            return redirect()->route('teacher.dashboard');
        }

        return redirect()->route('student.dashboard');
    }
}
