<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\TicketController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\UserController;

Route::controller(RegisterController::class)->group(function(){
    Route::post('register', 'register')->name('register');
    Route::post('login', 'login')->name('login');
});

Route::middleware('auth:sanctum')->group( function () {
    Route::get('user', function (Request $request) {
        return $request->user();
    })->name('user');

    // management tugas
    Route::controller(TicketController::class)->group(function () {
        Route::get('/ticket', 'index');
        Route::post('/add-ticket', 'addTicket');
        Route::post('/view-ticket/{id}', 'show');
        Route::post('/edit-ticket', 'update');
        Route::delete('/delete-ticket/{id}', 'destroy');

        // pemberian tugas
        Route::post('/asign-ticket', 'asignTicket');

        // pengerjaan tugas
        Route::post('/update-ticket-status', 'updateStatus');
    });

    // Notifikasi
    Route::controller(NotificationController::class)->group(function () {
        Route::post('/notif', 'indexNotif');
        Route::post('/view-notif/{id}', 'showNotif');
    });
});