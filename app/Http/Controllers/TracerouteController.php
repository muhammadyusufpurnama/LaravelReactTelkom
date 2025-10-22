<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TracerouteController extends Controller
{
    /**
     * Menjalankan perintah traceroute dari server.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function run(Request $request)
    {
        // 1. Validasi input dari frontend
        $validator = Validator::make($request->all(), [
            'target' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $target = $request->input('target');

        // 2. Tentukan perintah berdasarkan sistem operasi server (Windows vs Linux/macOS)
        $command = DIRECTORY_SEPARATOR === '\\' ? 'tracert' : 'traceroute';

        // 3. Gunakan Symfony Process untuk menjalankan perintah dengan aman
        // escapeshellarg() SANGAT PENTING untuk mencegah command injection
        $process = Process::fromShellCommandline($command.' '.escapeshellarg($target));
        $process->setTimeout(120); // Set timeout 2 menit

        try {
            $process->mustRun();
            $output = $process->getOutput();

            return response()->json(['output' => $output]);
        } catch (ProcessFailedException $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}
