<?php

namespace App\Livewire\Communication;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Person;
use App\Models\Organization;
use App\Models\CommunicationFilterProfile;
use App\Models\CommunicationTemplate;
use App\Services\Communication\CommunicationManager;
use App\Services\PersonFilterService;
use App\Traits\HandlesSweetAlerts;
use App\Contracts\Communication\CommunicationMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Helpers\OrganizationHelperNew as OrganizationHelper;

class SendMessage extends Component
{
    use HandlesSweetAlerts;
    use WithPagination;

    // Step management
    public $currentStep = 1;
    public $totalSteps = 3;
    public $step_names = [
        'Select Recipients',
        'Choose Channels',
        'Compose Message'
    ];

    // Step 1: Recipient Selection
    public $selectionMode = 'search'; // 'search', 'all', 'filter_profile'
    public $person_search = '';
    public $search_results = [];
    public $selected_persons = [];
    public $filter_preview_count = 0;

    // Filter Profile Selection
    public $selected_filter_profile_id = null;
    public $available_filter_profiles = [];
    public $filter_profile_preview_count = 0;

    // Step 2: Channel Selection
    public $selected_channels = [];
    public $available_channels = ['email', 'sms', 'whatsapp'];

    // Step 3: Message Composition
    public $subject = '';
    public $message_content = '';
    public $selected_template_id = null;
    public $available_templates = [];

    // Sending Process Variables
    public $estimated_recipients = 0;
    public $show_progress_modal = false;
    public $sending_status = 'idle'; // 'idle', 'sending', 'completed', 'failed'
    public $sending_progress = 0;
    public $total_messages = 0;
    public $sent_messages = 0;
    public $failed_messages = 0;
    public $current_recipient = '';
    public $current_channel = '';
    public $progress_details = [];

    // Services
    protected $communicationManager;
    protected $personFilterService;

    public function boot()
    {
        $this->communicationManager = app(CommunicationManager::class);
        $this->personFilterService = app(PersonFilterService::class);
    }

    /**
     * Check if the current user is a Super Admin
     */
    protected function isSuperAdmin(): bool
    {
        $user = Auth::user();
        return $user && method_exists($user, 'hasRole') && $user->hasRole('Super Admin');
    }

    public function mount()
    {
        $this->loadAvailableTemplates();
        $this->loadAvailableFilterProfiles();
        $this->updateFilterPreviewCount();
        $this->checkChannelAvailability();

        // Check if a filter profile should be preselected
        if (session()->has('preselect_filter_profile')) {
            $profileId = session('preselect_filter_profile');

            // Verify the profile exists and is accessible to the user
            $profile = collect($this->available_filter_profiles)
                ->firstWhere('id', $profileId);

            if ($profile) {
                $this->selectionMode = 'filter_profile';
                $this->selectFilterProfile($profileId);
            }

            // Clear the session variable
            session()->forget('preselect_filter_profile');
        }
    }

    /**
     * Check availability of communication channels
     */
    public function checkChannelAvailability()
    {
        // This will be used in the view to show channel status
    }

