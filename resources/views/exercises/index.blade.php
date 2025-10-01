<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>AI Speaking Test</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-blue-100 min-h-screen flex items-center justify-center p-3">

  <div class="w-96 bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="flex items-center justify-center px-4 py-3 ">
      <h2 class="font-semibold text-lg flex items-center gap-2">
        AI Speaking Test
      </h2>
    </div>

    <ul id="exercisesList" class="divide-y">
      @foreach($exercises as $exercise)
        <a href="{{ route('exercises.detail', $exercise->id) }}" 
         class="flex items-center px-4 py-3 cursor-pointer hover:bg-gray-100 block">
        <span class="ml-3 flex-1">
          {{ $exercise->prompt }}
        </span>
        </a>
      @endforeach
    </ul>
  </div>

  <script src="/js/exercises.js"></script>
</body>
</html>
