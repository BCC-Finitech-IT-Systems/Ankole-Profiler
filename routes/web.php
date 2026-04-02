<?php

use App\Exports\OrganizationTemplateExport;
use App\Http\Controllers\AfricasTalkingCallbackController;
use App\Http\Controllers\AllPersonsListController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonSearchController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RelationshipController;
use App\Http\Controllers\SMSWebhookController;
use App\Http\Requests\CustomVerifyEmailRequest;
use App\Livewire\Dashboard\DashboardComponent;
use App\Livewire\Dashboard\OrgAdminDashboardComponent;
use App\Livewire\Departments\DepartmentComponent;
use App\Livewire\Departments\DepartmentsDashboard;
use App\Livewire\Organizations\ImportOrganizations;
use App\Livewire\Person\Notifications as PersonNotificationsLivewire;
use App\Models\CustomField;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

$authVerifiedMiddleware = [
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
];

Route::prefix('organizations')->name('organizations.')->group(function () use ($authVerifiedMiddleware) {
    Route::post('/export-template', function (Request $request) {
        $fields = $request->input('fields', []);
        $headers = array_map('trim', $fields);

        foreach ($fields as $field) {
            CustomField::updateOrCreate([
                'model_type' => 'Organization_template',
                'model_id' => 0,
                'field_name' => $field,
            ], [
                'field_label' => ucfirst(str_replace('_', ' ', $field)),
                'field_type' => 'string',
                'field_options' => null,
                'is_required' => false,
                'validation_rules' => null,
                'group' => null,
                'order' => null,
                'description' => null,
            ]);
        }

        $export = new OrganizationTemplateExport([], $headers);

        return Excel::download($export, 'custom_Organization_template.xlsx');
    })->name('export-template');

    Route::middleware($authVerifiedMiddleware)->group(function () {
        Route::get('/template', function () {
            $export = new OrganizationTemplateExport();

            return Excel::download($export, 'Organization_import_template.xlsx');
        })->name('template');

        Route::get('/import', ImportOrganizations::class)->name('import');
    });
});
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/person/self-register', App\Livewire\Person\PersonSelfRegistrationComponent::class)
    ->name('person.self-register');

Route::get('/', function () {
    if (auth()->check()) {
        if (!auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->route('dashboard');
    }

    return view('auth.login');
});

Route::get('/test-at', function () {
    function testApiConnection($AT, $from, $to)
    {
        try {
            $voice = $AT->voice();
            $results = $voice->call(['from' => $from, 'to' => $to]);

            return ['success' => true, 'message' => 'API key is active!'];
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'authentication') !== false) {
                return ['success' => false, 'message' => 'API key still activating...'];
            }

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
});

Route::middleware($authVerifiedMiddleware)->group(function () {
    Route::get('/dashboard', DashboardComponent::class)->name('dashboard');

    Route::get('/admin/dash', OrgAdminDashboardComponent::class)->name('admin.dashboard');

    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/', App\Livewire\Organizations\Index::class)->name('index');
        Route::get('/current-project', App\Livewire\Organizations\CurrentProject::class)->name('current-project');
        Route::get('/create', App\Livewire\Organizations\Create::class)->name('create');
        Route::get('/{id}', App\Livewire\Organizations\Show::class)->name('show');
    });

    Route::prefix('persons')->group(function () {
        Route::get('/all', [AllPersonsListController::class, 'index'])->name('persons.all');
        Route::get('/create/{edit?}', App\Livewire\Person\CreatePersonsComponent::class)->name('persons.create');
        Route::get('/import', App\Livewire\Person\ImportPersons::class)->name('persons.import');
        Route::get('/export', App\Livewire\Person\ExportPersons::class)->name('persons.export');
        Route::get('/products', App\Livewire\PersonProducts::class)->name('person-products');
        Route::get('/profile-current', App\Livewire\Person\ProfileView::class)->name('persons.profile-current');

        Route::get('/search', [PersonSearchController::class, 'index2'])->name('persons.search');
        Route::get('/search/api', [PersonSearchController::class, 'search'])->name('persons.search.api');
        Route::get('/search/suggestions', [PersonSearchController::class, 'suggestions'])->name('persons.search.suggestions');
        Route::post('/search/export', [PersonSearchController::class, 'export'])->name('persons.search.export');
        Route::get('/{id}', [PersonController::class, 'show'])->name('persons.show');
    });

    Route::get('/my-Organization-units', App\Livewire\Person\OrganizationUnitsList::class)
        ->name('person.Organization-units');

    Route::get('/person/notifications', PersonNotificationsLivewire::class)
        ->name('person.notifications');

    Route::prefix('organization-units')->name('organization-units.')->group(function () {
        Route::get('/', App\Livewire\Organizations\ListOrganizationUnits::class)->name('index');
        Route::get('/create', App\Livewire\Organizations\CreateOrganizationUnit::class)->name('create');
        Route::get('/applications', App\Livewire\Organizations\ReviewUnitApplications::class)->name('applications');
    });

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/{project}/persons', function ($projectId) {
            $project = \App\Models\Project::with('persons')->findOrFail($projectId);
            $persons = $project->persons;
            return view('projects.persons', compact('project', 'persons'));
        })->name('persons')->middleware('can:view-projects');
        Route::get('/', [ProjectController::class, 'index'])
            ->name('index')
            ->middleware('can:view-projects');

        Route::post('/', [ProjectController::class, 'store'])
            ->name('store')
            ->middleware('can:create-projects');

        Route::get('/{project}', [ProjectController::class, 'show'])
            ->name('show')
            ->middleware('can:view-projects');

        Route::put('/{project}', [ProjectController::class, 'update'])
            ->name('update')
            ->middleware('can:edit-projects');

        Route::delete('/{project}', [ProjectController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:delete-projects');

        Route::put('/{project}/persons', [ProjectController::class, 'syncPersons'])
            ->name('persons.sync')
            ->middleware('can:manage-project-persons');
    });

    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/dashboard', DepartmentsDashboard::class)
            ->name('dashboard');

        Route::post('/', [DepartmentController::class, 'store'])
            ->name('store');

        Route::get('/{department}', [DepartmentController::class, 'show'])
            ->name('show');

        Route::put('/{department}', [DepartmentController::class, 'update'])
            ->name('update');

        Route::delete('/{department}', [DepartmentController::class, 'destroy'])
            ->name('destroy');
        Route::get('/', DepartmentComponent::class)
            ->name('index');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', App\Livewire\Admin\UserManager::class)->name('users.index');
        Route::get('/permissions', App\Livewire\Admin\PermissionManager::class)->name('permissions.index');
        Route::get('/roles', App\Livewire\Admin\RoleManager::class)->name('roles.index');
        Route::get('/role-types', App\Livewire\Admin\RoleTypeManager::class)->name('role-types.index');
        Route::get('/allow-email-domains', App\Livewire\Admin\AllowedEmailDomainManager::class)->name('allowEmailDomains');
    });

    Route::prefix('communication')->name('communication.')->group(function () {
        Route::get('/', [CommunicationController::class, 'index'])->name('index');

        Route::get('/send', [CommunicationController::class, 'sendMessage'])
            ->name('send');

        Route::get('/filter-profiles', App\Livewire\Communication\FilterProfiles::class)
            ->name('filter-profiles');

        Route::get('/history', [CommunicationController::class, 'history'])
            ->name('history');

        Route::get('/settings', [CommunicationController::class, 'settings'])
            ->name('settings');
    });
});

