<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Todo;

class TodoController extends Controller
{
    public function index()
    {
        $todos = Todo::orderBy('is_done')->orderByDesc('updated_at')->get();
        return view('todos.index', compact('todos'));
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $todo = Todo::create($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($todo, 201);
        }
        return redirect()->route('todos.index');
    }

    public function update(Request $request, Todo $todo)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $todo->update($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($todo);
        }
        return redirect()->route('todos.index');
    }

    public function toggle(Request $request, Todo $todo)
    {
        $todo->update(['is_done' => !$todo->is_done]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($todo);
        }
        return redirect()->route('todos.index');
    }

    public function delete(Request $request, Todo $todo)
    {
        $todo->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('todos.index');
    }
}
