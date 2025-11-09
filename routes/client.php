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
Route::prefix('payments')->group(function () {
    Route::post('/order', [PaymentController::class, 'createOrder']);
});
Route::post('/membership-signup/confirm', [MembershipController::class, 'confirmPayment']);
Route::get('eventValidationHandle', [PaymentController::class, 'eventValidationHandle']);
Route::post('/ainet2025ppf', [FormController::class, 'storePpfs']);
Route::post('/ainet2020drf',[FormController::class, 'storeDrfs']);
Route::post('/check-user', [FormController::class, 'checkUserExists']);

// Public website endpoints
Route::get('/banners', [AdminController::class, 'getWebsiteBanners']);
Route::get('/conference', [AdminController::class, 'getWebsiteConference']);
Route::get('/events', [AdminController::class, 'getWebsiteEvents']);
Route::get('/partners', [AdminController::class, 'getWebsitePartners']);
Route::get('/galleries', [AdminController::class, 'getWebsiteGalleries']);
Route::get('/news', [AdminController::class, 'getWebsiteNews']);
Route::post('/newsletter/subscribe', [AdminController::class, 'subscribeNewsletter']);

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
        Route::delete('/users/bulk', [AdminController::class, 'bulkDeleteUser']);
        Route::get('/users/{id}', [AdminController::class, 'getUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        
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

        // Banner CRUD operations
        Route::get('/banners', [AdminController::class, 'getBannerList']);
        Route::post('/banners', [AdminController::class, 'createBanner']);
        Route::get('/banners/{id}', [AdminController::class, 'getBanner']);
        Route::put('/banners/{id}', [AdminController::class, 'updateBanner']);
        Route::delete('/banners/{id}', [AdminController::class, 'deleteBanner']);
        Route::delete('/banners/bulk', [AdminController::class, 'bulkDeleteBanner']);

        // Event CRUD operations
        Route::get('/events', [AdminController::class, 'getEventList']);
        Route::post('/events', [AdminController::class, 'createEvent']);
        Route::get('/events/{id}', [AdminController::class, 'getEvent']);
        Route::put('/events/{id}', [AdminController::class, 'updateEvent']);
        Route::delete('/events/{id}', [AdminController::class, 'deleteEvent']);
        Route::delete('/events/bulk', [AdminController::class, 'bulkDeleteEvent']);

        // Partner CRUD operations
        Route::get('/partners', [AdminController::class, 'getPartnerList']);
        Route::post('/partners', [AdminController::class, 'createPartner']);
        Route::get('/partners/{id}', [AdminController::class, 'getPartner']);
        Route::put('/partners/{id}', [AdminController::class, 'updatePartner']);
        Route::delete('/partners/{id}', [AdminController::class, 'deletePartner']);
        Route::delete('/partners/bulk', [AdminController::class, 'bulkDeletePartner']);

        // Gallery CRUD operations
        Route::get('/galleries', [AdminController::class, 'getGalleryList']);
        Route::post('/galleries', [AdminController::class, 'createGallery']);
        Route::get('/galleries/{id}', [AdminController::class, 'getGallery']);
        Route::put('/galleries/{id}', [AdminController::class, 'updateGallery']);
        Route::delete('/galleries/{id}', [AdminController::class, 'deleteGallery']);
        Route::delete('/galleries/bulk', [AdminController::class, 'bulkDeleteGallery']);

        // Newsletter CRUD operations
        Route::get('/newsletters', [AdminController::class, 'getNewsletterList']);
        Route::get('/newsletters/{id}', [AdminController::class, 'getNewsletterSubscription']);
        Route::delete('/newsletters/{id}', [AdminController::class, 'deleteNewsletter']);
        Route::delete('/newsletters/bulk', [AdminController::class, 'bulkDeleteNewsletter']);

        // News CRUD operations (AINET In News)
        Route::get('/news', [AdminController::class, 'getNewsList']);
        Route::post('/news', [AdminController::class, 'createNews']);
        Route::get('/news/{id}', [AdminController::class, 'getNews']);
        Route::put('/news/{id}', [AdminController::class, 'updateNews']);
        Route::delete('/news/{id}', [AdminController::class, 'deleteNews']);
        Route::delete('/news/bulk', [AdminController::class, 'bulkDeleteNews']);
        
        // Membership CRUD operations
        Route::get('/memberships', [AdminController::class, 'getMembershipList']);
        Route::get('/memberships/export', [AdminController::class, 'exportMembership']);
        Route::get('/memberships/trashed', [AdminController::class, 'getTrashedMemberships']);
        Route::delete('/memberships/{id}', [AdminController::class, 'deleteMembership']);
        Route::post('/memberships/{id}/restore', [AdminController::class, 'restoreMembership']);
    });
});

// Client authentication routes
Route::post('/auth/login', [LoginController::class, 'login'])->name('login');
Route::post('/auth/forgot-password', [LoginController::class, 'forgotPassword'])->name('password.forgot');
Route::post('/auth/reset-password', [LoginController::class, 'resetPassword'])->name('password.reset');
Route::middleware('auth:api')->group(function () {
    Route::get('/auth/profile', [LoginController::class, 'profile']);
    Route::post('/auth/{id}/profile', [LoginController::class, 'updateProfile']);
});
