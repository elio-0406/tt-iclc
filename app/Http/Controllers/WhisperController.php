<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class WhisperController extends Controller
{
    public function transcribe(Request $request)
{
    $request->validate([
        'audio' => 'required|file|mimetypes:audio/webm,audio/mp4,audio/m4a,audio/mpeg,audio/wav,audio/ogg,video/webm,video/mp4|max:51200',
    ]);

    $ffmpeg  = env('FFMPEG_BIN', 'ffmpeg');
    $cli     = env('WHISPER_CLI');
    $model   = env('WHISPER_MODEL');
    if (!$cli || !$model) {
        return response()->json(['ok'=>false,'error'=>'WHISPER_CLI/WHISPER_MODEL not configured'], 500);
    }

    $srcPath   = $request->file('audio')->getRealPath();
    $srcSize   = filesize($srcPath) ?: 0;
    $srcMime   = $request->file('audio')->getClientMimeType();

    $baseDir = storage_path('app/stt_tmp');
    @is_dir($baseDir) || @mkdir($baseDir, 0777, true);
    $base = $baseDir.'/'.Str::uuid()->toString();
    $wav  = $base.'.wav';

    $p1 = new Process([$ffmpeg, '-hide_banner', '-loglevel', 'error',
        '-y', '-i', $srcPath, '-ar', '16000', '-ac', '1', $wav
    ]);
    $p1->run();
    $ffErr = trim($p1->getErrorOutput());
    $wavSize = is_file($wav) ? filesize($wav) : 0;

    if (!$p1->isSuccessful() || $wavSize === 0) {
        Log::error('FFmpeg failed', ['err'=>$ffErr, 'srcSize'=>$srcSize, 'srcMime'=>$srcMime]);
        return response()->json([
            'ok'=>false,
            'error'=>'ffmpeg failed',
            'diag'=>['srcSize'=>$srcSize,'srcMime'=>$srcMime,'ffmpegErr'=>$ffErr,'wavSize'=>$wavSize]
        ], 500);
    }

   $lang = $request->filled('locale')
    ? substr((string) $request->input('locale'), 0, 2)
    : 'en';
    $threads = max(1, (int)($_SERVER['NUMBER_OF_PROCESSORS'] ?? 4));

    $p2 = new Process([$cli, '-m', $model, '-f', $wav, '-otxt', '-l', $lang, '-t', (string)$threads]);
    $p2->run();
    $wOut = trim($p2->getOutput());
    $wErr = trim($p2->getErrorOutput());

    $txtFile = $wav.'.txt';
    $text = is_file($txtFile) ? trim(file_get_contents($txtFile)) : '';

    @unlink($wav); @unlink($txtFile);

    if ($text === '') {
        Log::warning('Whisper empty transcript', ['wOut'=>$wOut, 'wErr'=>$wErr, 'wavSize'=>$wavSize]);
        return response()->json([
            'ok'=>true,             
            'transcript'=>'',
            'diag'=>[
                'srcSize'=>$srcSize, 'srcMime'=>$srcMime,
                'wavSize'=>$wavSize,
                'whisperOut'=>$wOut, 'whisperErr'=>$wErr
            ]
        ], 200);
    }

    return response()->json([
        'ok'=>true,
        'transcript'=>$text,
        'diag'=>['srcSize'=>$srcSize,'srcMime'=>$srcMime,'wavSize'=>$wavSize]
    ], 200, [], JSON_UNESCAPED_UNICODE);
}
}
