<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class TinyMceUploadController extends Controller
{
    public function upload(Request $request)
    {
        $file = $request->file('file');

        if (!$file) {
            return response()->json(['error' => 'Nenhum arquivo enviado.'], 400);
        }

        // Sanitiza o nome original para SEO
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $slug = Str::slug($originalName);

        $directory = 'kb-images';
        $fileName = $slug . '.' . $extension;

        // Evita sobrescrever arquivos existentes adicionando um contador
        $i = 1;
        while (Storage::disk('public')->exists($directory . '/' . $fileName)) {
            $fileName = $slug . '-' . $i . '.' . $extension;
            $i++;
        }

        // Salva o arquivo
        $path = $file->storeAs($directory, $fileName, 'public');

        return response()->json([
            'location' => asset('storage/' . $path)
        ]);
    }
}
