<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SetupController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispositionController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventCertificateController;
use App\Http\Controllers\IncomingLetterController;
use App\Http\Controllers\OutgoingLetterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! User::query()->exists()) {
        return view('auth.setup');
    }

    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});
Route::middleware('guest')->group(function (): void {
    Route::get('/setup', [SetupController::class, 'create'])->name('setup');
    Route::post('/setup', [SetupController::class, 'store'])->middleware('throttle:5,1')->name('setup.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
});
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/documents/{type}/{id}', [DocumentController::class, 'show'])->whereIn('type', ['incoming', 'outgoing', 'disposition'])->name('documents.show');
    Route::middleware('role:tu')->group(function (): void {
        Route::get('/incoming/create', [IncomingLetterController::class, 'create'])->name('incoming.create');
        Route::post('/incoming', [IncomingLetterController::class, 'store'])->name('incoming.store');
        Route::get('/incoming/{incoming}/edit', [IncomingLetterController::class, 'edit'])->name('incoming.edit');
        Route::put('/incoming/{incoming}', [IncomingLetterController::class, 'update'])->name('incoming.update');
        Route::get('/outgoing/create', [OutgoingLetterController::class, 'create'])->name('outgoing.create');
        Route::get('/outgoing/create/{type}', [OutgoingLetterController::class, 'createType'])->name('outgoing.create.type');
        Route::post('/outgoing', [OutgoingLetterController::class, 'store'])->name('outgoing.store');
        Route::get('/outgoing/{outgoing}/edit', [OutgoingLetterController::class, 'edit'])->name('outgoing.edit');
        Route::put('/outgoing/{outgoing}', [OutgoingLetterController::class, 'update'])->name('outgoing.update');
    });
    Route::middleware('role:tu,kepala_tu,kepsek')->group(function (): void {
        Route::get('/incoming', [IncomingLetterController::class, 'index'])->name('incoming.index');
        Route::get('/incoming/{incoming}/disposition/print', [DispositionController::class, 'printIncoming'])->name('incoming.disposition.print');
        Route::get('/incoming/{incoming}', [IncomingLetterController::class, 'show'])->name('incoming.show');
        Route::get('/incoming/{incoming}/disposition/edit', [DispositionController::class, 'editIncoming'])->middleware('role:kepsek')->name('incoming.disposition.edit');
        Route::put('/incoming/{incoming}/disposition', [DispositionController::class, 'updateIncoming'])->middleware('role:kepsek')->name('incoming.disposition.update');
        Route::post('/incoming/{incoming}/follow-up-confirmation', [DispositionController::class, 'confirmIncomingFollowUp'])->middleware('role:tu')->name('incoming.follow_up.confirm');
    });
    Route::delete('/incoming/{incoming}', [IncomingLetterController::class, 'destroy'])->middleware('role:kepala_tu')->name('incoming.destroy');
    Route::middleware('role:tu,kepala_tu,kepsek')->group(function (): void {
        Route::get('/outgoing', [OutgoingLetterController::class, 'index'])->name('outgoing.index');
        Route::get('/outgoing/{outgoing}/print', [OutgoingLetterController::class, 'print'])->name('outgoing.print');
        Route::get('/outgoing/{outgoing}/disposition/print', [DispositionController::class, 'printOutgoing'])->name('outgoing.disposition.print');
        Route::get('/outgoing/{outgoing}', [OutgoingLetterController::class, 'show'])->name('outgoing.show');
    });
    Route::delete('/outgoing/{outgoing}', [OutgoingLetterController::class, 'destroy'])->middleware('role:tu,kepala_tu')->name('outgoing.destroy');
    Route::post('/outgoing/{outgoing}/verify', [OutgoingLetterController::class, 'verify'])->middleware('role:kepala_tu')->name('outgoing.verify');
    Route::post('/outgoing/{outgoing}/principal-review', [OutgoingLetterController::class, 'principalReview'])->middleware('role:kepsek')->name('outgoing.principal_review');
    Route::post('/incoming/{incoming}/disposition', [DispositionController::class, 'incoming'])->middleware('role:kepsek')->name('incoming.disposition');
    Route::middleware('role:humas,kepala_tu')->group(function (): void {
        Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');
        Route::get('/students/template', [StudentController::class, 'template'])->name('students.template');
        Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
        Route::get('/certificates/download-all', [CertificateController::class, 'downloadAll'])->name('certificates.download_all');
        Route::resource('students', StudentController::class)->except(['show']);
        Route::post('/students/{student}/complete', [StudentController::class, 'complete'])->name('students.complete');
        Route::get('/certificates/{student}', [CertificateController::class, 'show'])->name('certificates.show');
        Route::put('/certificates/{student}/signatory', [CertificateController::class, 'updateSignatory'])->name('certificates.signatory.update');
        Route::get('/companies/{company}/mou', [CompanyController::class, 'downloadMou'])->name('companies.mou.download');
        Route::get('/companies/export', [CompanyController::class, 'export'])->name('companies.export');
        Route::resource('companies', CompanyController::class)->except(['show']);
    });
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{section}', [ReportController::class, 'export'])->name('reports.export');
    Route::middleware('role:tu,humas')->group(function (): void {
        Route::get('/event-certificates/print-all', [EventCertificateController::class, 'printAll'])->name('event-certificates.print_all');
        Route::get('/event-certificates', [EventCertificateController::class, 'index'])->name('event-certificates.index');
        Route::get('/event-certificates/create', [EventCertificateController::class, 'create'])->name('event-certificates.create');
        Route::post('/event-certificates', [EventCertificateController::class, 'store'])->name('event-certificates.store');
        Route::get('/event-certificates/{certificate}', [EventCertificateController::class, 'show'])->name('event-certificates.show');
        Route::delete('/event-certificates/{certificate}', [EventCertificateController::class, 'destroy'])->name('event-certificates.destroy');
    });
    Route::middleware('role:kepala_tu')->group(function (): void {
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::resource('users', UserController::class)->except(['show']);
    });
});
