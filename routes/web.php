<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HolidaysController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimeSheetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');
    Route::get('/', [DashboardController::class,'index'])->name('pages-home');
    //clients
    Route::get('clients-list',[ClientController::class,'index'])->name('clients.index');
    Route::post('client-list',[ClientController::class,'store'])->name('clients.store');
    Route::delete('client-list/{id}',[ClientController::class,'destroy'])->name('clients.destroy');
    Route::get('client-list/{id}/edit',[ClientController::class,'edit'])->name('clients.edit');
    Route::get('clients',[ClientController::class,'clients'])->name('clients.get');
    //tasks
    Route::get('tasks-list',[TaskController::class,'index'])->name('tasks.index');
    Route::post('task-list',[TaskController::class,'store'])->name('tasks.store');
    Route::get('tasks',[TaskController::class,'tasks'])->name('tasks.get');
    Route::delete('task-list/{id}',[TaskController::class,'destroy'])->name('tasks.destroy');
    Route::get('task-list/{id}/edit',[TaskController::class,'edit'])->name('tasks.edit');
    //admin
    Route::get('task-add/{id}/admin',[TaskController::class,'AdminAddTask'])->name('tasks.addtaskadmin')->middleware('admin');
    Route::post('task-add/create/admin',[TaskController::class,'AdminStoreTask'])->name('tasks.storetaskadmin')->middleware('admin');
   
    Route::get('client-add/{id}/admin',[TimeSheetController::class,'AdminClient'])->name('timesheet.addeventadmin')->middleware('admin');
    Route::post('client-add/{id}/admin/create',[TimeSheetController::class,'AdminClientStore'])->name('timesheet.addstoreadmin')->middleware('admin');
    //time sheet
    Route::get('time-sheet',[TimeSheetController::class,'index'])->name('timesheet.index');
    Route::get('/events', [TimeSheetController::class, 'events'])->name('timesheet.events');
    Route::post('/events', [TimeSheetController::class, 'store'])->name('timesheet.store');
    Route::post('/events/update', [TimeSheetController::class, 'update'])->name('timesheet.update');
    Route::post('/events/delete', [TimeSheetController::class, 'destroy'])->name('timesheet.destroy');
    Route::post('/events/copy', [TimeSheetController::class, 'copy'])->name('timesheet.copy');
    Route::post('/events/workday', [TimeSheetController::class, 'workday'])->name('timesheet.workday');
    //report
    Route::get('/reports',[ReportController::class,'index'])->name('timesheet.report');
    Route::get('/download-report/{duration}/{date}/{client}',[ReportController::class,'download'])->name('timesheet.download');
    //dashboard users
    Route::get('users',[DashboardController::class,'users'])->name('users.get');
    //holidays
    Route::get('holidays/{id}/admin',[HolidaysController::class,'index'])->name('holidays.index')->middleware('admin');
    Route::post('holidays/{id}/admin/create',[HolidaysController::class,'store'])->name('holidays.store')->middleware('admin');
    
});
