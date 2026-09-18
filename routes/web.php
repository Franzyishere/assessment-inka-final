<?php

use App\Http\Controllers\Admin\AssessmentMonitoringController;
use App\Http\Controllers\Admin\AssessmentParticipantAccountController;
use App\Http\Controllers\Admin\AssessmentProgramController;
use App\Http\Controllers\Admin\AssessmentProgramSetupController;
use App\Http\Controllers\Admin\AssessorAssignmentController;
use App\Http\Controllers\Admin\SimulationScenarioController;
use App\Http\Controllers\Assessor\AssessmentWorkspaceController;
use App\Http\Controllers\Assessor\AssignedSimulationController;
use App\Http\Controllers\Assessor\SimulationReviewController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Participant\AssessmentSimulationController;
use App\Http\Controllers\SuperAdmin\AccessControlController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/assessment/invitations/{token}', [\App\Http\Controllers\Auth\AssessmentInvitationController::class, 'show'])->where('token', '[A-Za-z0-9]{64}')->name('assessment.invitation');
    Route::post('/assessment/invitations/{token}/otp', [\App\Http\Controllers\Auth\AssessmentInvitationController::class, 'requestOtp'])->where('token', '[A-Za-z0-9]{64}')->middleware('throttle:assessment-otp')->name('assessment.invitation.otp');
    Route::post('/assessment/invitations/{token}/verify', [\App\Http\Controllers\Auth\AssessmentInvitationController::class, 'verify'])->where('token', '[A-Za-z0-9]{64}')->middleware('throttle:assessment-verify')->name('assessment.invitation.verify');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/header-notifications', \App\Http\Controllers\HeaderNotificationController::class)->middleware('throttle:60,1')->name('header.notifications');
    Route::get('/', [DashboardController::class, 'redirect'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('role:super_admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'superAdmin'])->name('dashboard');
        Route::resource('users', UserManagementController::class)->except(['show']);
        Route::get('/roles', [AccessControlController::class, 'index'])->name('roles.index');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    Route::middleware('role:admin,super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/invitations', [\App\Http\Controllers\Admin\AssessmentInvitationController::class, 'index'])->name('invitations.index');
        Route::get('/invitations/{assessmentProgram}', [\App\Http\Controllers\Admin\AssessmentInvitationController::class, 'show'])->name('invitations.show');
        Route::post('/invitations/{assessmentProgram}', [\App\Http\Controllers\Admin\AssessmentInvitationController::class, 'send'])->middleware('throttle:10,1')->name('invitations.send');
        Route::delete('/invitations/{assessmentProgram}/{invitation}', [\App\Http\Controllers\Admin\AssessmentInvitationController::class, 'revoke'])->name('invitations.revoke');
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::delete('assessment-programs/bulk', [AssessmentProgramController::class, 'bulkDestroy'])->name('assessment-programs.bulk-destroy');
        Route::resource('assessment-programs', AssessmentProgramController::class)
            ->except(['show']);
        Route::get('assessment-programs/{assessmentProgram}/setup', [AssessmentProgramSetupController::class, 'edit'])->name('assessment-programs.setup.edit');
        Route::put('assessment-programs/{assessmentProgram}/setup', [AssessmentProgramSetupController::class, 'update'])->name('assessment-programs.setup.update');
        Route::resource('simulations', SimulationScenarioController::class)
            ->parameters(['simulations' => 'simulationScenario'])
            ->only(['index', 'edit', 'update']);
        Route::delete('participants/bulk', [AssessmentParticipantAccountController::class, 'bulkDestroy'])->name('participants.bulk-destroy');
        Route::resource('participants', AssessmentParticipantAccountController::class)->except(['show']);
        Route::get('/assessor-assignments', [AssessorAssignmentController::class, 'index'])->name('assessor-assignments.index');
        Route::get('/assessor-assignments/{assessmentProgram}/edit', [AssessorAssignmentController::class, 'edit'])->name('assessor-assignments.edit');
        Route::put('/assessor-assignments/{assessmentProgram}', [AssessorAssignmentController::class, 'update'])->name('assessor-assignments.update');
        Route::get('/monitoring', [AssessmentMonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/monitoring/{assessmentProgram}', [AssessmentMonitoringController::class, 'show'])->name('monitoring.show');
    });

    Route::middleware('role:asesor')->prefix('asesor')->name('asesor.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'asesor'])->name('dashboard');
        Route::get('/simulations', [AssignedSimulationController::class, 'index'])->name('simulations.index');
        Route::get('/simulations/programs/{program}', [AssignedSimulationController::class, 'program'])->name('simulations.program');
        Route::get('/simulations/{programSimulation}', [AssignedSimulationController::class, 'show'])->name('simulations.show');
        Route::get('/submissions/{submission}/download', [AssignedSimulationController::class, 'download'])->name('submissions.download');
        Route::get('/submissions/{submission}/preview', [AssignedSimulationController::class, 'preview'])->name('submissions.preview');
        Route::get('/simulations/{programSimulation}/materials/{material}/preview', [AssignedSimulationController::class, 'materialPreview'])->name('materials.preview');
        Route::get('/participants', [AssessmentWorkspaceController::class, 'participants'])->name('participants.index');
        Route::get('/monitoring', [AssessmentWorkspaceController::class, 'monitoring'])->name('monitoring.index');
        Route::get('/monitoring/programs/{program}', [AssessmentWorkspaceController::class, 'monitoringProgram'])->name('monitoring.program');
        Route::get('/reviews', [SimulationReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/programs/{program}', [SimulationReviewController::class, 'program'])->name('reviews.program');
        Route::get('/reviews/{session}/edit', [SimulationReviewController::class, 'edit'])->name('reviews.edit');
        Route::put('/reviews/{session}', [SimulationReviewController::class, 'update'])->name('reviews.update');
    });

    Route::middleware('role:peserta_assessment')->prefix('peserta-assessment')->name('peserta-assessment.')->group(function () {
        Route::get('/dashboard', fn () => to_route('peserta-assessment.simulations.index'))->name('dashboard');
        Route::get('/simulations', [AssessmentSimulationController::class, 'index'])->name('simulations.index');
        Route::get('/simulations/{programSimulation}', [AssessmentSimulationController::class, 'show'])->name('simulations.show');
        Route::post('/simulations/{programSimulation}/start', [AssessmentSimulationController::class, 'start'])->name('simulations.start');
        Route::get('/simulations/{programSimulation}/material/{page}', [AssessmentSimulationController::class, 'material'])->name('simulations.material');
        Route::get('/simulations/{programSimulation}/materials/{material}/pdf', [AssessmentSimulationController::class, 'materialPdf'])->name('simulations.material.pdf');
        Route::put('/simulations/{programSimulation}/material/{page}', [AssessmentSimulationController::class, 'saveMaterial'])->name('simulations.material.save');
        Route::get('/simulations/{programSimulation}/lgd-review', [AssessmentSimulationController::class, 'lgdReview'])->name('simulations.lgd-review');
        Route::post('/simulations/{programSimulation}/lgd-review/submit', [AssessmentSimulationController::class, 'submitLgd'])->name('simulations.lgd-review.submit');
        Route::post('/simulations/{programSimulation}/submit', [AssessmentSimulationController::class, 'submit'])->name('simulations.submit');
        Route::post('/simulations/{programSimulation}/events', [AssessmentSimulationController::class, 'recordEvent'])->middleware('throttle:30,1')->name('simulations.events.store');
        Route::get('/simulations/{programSimulation}/presentation', [AssessmentSimulationController::class, 'presentation'])->name('simulations.presentation');
        Route::post('/simulations/{programSimulation}/presentation', [AssessmentSimulationController::class, 'submitPresentation'])->name('simulations.presentation.submit');
        Route::get('/simulations/{programSimulation}/case-response', [AssessmentSimulationController::class, 'caseResponse'])->name('simulations.case-response');
        Route::post('/simulations/{programSimulation}/case-response', [AssessmentSimulationController::class, 'submitCaseResponse'])->name('simulations.case-response.submit');
        Route::get('/schedule', fn () => to_route('peserta-assessment.simulations.index'))->name('schedule.index');
        Route::get('/results', fn () => to_route('peserta-assessment.simulations.index'))->name('results.index');
    });

});
