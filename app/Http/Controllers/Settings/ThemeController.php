<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark,system',
        ]);

        $request->user()->update([
            'theme_preference' => $validated['theme'],
        ]);

        return back()->with('success', 'Theme updated successfully.');
    }
}