    /**
     * Check if SMS channel is available
     */
    public function isSmsChannelAvailable(): bool
    {
        try {
            return $this->communicationManager->isChannelAvailable('sms');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if email channel is available
     */
    public function isEmailChannelAvailable(): bool
    {
        try {
            return $this->communicationManager->isChannelAvailable('email');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if WhatsApp channel is available
     */
    public function isWhatsAppChannelAvailable(): bool
    {
        try {
            return $this->communicationManager->isChannelAvailable('whatsapp');
        } catch (\Exception $e) {
            return false;
        }
    }

    // Step Navigation
    public function nextStep()
    {
        if ($this->validateCurrentStep()) {
            if ($this->currentStep < $this->totalSteps) {
                $this->currentStep++;
                $this->updateEstimatedRecipients();
            }
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    protected function validateCurrentStep()
    {
        switch ($this->currentStep) {
            case 1:
                if ($this->selectionMode === 'search' && empty($this->selected_persons)) {
                    session()->flash('error', 'Please select at least one person or choose a different selection mode.');
                    return false;
                }
                if ($this->selectionMode === 'filter_profile' && empty($this->selected_filter_profile_id)) {
                    session()->flash('error', 'Please select a filter profile.');
                    return false;
                }
                return true;
            case 2:
                if (empty($this->selected_channels)) {
                    session()->flash('error', 'Please select at least one communication channel.');
                    return false;
                }
                return true;
            case 3:
                if (empty($this->message_content)) {
                    session()->flash('error', 'Please enter a message.');
                    return false;
                }
                if (in_array('email', $this->selected_channels) && empty($this->subject)) {
                    session()->flash('error', 'Please enter an email subject.');
                    return false;
                }
                return true;
            default:
                return true;
        }
    }

    // Step 1: Recipient Selection Methods
    public function setSelectionMode($mode)
    {
        $this->selectionMode = $mode;
        $this->selected_persons = [];
        $this->person_search = '';
        $this->search_results = [];
        $this->selected_filter_profile_id = null;
        $this->filter_profile_preview_count = 0;
        $this->updateFilterPreviewCount();
    }

    public function selectFilterProfile($profileId)
    {
        $this->selected_filter_profile_id = $profileId;
        $this->updateFilterProfilePreviewCount();
    }

    protected function updateFilterProfilePreviewCount()
    {
        if (!$this->selected_filter_profile_id) {
            $this->filter_profile_preview_count = 0;
            return;
        }

        try {
            $profile = collect($this->available_filter_profiles)
                ->firstWhere('id', $this->selected_filter_profile_id);

            if ($profile) {
                $this->filter_profile_preview_count = $profile['estimated_count'] ?? 0;
            } else {
                $this->filter_profile_preview_count = 0;
            }
        } catch (Exception $e) {
            $this->filter_profile_preview_count = 0;
        }
    }

    public function updatedPersonSearch()
    {
        if (strlen($this->person_search) >= 2) {
            $this->searchPersons();
        } else {
            $this->search_results = [];
        }
    }

    protected function searchPersons()
    {
        try {
            logger()->info('Searching persons with search term: ' . $this->person_search);

            $organization = OrganizationHelper::getCurrentOrganization();

            if (!$organization) {
                logger()->warning('No organization associated with the user during person search.');
                session()->flash('error', 'You are not associated with any organization.');
                $this->search_results = [];
                return;
            }

            logger()->info('Organization found for search: ' . $organization->id . ' - ' . $organization->name);

            // Build the base query
            $query = Person::query();

            // Super Admin can search across all organizations
            if ($this->isSuperAdmin()) {
                $query->whereHas('affiliations');
            } else {
                $query->whereHas('affiliations', function ($q) use ($organization) {
                    $q->where('organization_id', $organization->id);
                });
            }

            $persons = $query->where(function ($query) {
                $query->where('given_name', 'like', '%' . $this->person_search . '%')
                    ->orWhere('family_name', 'like', '%' . $this->person_search . '%');
            })
                ->with(['emailAddresses', 'phones', 'affiliations.Organization'])
                ->limit(15) // Increased limit for Super Admin who might see more results
                ->get();

            $this->search_results = $persons->map(function ($person) {
                $primaryAffiliation = $person->affiliations->first();
                return [
                    'id' => $person->id,
                    'given_name' => $person->given_name,
                    'family_name' => $person->family_name,
                    'email_addresses' => $person->emailAddresses->toArray(),
                    'phones' => $person->phones->toArray(),
                    'affiliations' => $person->affiliations->toArray(),
                    'organization_name' => $primaryAffiliation ? $primaryAffiliation->Organization->legal_name ?? $primaryAffiliation->Organization->name ?? 'Unknown Organization' : 'No Organization'
                ];
            })->toArray();
        } catch (Exception $e) {
            session()->flash('error', 'Error searching persons: ' . $e->getMessage());
            $this->search_results = [];
        }
    }

    public function togglePersonSelection($personId)
    {
        if (in_array($personId, $this->selected_persons)) {
            $this->selected_persons = array_filter($this->selected_persons, function ($id) use ($personId) {
                return $id != $personId;
            });
        } else {
            $this->selected_persons[] = $personId;
        }
        $this->selected_persons = array_values($this->selected_persons); // Reset array keys
    }

    protected function updateFilterPreviewCount()
    {
        try {
            // Super Admin can see all persons across all organizations
            if ($this->isSuperAdmin()) {
                $this->filter_preview_count = Person::whereHas('affiliations')->count();
            } else {
                // Other users see only their organization's persons
                $organization = OrganizationHelper::getCurrentOrganization();
                $this->filter_preview_count = Person::whereHas('affiliations', function ($query) use ($organization) {
                    $query->where('organization_id', $organization->id);
                })->count();
            }
        } catch (Exception $e) {
            $this->filter_preview_count = 0;
        }
    }

    // Step 2: Channel Selection Methods
    public function toggleChannel($channel)
    {
        if (in_array($channel, $this->selected_channels)) {
            $this->selected_channels = array_filter($this->selected_channels, function ($ch) use ($channel) {
                return $ch !== $channel;
            });
        } else {
            $this->selected_channels[] = $channel;
        }
        $this->selected_channels = array_values($this->selected_channels); // Reset array keys
    }

    // Step 3: Message Composition Methods
    protected function loadAvailableFilterProfiles()
    {
        try {
            $organization = OrganizationHelper::getCurrentOrganization();

            $this->available_filter_profiles = CommunicationFilterProfile::accessibleBy(Auth::id(), $organization->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($profile) {
                    return [
                        'id' => $profile->id,
                        'name' => $profile->name,
                        'description' => $profile->description,
                        'estimated_count' => $profile->getEstimatedPersonCount(),
                        'usage_count' => $profile->usage_count,
                        'is_shared' => $profile->is_shared,
                        'filter_criteria' => $profile->filter_criteria
                    ];
                })
                ->toArray();
        } catch (Exception $e) {
            Log::error('Failed to load filter profiles for communication: ' . $e->getMessage());
            $this->available_filter_profiles = [];
        }
    }

    protected function loadAvailableTemplates()
    {
        try {
            $organization = OrganizationHelper::getCurrentOrganization();

            $this->available_templates = CommunicationTemplate::where(function ($query) use ($organization) {
                $query->where('organization_id', $organization->id)
                    ->orWhere('is_global', true);
            })
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->toArray();
        } catch (Exception $e) {
            $this->available_templates = [];
        }
    }

    public function loadTemplate($templateId)
    {
        try {
            $template = CommunicationTemplate::find($templateId);
            if ($template) {
                $this->subject = $template->subject ?? '';
                $this->message_content = $template->content;
                session()->flash('success', 'Template loaded successfully.');
            }
        } catch (Exception $e) {
            session()->flash('error', 'Error loading template: ' . $e->getMessage());
        }
    }

    // Recipient Estimation & Sending Methods
    protected function updateEstimatedRecipients()
    {
        if ($this->selectionMode === 'all') {
            $this->estimated_recipients = $this->filter_preview_count;
        } elseif ($this->selectionMode === 'filter_profile') {
            $this->estimated_recipients = $this->filter_profile_preview_count;
        } else {
            $this->estimated_recipients = count($this->selected_persons);
        }
    }



    public function sendMessage()
    {
        try {
            if (!$this->validateAllSteps()) {
                return;
            }

            $this->initializeProgressTracking();
            $recipients = $this->getRecipients();

            if (empty($recipients)) {
                throw new Exception('No valid recipients found.');
            }

            $this->total_messages = count($recipients); // Only set once
            $this->sent_messages = 0;
            $this->failed_messages = 0;
            $this->show_progress_modal = true;
            $this->sending_status = 'sending';

            foreach ($recipients as $recipient) {
                $this->current_channel = ucfirst($recipient['channel']);
                $this->current_recipient = $recipient['name'];
                $this->updateProgress();

                try {
                    $this->sendSingleMessage($recipient, $recipient['channel']);
                    $this->sent_messages++;

                    $this->progress_details[] = [
                        'recipient' => $recipient['name'],
                        'channel' => $recipient['channel'],
                        'status' => 'success',
                        'time' => now()->format('H:i:s')
                    ];
                } catch (Exception $e) {
                    $this->failed_messages++;

                    $this->progress_details[] = [
                        'recipient' => $recipient['name'],
                        'channel' => $recipient['channel'],
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                        'time' => now()->format('H:i:s')
                    ];

                    logger()->error("Failed to send message to {$recipient['contact']} via {$recipient['channel']}: " . $e->getMessage());
                }

                usleep(200000);
            }

            $this->completeProgressTracking();
        } catch (Exception $e) {
            $this->sending_status = 'failed';
            session()->flash('error', 'Failed to send messages: ' . $e->getMessage());
        }
    }

    protected function initializeProgressTracking()
    {
        $this->sending_progress = 0;
        $this->sent_messages = 0;
        $this->failed_messages = 0;
        $this->current_recipient = '';
        $this->current_channel = '';
        $this->progress_details = [];
    }

    protected function updateProgress()
    {
        $completed = $this->sent_messages + $this->failed_messages;
        $this->sending_progress = $this->total_messages > 0 ?
            round(($completed / $this->total_messages) * 100) : 0;
    }

    protected function completeProgressTracking()
    {
        $this->sending_status = 'completed';
        $this->sending_progress = 100;
        $this->current_recipient = '';
        $this->current_channel = '';

        $successCount = $this->sent_messages;
        $failureCount = $this->failed_messages;

        if ($failureCount > 0) {
            session()->flash('warning', "Sent {$successCount} message(s) successfully. {$failureCount} message(s) failed.");
        } else {
            session()->flash('success', "Successfully sent {$successCount} message(s).");
        }
    }

    protected function validateAllSteps()
    {
        // Validate recipients
        if ($this->selectionMode === 'search' && empty($this->selected_persons)) {
            session()->flash('error', 'Please select at least one recipient.');
            return false;
        }

        // Validate channels
        if (empty($this->selected_channels)) {
            session()->flash('error', 'Please select at least one communication channel.');
            return false;
        }

        // Validate message content
        if (empty($this->message_content)) {
            session()->flash('error', 'Please enter a message.');
            return false;
        }

        // Validate email subject if email is selected
        if (in_array('email', $this->selected_channels) && empty($this->subject)) {
            session()->flash('error', 'Please enter an email subject.');
            return false;
        }

        return true;
    }

    protected function getRecipients()
    {
        $recipients = [];

        try {
            if ($this->selectionMode === 'all') {
                // Super Admin can get all persons across organizations
                if ($this->isSuperAdmin()) {
                    $persons = Person::whereHas('affiliations')
                        ->with(['emailAddresses', 'phones'])
                        ->get();
                } else {
                    // Other users get persons from their organization only
                    $organization = OrganizationHelper::getCurrentOrganization();
                    $persons = Person::whereHas('affiliations', function ($query) use ($organization) {
                        $query->where('organization_id', $organization->id);
                    })
                        ->with(['emailAddresses', 'phones'])
                        ->get();
                }
            } elseif ($this->selectionMode === 'filter_profile') {
                // Use filter profile to get persons
                $profile = CommunicationFilterProfile::find($this->selected_filter_profile_id);
                if ($profile) {
                    $persons = $profile->getFilteredPersons();

                    // Mark profile as used
                    $profile->markAsUsed();
                } else {
                    throw new Exception('Selected filter profile not found');
                }
            } else {
                // Get selected persons (search mode) - ensure they belong to current organization
                $query = Person::whereIn('id', $this->selected_persons)
                    ->with(['emailAddresses', 'phones']);

                // Apply organization constraint (except for Super Admin)
                if (!$this->isSuperAdmin()) {
                    $organization = OrganizationHelper::getCurrentOrganization();
                    $query->whereHas('affiliations', function ($q) use ($organization) {
                        $q->where('organization_id', $organization->id);
                    });
                }

                $persons = $query->get();
            }

            foreach ($persons as $person) {
                // Add recipient based on selected channels
                foreach ($this->selected_channels as $channel) {
                    $contact = $this->getPersonContact($person, $channel);
                    if ($contact) {
                        $recipients[] = [
                            'person_id' => $person->id,
                            'name' => $person->given_name . ' ' . $person->family_name,
                            'contact' => $contact,
                            'channel' => $channel
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('Error getting recipients: ' . $e->getMessage());
        }

        return $recipients;
    }

    protected function getPersonContact($person, $channel)
    {
        switch ($channel) {
            case 'email':
                return $person->emailAddresses->first()?->email;
            case 'sms':
            case 'whatsapp':
                return $person->phones->first()?->number;
            default:
                return null;
        }
    }

    protected function sendSingleMessage($recipient, $channel)
    {
        // Create proper CommunicationMessage object based on channel
        $options = ['recipient_name' => $recipient['name']];

        $message = match ($channel) {
            'email' => CommunicationMessage::email(
                recipient: $recipient['contact'],
                content: $this->message_content,
                subject: $this->subject ?: 'Message from ' . config('app.name'),
                options: $options
            ),
            'sms' => CommunicationMessage::sms(
                recipient: $recipient['contact'],
                content: $this->message_content,
                options: $options
            ),
            'whatsapp' => CommunicationMessage::whatsapp(
                recipient: $recipient['contact'],
                content: $this->message_content,
                options: $options
            ),
            default => throw new Exception("Unsupported channel: {$channel}")
        };

        // Send through communication manager
        $this->communicationManager->send($message);
    }

    public function resetComponent()
    {
        $this->currentStep = 1;
        $this->selectionMode = 'search';
        $this->person_search = '';
        $this->search_results = [];
        $this->selected_persons = [];
        $this->selected_channels = [];
        $this->subject = '';
        $this->message_content = '';
        $this->selected_template_id = null;
        $this->estimated_recipients = 0;
        $this->show_progress_modal = false;
        $this->sending_status = 'idle';
        $this->sending_progress = 0;
        $this->total_messages = 0;
        $this->sent_messages = 0;
        $this->failed_messages = 0;
        $this->current_recipient = '';
        $this->current_channel = '';
        $this->progress_details = [];

        $this->updateFilterPreviewCount();
        session()->flash('success', 'Form reset successfully.');
    }

    public function closeProgressModal()
    {
        $this->show_progress_modal = false;
    }

    public function closeModals()
    {
        $this->show_progress_modal = false;
    }

    public function render()
    {
        return view('livewire.communication.send-message');
    }
}