Route::middleware(['auth', 'verified'])->prefix('relationships')->name('relationships.')->group(function () {
    Route::get('/', [RelationshipController::class, 'index'])->name('index');
    Route::get('/network-analysis', [RelationshipController::class, 'getNetworkAnalysis'])->name('network-analysis');

    Route::get('/personal', [RelationshipController::class, 'personalRelationships'])->name('personal');
    Route::post('/personal', [RelationshipController::class, 'createManualRelationship'])->name('personal.create');
    Route::post('/personal/{relationship}/verify', [RelationshipController::class, 'verifyPersonalRelationship'])->name('personal.verify');
    Route::post('/personal/{relationship}/reject', [RelationshipController::class, 'rejectPersonalRelationship'])->name('personal.reject');

    Route::get('/cross-org', [RelationshipController::class, 'crossOrgRelationships'])->name('cross-org');
    Route::post('/cross-org/{relationship}/verify', [RelationshipController::class, 'verifyCrossOrgRelationship'])->name('cross-org.verify');

    Route::get('/person/{person}/network', [RelationshipController::class, 'personNetwork'])->name('person.network');

    Route::post('/discover', [RelationshipController::class, 'runDiscovery'])->name('discover');
    Route::get('/export', [RelationshipController::class, 'exportRelationships'])->name('export');
});

Route::middleware(['auth:sanctum', 'verified'])->prefix('api/relationships')->name('api.relationships.')->group(function () {
    Route::get('/stats', [RelationshipController::class, 'getRelationshipStats'])->name('stats');
    Route::get('/pending', [RelationshipController::class, 'getPendingVerifications'])->name('pending');
    Route::get('/network-data/{person}', [RelationshipController::class, 'getPersonNetworkData'])->name('network-data');
});

Route::post('/webhooks/africastalking/delivery-reports', [SMSWebhookController::class, 'handleDeliveryReport'])
    ->name('sms.delivery.webhook');

Route::post('/africastalking/callback', [AfricasTalkingCallbackController::class, 'handle'])
    ->name('africastalking.callback');

Route::get('/email/verify/{id}/{hash}', function (CustomVerifyEmailRequest $request) {
    $user = User::findOrFail($request->route('id'));

    if ($user->hasVerifiedEmail()) {
        return redirect()->route('dashboard', ['verified' => 1]);
    }

    if ($user->markEmailAsVerified()) {
        event(new Verified($user));
    }

    return redirect()->route('dashboard', ['verified' => 1]);
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
