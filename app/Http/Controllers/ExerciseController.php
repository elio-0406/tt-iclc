<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exercise;
use Illuminate\Support\Facades\Http;
use function PHPUnit\Framework\isEmpty;

class ExerciseController extends Controller
{
    public function index()
    {
        $exercises = Exercise::latest()->get();
        return view('exercises.index', compact('exercises'));
    }

    public function detail($id)
    {
        $exercise = Exercise::findOrFail($id);
        return view('exercises.detail', compact('exercise'));
    }

    public function getResult(Request $request)
    {
        $data = $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
            'transcript'  => 'nullable|string', 
        ]);

        $exercise = Exercise::findOrFail($data['exercise_id']);

        // Làm việc đi con GPT
        $systemPrompt = "Bạn là một gia sư tiếng anh đang luyện nói cho người học. "
            ."So sánh văn bản do trẻ nói với văn bản tham khảo, và chấm điểm, đưa ra nhận xét. "
            ."Trả về CHỈ JSON hợp lệ theo mẫu chính xác sau: {\"score\": \"0-100 integer\", \"feedback\": \"short comment\"}. "
            ."Không có code fence, không có văn bản thừa, chỉ có y hệt vậy, feedback viết bằng tiếng Việt.";

        $userPrompt = json_encode([
            'reference_text' => $exercise->answer,
            'recognized_text'=> ($data['transcript'] === '') ? "Người dùng không nói gì hoặc không ghi âm được." : trim($data['transcript']),
            'locale' => $exercise->locale,
        ], JSON_UNESCAPED_UNICODE);
        $resp = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            'https://generativelanguage.googleapis.com/v1beta/models/'.
            env('GEMINI_MODEL','gemini-2.5-flash').':generateContent?key='.env('GEMINI_API_KEY'),
            [
                'contents' => [[
                    'role'  => 'user',
                    'parts' => [
                        ['text' => $systemPrompt],
                        ['text' => $userPrompt],
                    ],
                ]],
                'generationConfig' => ['temperature' => 0.2],
            ]
        );

        if (!$resp->ok()) {
            return response()->json(['ok'=>false,'error'=>'error: '.$resp->body()], 500);
        }

        $payload = $resp->json();

        $text = data_get($payload, 'candidates.0.content.parts.0.text', '');

        $result = json_decode(trim($text), true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($result)) {
            $result = ['score' => 0, 'feedback' => ''];
        }

        return response()->json([
            'ok'         => true,
            'transcript' => $data['transcript'],
            'score'      => (int)($result['score'] ?? 0),
            'feedback'   => (string)($result['feedback'] ?? ''),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
