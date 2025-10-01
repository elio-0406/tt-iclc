<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>My To-Do List</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-blue-100 min-h-screen flex items-center justify-center p-3">

  <div class="w-96 bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="flex items-center justify-center px-4 py-3 ">
      <h2 class="font-semibold text-lg flex items-center gap-2">
        TO DO LIST
      </h2>
    </div>

    <form id="todoForm" class="flex items-center gap-2 p-3 border-b" onsubmit="handleSubmit(event)">
      @csrf
      <input id="titleInput" name="title" placeholder="Add new item..."
        class="flex-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500" required>
      
        <button type="submit"
        class="bg-black text-white px-4 py-2 rounded-lg">Add</button>
    </form>

    <ul id="todoList" class="divide-y">
      @foreach($todos as $todo)
        <li class="flex items-center px-4 py-3">
          <input type="checkbox" onchange="toggle({{ $todo->id }}, this)"
            {{ $todo->is_done ? 'checked' : '' }}
            class="w-5 h-5 text-green-600 rounded-full border-gray-300 focus:ring-green-500">
          <span class="ml-3 flex-1 {{ $todo->is_done ? 'line-through text-gray-400' : '' }}">
            {{ $todo->title }}
          </span>

          <button type="button"
            onclick="editTodo({{ $todo->id }}, this)"
            class="bg-blue-500 hover:bg-blue-600 ml-2 px-3 py-1 rounded-lg">Edit</button>

          <button onclick="removeTodo({{ $todo->id }}, this)"
            class="bg-red-500 hover:bg-red-600 ml-2 px-3 py-1 rounded-lg">Delete</button>
        </li>
      @endforeach
    </ul>
  </div>

  <script src="/js/todo.js"></script>
</body>
</html>
