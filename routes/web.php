<?php

use App\Http\Controllers\Admin\AssessmentMonitoringController;
use App\Http\Controllers\Admin\AssessmentParticipantAccountController;
use App\Http\Controllers\Admin\AssessmentProgramController;
use App\Http\Controllers\Admin\AssessmentProgramSetupController;
use App\Http\Controllers\Admin\AssessorAssignmentController;
use App\Http\Controllers\Admin\PapiConfigurationController;
use App\Http\Controllers\Admin\PapiQuestionImportController;
use App\Http\Controllers\Admin\PapiScoringKeyImportController;
use App\Http\Controllers\Admin\RecruitmentBatchController;
use App\Http\Controllers\Admin\RecruitmentParticipantImportController;
use App\Http\Controllers\Admin\RecruitmentPsychologicalResultController;
use App\Http\Controllers\Admin\SimulationScenarioController;
use App\Http\Controllers\Assessor\AssessmentWorkspaceController;
use App\Http\Controllers\Assessor\AssignedSimulationController;
use App\Http\Controllers\Assessor\SimulationReviewController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Participant\AssessmentResultController;
use App\Http\Controllers\Participant\AssessmentScheduleController;
use App\Http\Controllers\Participant\AssessmentSimulationController;
use App\Http\Controllers\Participant\RecruitmentExamController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\SuperAdmin\AccessControlController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/portal')->name('dashboard');
    Route::get('/portal', [PortalController::class, 'index'])->name('portal.index');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('role:super_admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'superAdmin'])->name('dashboard');
        Route::resource('users', UserManagementController::class)->except(['show', 'destroy']);
        Route::get('/roles', [AccessControlController::class, 'index'])->name('roles.index');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    Route::middleware('role:admin,super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::resource('assessment-programs', AssessmentProgramController::class)
            ->except(['show']);
        Route::get('assessment-programs/{assessmentProgram}/setup', [AssessmentProgramSetupController::class, 'edit'])->name('assessment-programs.setup.edit');
        Route::put('assessment-programs/{assessmentProgram}/setup', [AssessmentProgramSetupController::class, 'update'])->name('assessment-programs.setup.update');
        Route::resource('simulations', SimulationScenarioController::class)
            ->parameters(['simulations' => 'simulationScenario'])
            ->only(['index', 'edit', 'update']);
        Route::resource('participants', AssessmentParticipantAccountController::class)->except(['show']);
        Route::get('/assessor-assignments', [AssessorAssignmentController::class, 'index'])->name('assessor-assignments.index');
        Route::get('/assessor-assignments/{assessmentProgram}/edit', [AssessorAssignmentController::class, 'edit'])->name('assessor-assignments.edit');
        Route::put('/assessor-assignments/{assessmentProgram}', [AssessorAssignmentController::class, 'update'])->name('assessor-assignments.update');
        Route::get('/monitoring', [AssessmentMonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/monitoring/{assessmentProgram}', [AssessmentMonitoringController::class, 'show'])->name('monitoring.show');
        Route::get('/recruitment/import-template', [RecruitmentParticipantImportController::class, 'template'])->name('recruitment.import.template');
        Route::get('/recruitment-results', [RecruitmentPsychologicalResultController::class, 'index'])->name('recruitment-results.index');
        Route::get('/recruitment-results/batches/{batch}', [RecruitmentPsychologicalResultController::class, 'batch'])->name('recruitment-results.batch');
        Route::get('/recruitment-results/{result}', [RecruitmentPsychologicalResultController::class, 'show'])->name('recruitment-results.show');
        Route::get('/recruitment/psychotests', [PapiConfigurationController::class, 'index'])->name('recruitment.psychotests.index');
        Route::post('/recruitment/psychotests/{psychologicalTest}/versions', [PapiConfigurationController::class, 'store'])->name('recruitment.psychotests.versions.store');
        Route::get('/recruitment/psychotests/versions/{version}', [PapiConfigurationController::class, 'show'])->name('recruitment.psychotests.show');
        Route::post('/recruitment/psychotests/versions/{version}/validate', [PapiConfigurationController::class, 'validateConfiguration'])->name('recruitment.psychotests.validate');
        Route::post('/recruitment/psychotests/versions/{version}/publish', [PapiConfigurationController::class, 'publish'])->name('recruitment.psychotests.publish');
        Route::get('/recruitment/psychotests/question-template', [PapiQuestionImportController::class, 'template'])->name('recruitment.psychotests.questions.template');
        Route::get('/recruitment/psychotests/versions/{version}/questions/import', [PapiQuestionImportController::class, 'create'])->name('recruitment.psychotests.questions.import.create');
        Route::post('/recruitment/psychotests/versions/{version}/questions/import/preview', [PapiQuestionImportController::class, 'preview'])->name('recruitment.psychotests.questions.import.preview');
        Route::post('/recruitment/psychotests/versions/{version}/questions/import', [PapiQuestionImportController::class, 'store'])->name('recruitment.psychotests.questions.import.store');
        Route::get('/recruitment/psychotests/scoring-template', [PapiScoringKeyImportController::class, 'template'])->name('recruitment.psychotests.scoring.template');
        Route::get('/recruitment/psychotests/versions/{version}/scoring/import', [PapiScoringKeyImportController::class, 'create'])->name('recruitment.psychotests.scoring.import.create');
        Route::post('/recruitment/psychotests/versions/{version}/scoring/import/preview', [PapiScoringKeyImportController::class, 'preview'])->name('recruitment.psychotests.scoring.import.preview');
        Route::post('/recruitment/psychotests/versions/{version}/scoring/import', [PapiScoringKeyImportController::class, 'store'])->name('recruitment.psychotests.scoring.import.store');
        Route::get('/recruitment/{recruitment}/participants/import', [RecruitmentParticipantImportController::class, 'create'])->name('recruitment.import.create');
        Route::post('/recruitment/{recruitment}/participants/import/preview', [RecruitmentParticipantImportController::class, 'preview'])->name('recruitment.import.preview');
        Route::post('/recruitment/{recruitment}/participants/import', [RecruitmentParticipantImportController::class, 'store'])->name('recruitment.import.store');
        Route::delete('/recruitment/{recruitment}/participants/import', [RecruitmentParticipantImportController::class, 'cancel'])->name('recruitment.import.cancel');
        Route::post('/recruitment/{recruitment}/psychotests', [RecruitmentBatchController::class, 'assignPsychologicalTest'])->name('recruitment.psychotests.assign');
        Route::resource('recruitment', RecruitmentBatchController::class);
    });

    Route::middleware('role:asesor')->prefix('asesor')->name('asesor.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'asesor'])->name('dashboard');
        Route::get('/simulations', [AssignedSimulationController::class, 'index'])->name('simulations.index');
        Route::get('/simulations/{programSimulation}', [AssignedSimulationController::class, 'show'])->name('simulations.show');
        Route::get('/submissions/{submission}/download', [AssignedSimulationController::class, 'download'])->name('submissions.download');
        Route::get('/submissions/{submission}/preview', [AssignedSimulationController::class, 'preview'])->name('submissions.preview');
        Route::get('/simulations/{programSimulation}/materials/{material}/preview', [AssignedSimulationController::class, 'materialPreview'])->name('materials.preview');
        Route::get('/participants', [AssessmentWorkspaceController::class, 'participants'])->name('participants.index');
        Route::put('/participants/{participant}/simulation-three-choice', [AssessmentWorkspaceController::class, 'updateSimulationThreeChoice'])->middleware('throttle:20,1')->name('participants.simulation-three-choice.update');
        Route::get('/monitoring', [AssessmentWorkspaceController::class, 'monitoring'])->name('monitoring.index');
        Route::get('/reviews', [SimulationReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/{session}/edit', [SimulationReviewController::class, 'edit'])->name('reviews.edit');
        Route::put('/reviews/{session}', [SimulationReviewController::class, 'update'])->name('reviews.update');
    });

    Route::middleware('role:peserta_assessment')->prefix('peserta-assessment')->name('peserta-assessment.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'pesertaAssessment'])->name('dashboard');
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
        Route::get('/schedule', [AssessmentScheduleController::class, 'index'])->name('schedule.index');
        Route::get('/results', [AssessmentResultController::class, 'index'])->name('results.index');
    });

    Route::middleware('role:peserta_rekrutmen')->prefix('peserta-rekrutmen')->name('peserta-rekrutmen.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'pesertaRekrutmen'])->name('dashboard');
        Route::get('/exams', [RecruitmentExamController::class, 'index'])->name('exams.index');
        Route::get('/exams/{assignment}/instructions', [RecruitmentExamController::class, 'instructions'])->name('exams.instructions');
        Route::post('/exams/{assignment}/start', [RecruitmentExamController::class, 'start'])->name('exams.start');
        Route::get('/exam-sessions/{session}', [RecruitmentExamController::class, 'take'])->name('exams.take');
        Route::post('/exam-sessions/{session}/answers', [RecruitmentExamController::class, 'answer'])->middleware('throttle:120,1')->name('exams.answers.store');
        Route::post('/exam-sessions/{session}/submit', [RecruitmentExamController::class, 'submit'])->name('exams.submit');
        Route::get('/stages', [DashboardController::class, 'placeholder'])->defaults('pageTitle', 'Tahapan Seleksi')->name('stages.index');
        Route::get('/results', [DashboardController::class, 'placeholder'])->defaults('pageTitle', 'Hasil Seleksi')->name('results.index');
    });
});
