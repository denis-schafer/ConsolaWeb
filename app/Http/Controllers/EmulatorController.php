<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class EmulatorController extends Controller
{
    public function index(): View
    {
        return view('emulator');
    }

    public function play(): View
    {
        return view('play');
    }

    public function roms(Request $request): JsonResponse
    {
        $romsDir = public_path('roms');
        $roms = [];

        if (File::isDirectory($romsDir)) {
            $files = File::files($romsDir);
            foreach ($files as $file) {
                $name = $file->getFilename();
                if ($name === '.gitignore') {
                    continue;
                }

                $ext = strtolower($file->getExtension());
                $core = $this->coreForExtension($ext);
                if (!$core) {
                    continue;
                }

                $roms[] = [
                    'id' => base64_encode($name),
                    'name' => $name,
                    'file' => $name,
                    'core' => $core,
                    'size' => $file->getSize(),
                    'url' => asset('roms/' . rawurlencode($name)),
                ];
            }
        }

        return response()->json($roms);
    }

    private function coreForExtension(string $ext): ?string
    {
        return match ($ext) {
            'nes' => 'nestopia',
            'smc', 'sfc', 'fig' => 'snes9x',
            'gb', 'gbc', 'gba' => 'mgba',
            'gen', 'md' => 'picodrive',
            'n64', 'z64', 'v64' => 'n64',
            'nds' => 'desmuME',
            'cue', 'chd', 'pbp' => 'pcsx_rearmed',
            default => null,
        };
    }
}
