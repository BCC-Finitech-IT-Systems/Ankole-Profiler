<?php

namespace App\Livewire\Communication;

use App\Contracts\Communication\CommunicationMessage as CommunicationMessageDTO;
use App\Models\CommunicationMessage;
use App\Services\Communication\CommunicationManager;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class MessageHistory extends Component
{
    use WithPagination;

    public string $search = '';
    public string $channel_filter = '';
    public string $status_filter = '';
    public string $date_from = '';
    public string $date_to = '';
    public int $per_page = 20;
    public $editingMessage = null;


    public function deleteMessage(int $id): void
    {
        $message = CommunicationMessage::where('organization_id', $this->resolveOrganizationId())
            ->findOrFail($id);
        $message->delete();
        session()->flash('success', 'Message deleted successfully.');
        $this->resetPage();
    }

    public function editMessage(int $id): void
    {
        $this->editingMessage = CommunicationMessage::where('organization_id', $this->resolveOrganizationId())
            ->findOrFail($id);
    }

    /**
     * Send the same content to the same recipient again. This deliberately
     * creates a new history entry rather than mutating the original, so the
     * earlier attempt and its failure reason stay on the record.
     */
    public function resendMessage(int $id): void
    {
        $message = CommunicationMessage::where('organization_id', $this->resolveOrganizationId())
            ->findOrFail($id);

        if (blank($message->recipient_identifier) || blank($message->content)) {
            session()->flash('error', 'This message has no recipient or content to resend.');

            return;
        }

        $manager = app(CommunicationManager::class);

        if (!$manager->isChannelAvailable($message->channel)) {
            session()->flash('error', "The {$message->channel} channel is not configured, so this message cannot be resent.");

            return;
        }

        $result = $manager->send(new CommunicationMessageDTO(
            recipient: $message->recipient_identifier,
            content: $message->content,
            channel: $message->channel,
            subject: $message->subject,
        ));

        if ($result->success) {
            session()->flash('success', 'Message resent to ' . $message->recipient_identifier . '.');
        } else {
            session()->flash('error', 'Resend failed: ' . ($result->errorMessage ?? 'unknown error'));
        }

        $this->resetPage();
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'channel_filter' => ['except' => ''],
        'status_filter' => ['except' => ''],
        'date_from' => ['except' => ''],
        'date_to' => ['except' => ''],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedChannelFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->channel_filter = '';
        $this->status_filter = '';
        $this->date_from = '';
        $this->date_to = '';
        $this->resetPage();
    }

    protected function resolveOrganizationId(): ?int
    {
        $orgId = current_organization_id();
        if ($orgId) return $orgId;

        $person = Auth::user()->person ?? null;
        if ($person) {
            $affiliation = $person->affiliations()->first();
            if ($affiliation) return $affiliation->organization_id;
        }

        return null;
    }

    public function getMessages()
    {
        $organizationId = $this->resolveOrganizationId();

        $query = CommunicationMessage::query()
            ->with(['recipientPerson', 'sentByUser'])
            ->where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc');

        // Apply search
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('recipient_identifier', 'like', '%' . $this->search . '%')
                  ->orWhere('subject', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%')
                  ->orWhereHas('recipientPerson', function ($personQuery) {
                      $personQuery->whereRaw("CONCAT(given_name, ' ', family_name) LIKE ?", ['%' . $this->search . '%']);
                  });
            });
        }

        // Apply channel filter
        if (!empty($this->channel_filter)) {
            $query->where('channel', $this->channel_filter);
        }

        // Apply status filter
        if (!empty($this->status_filter)) {
            $query->where('status', $this->status_filter);
        }

        // Apply date filters
        if (!empty($this->date_from)) {
            $query->where('created_at', '>=', $this->date_from);
        }

        if (!empty($this->date_to)) {
            $query->where('created_at', '<=', $this->date_to . ' 23:59:59');
        }

        return $query->paginate($this->per_page);
    }

    public function getChannelStats(): array
    {
        $organizationId = $this->resolveOrganizationId();

        if (!$organizationId) {
            return [];
        }

        $communicationManager = app(CommunicationManager::class);
        return $communicationManager->getOrganizationStats($organizationId, 'month');
    }

    public function render()
    {
        return view('livewire.communication.message-history', [
            'messages' => $this->getMessages(),
            'stats' => $this->getChannelStats(),
            'available_channels' => ['email', 'sms', 'whatsapp'],
            'available_statuses' => [
                'pending' => 'Pending',
                'sent' => 'Sent',
                'delivered' => 'Delivered',
                'read' => 'Read',
                'failed' => 'Failed',
                'bounced' => 'Bounced',
                'rejected' => 'Rejected',
            ],
        ]);
    }
}
