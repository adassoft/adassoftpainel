<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use Illuminate\Http\Request;

class LegalPageController extends Controller
{
    public function show($slug)
    {
        // Branding para o header/footer
        $branding = [
            'nome_sistema' => 'AdasSoft',
            'logo_path' => asset('favicon.svg'),
        ];

        $page = LegalPage::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('legal_pages.show', compact('page', 'branding'));
    }
}
