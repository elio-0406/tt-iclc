<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\WhisperController;
use App\Models\Exercise;

Route::get('/', [ExerciseController::class, 'index'])->name('exercises.index');

Route::post('/todos', [TodoController::class, 'add'])->name('todos.add');
Route::put('/todos/{todo}', [TodoController::class, 'update'])->name('todos.update');
Route::patch('/todos/{todo}/toggle', [TodoController::class, 'toggle'])->name('todos.toggle');
Route::delete('/todos/{todo}', [TodoController::class, 'delete'])->name('todos.delete');

Route::get('/exercises/{exercise}', [ExerciseController::class, 'detail'])->name('exercises.detail');
Route::post('/exercises/get-result', [ExerciseController::class, 'getResult'])->name('exercises.getResult');
Route::post('/exercises', [ExerciseController::class, 'create'])->name('exercises.create');
Route::post('/exercises/transcribe', [WhisperController::class, 'transcribe'])->name('exercises.transcribe');


