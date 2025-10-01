<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>{{ $exercise->prompt }}</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-xl">
    <h1 class="text-2xl font-bold mb-4">{{ $exercise->prompt }}</h1>

    <div class="flex items-center gap-2 mb-4">
      <button id="startBtn" class="bg-red-600 text-white px-4 py-2 rounded">
        <i class="bi bi-record-circle-fill"></i>
      </button>
      <button id="stopBtn" class="bg-gray-600 text-white px-4 py-2 rounded" disabled
        data-exercise-id="{{ $exercise->id }}">
        <i class="bi bi-stop-fill"></i>
      </button>
      <span id="status" class="text-sm text-gray-500"></span>
    </div>

    <div class="mb-2">
      <p><strong>Kết quả ghi âm:</strong> <span id="transcript" class="text-blue-700"></span></p>
      <p><strong>Điểm:</strong> <span id="score" class="font-bold"></span></p>
      <p><strong>Đánh giá:</strong> <span id="feedback"></span></p>
    </div>
    <audio id="playback" class="hidden" controls></audio>

    <a href="{{ route('exercises.index') }}" 
       class="inline-block mt-4 text-black hover:underline">
       ← Trở về danh sách bài tập
    </a>
  </div>

    <script src="/js/exercises-detail.js"></script>
</body>
</html>
