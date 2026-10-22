<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return match ($request->user()->role) {
            UserRole::Owner, UserRole::Admin => redirect()->route('admin.dashboard'),
            UserRole::Teacher => redirect()->route('teacher.dashboard'),
            UserRole::Student => redirect()->route('student.dashboard'),
        };
    }
}
