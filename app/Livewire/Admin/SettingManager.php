<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class SettingManager extends Component
{
    use WithPagination;

    public $key;
    public $value;
    public $label;
    public $editing = false;
    public $settingId;

    public function mount(): void
    {
        Gate::authorize('manage-settings');
    }

    protected function rules(): array
    {
        return [
            'key' => 'required|string|max:191|unique:settings,key' . ($this->settingId ? ',' . $this->settingId : ''),
            'value' => 'nullable|string',
            'label' => 'nullable|string|max:191',
        ];
    }

    public function create()
    {
        Gate::authorize('manage-settings');
        $this->validate();

        Setting::create([
            'key' => $this->key,
            'value' => $this->value,
            'label' => $this->label,
        ]);

        session()->flash('success', 'Setting created successfully.');
        $this->resetFields();
    }

    public function edit($id)
    {
        $setting = Setting::findOrFail($id);
        $this->editing = true;
        $this->settingId = $setting->id;
        $this->key = $setting->key;
        $this->value = $setting->value;
        $this->label = $setting->label;
    }

    public function update()
    {
        Gate::authorize('manage-settings');
        $this->validate();

        $setting = Setting::findOrFail($this->settingId);
        $setting->update([
            'key' => $this->key,
            'value' => $this->value,
            'label' => $this->label,
        ]);

        session()->flash('success', 'Setting updated successfully.');
        $this->resetFields();
    }

    public function delete($id)
    {
        Gate::authorize('manage-settings');
        Setting::findOrFail($id)->delete();
        session()->flash('success', 'Setting deleted successfully.');
    }

    private function resetFields()
    {
        $this->key = '';
        $this->value = '';
        $this->label = '';
        $this->editing = false;
        $this->settingId = null;
    }

    public function render()
    {
        return view('livewire.admin.setting-manager', [
            'settings' => Setting::orderBy('key')->paginate(10),
        ]);
    }
}
