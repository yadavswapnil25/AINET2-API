<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\Client\FormController;
use App\Http\Controllers\Client\LoginController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Client\AdminController;

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API working fines!',
    ]);
});

Route::post('/membership-signup', [MembershipController::class, 'signup']);
Route::get('eventValidationHandle', [PaymentController::class, 'eventValidationHandle']);
Route::post('/ainet2025ppf', [FormController::class, 'storePpfs']);
Route::post('/ainet2020drf',[FormController::class, 'storeDrfs']);
Route::post('/check-user', [FormController::class, 'checkUserExists']);

// Admin authentication routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AdminController::class, 'logout']);
        Route::get('/profile', [AdminController::class, 'profile']);
        Route::post('/refresh-token', [AdminController::class, 'refreshToken']);
        
        // DRF CRUD operations
        Route::get('/drf', [AdminController::class, 'getDrfList']);
        Route::get('/drf/export', [AdminController::class, 'exportDrf']);
        Route::get('/drf/stats', [AdminController::class, 'getDrfStats']);
        Route::get('/drf/{id}', [AdminController::class, 'getDrf']);
        Route::put('/drf/{id}', [AdminController::class, 'updateDrf']);
        Route::delete('/drf/{id}', [AdminController::class, 'deleteDrf']);
        Route::delete('/drf/bulk', [AdminController::class, 'bulkDeleteDrf']);
        
        // PPF CRUD operations
        Route::get('/ppf', [AdminController::class, 'getPpfList']);
        Route::get('/ppf/export', [AdminController::class, 'exportPpf']);
        Route::get('/ppf/stats', [AdminController::class, 'getPpfStats']);
        Route::get('/ppf/{id}', [AdminController::class, 'getPpf']);
        Route::put('/ppf/{id}', [AdminController::class, 'updatePpf']);
        Route::delete('/ppf/{id}', [AdminController::class, 'deletePpf']);
        Route::delete('/ppf/bulk', [AdminController::class, 'bulkDeletePpf']);
        
        // User CRUD operations
        Route::get('/users', [AdminController::class, 'getUserList']);
        Route::get('/users/stats', [AdminController::class, 'getUserStats']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::get('/users/{id}', [AdminController::class, 'getUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::delete('/users/bulk', [AdminController::class, 'bulkDeleteUser']);
        
        // Admin users (role_id = 1)
        Route::get('/admin-users', [AdminController::class, 'getAdminUsers']);
        Route::get('/admin-users/list', [AdminController::class, 'getAdminUsersList']);
        
        // Blog CRUD operations
        Route::get('/blogs', [AdminController::class, 'getBlogList']);
        Route::get('/blogs/stats', [AdminController::class, 'getBlogStats']);
        Route::post('/blogs', [AdminController::class, 'createBlog']);
        Route::get('/blogs/{id}', [AdminController::class, 'getBlog']);
        Route::put('/blogs/{id}', [AdminController::class, 'updateBlog']);
        Route::delete('/blogs/{id}', [AdminController::class, 'deleteBlog']);
        Route::delete('/blogs/bulk', [AdminController::class, 'bulkDeleteBlog']);
    });
});

// Client authentication routes
Route::post('/auth/login', [LoginController::class, 'login'])->name('login');
Route::middleware('auth:api')->group(function () {
    Route::get('/auth/profile', [LoginController::class, 'profile']);
    Route::post('/auth/{id}/profile', [LoginController::class, 'updateProfile']);
});
