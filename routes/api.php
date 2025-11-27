<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMemberController;
use App\Http\Controllers\DashboardController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Rutas de usuarios
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    Route::get('/organizations', [OrganizationController::class, 'index']);
    Route::get('/organizations/{organizationId}', [OrganizationController::class, 'show']);

    Route::get('/conversations', [ChatController::class, 'index']);
    Route::post('/conversations/dm', [ChatController::class, 'startConversation']);
    Route::post('/conversations/group', [ChatController::class, 'startGroupConversation']);
    Route::get('/conversations/{id}/messages', [ChatController::class, 'messages']);
    Route::post('/conversations/{id}/messages', [ChatController::class, 'sendMessage']);

    Route::get('/my-tasks', [TaskController::class, 'myTasks']); 
    Route::get('/tasks/individual', [TaskController::class, 'individualTasks']); 
    Route::post('/tasks/individual', [TaskController::class, 'storeIndividual']); 
    Route::get('/tasks/individual/{taskId}', [TaskController::class, 'showIndividual']); 
    Route::post('/tasks/individual/{taskId}/grade', [TaskController::class, 'gradeIndividual']);
    Route::delete('/tasks/individual/{taskId}', [TaskController::class, 'destroyIndividual']);

    Route::post('/groups/join', [GroupController::class, 'joinByCode']);
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::get('/groups/{id}', [GroupController::class, 'show']);
    Route::put('/groups/{id}', [GroupController::class, 'update']);
    Route::delete('/groups/{id}', [GroupController::class, 'destroy']);

    Route::get('/groups/{id}/members', [GroupMemberController::class, 'index']);
    Route::post('/groups/{id}/members', [GroupMemberController::class, 'store']); 
    Route::delete('/groups/{id}/members/{profileId}', [GroupMemberController::class, 'destroy']); 
    Route::get('/groups/{id}/available-users', [GroupMemberController::class, 'available']); 

    Route::get('/groups/{id}/tasks', [TaskController::class, 'index']);
    Route::post('/groups/{id}/tasks', [TaskController::class, 'store']);
    Route::get('/groups/{id}/tasks/{taskId}', [TaskController::class, 'show']);
    Route::post('/groups/{id}/tasks/{taskId}/grade', [TaskController::class, 'gradeStudent']);
    Route::delete('/groups/{id}/tasks/{taskId}', [TaskController::class, 'destroy']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
});
