<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThemeController extends Controller
{
    /**
     * Persist the selected theme in session.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', Rule::in([
                'corporate-blue',
                'emerald-governance',
                'slate-professional',
                'indigo-tech',
                'neutral-minimal',
            ])],
        ]);

        $request->session()->put('theme', $validated['theme']);

        return back()->with('success', 'Theme preference updated.');
    }
}
