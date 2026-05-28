{{-- Clinic-Specific Details --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Clinic Type <span class="text-red-500">*</span></span>
            </label>
            <select wire:model="categoryDetails.clinic_type" class="select select-bordered">
                <option value="">Select Clinic Type</option>
                <option value="GENERAL">General Clinic</option>
                <option value="DENTAL">Dental Clinic</option>
                <option value="EYE">Eye / Optical Clinic</option>
                <option value="MATERNAL">Maternal & Child Health</option>
                <option value="SPECIALIST">Specialist Clinic</option>
                <option value="COMMUNITY">Community Clinic</option>
                <option value="PRIVATE">Private Clinic</option>
            </select>
            @error('categoryDetails.clinic_type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Ownership</span>
            </label>
            <select wire:model="categoryDetails.ownership" class="select select-bordered">
                <option value="">Select Ownership</option>
                <option value="PUBLIC">Government / Public</option>
                <option value="PRIVATE">Private</option>
                <option value="FAITH_BASED">Faith-Based</option>
                <option value="NGO">NGO / Charitable</option>
            </select>
            @error('categoryDetails.ownership') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Medical Officer in Charge</span>
            </label>
            <input type="text" wire:model="categoryDetails.medical_officer" class="input input-bordered"
                   placeholder="Dr. name">
            @error('categoryDetails.medical_officer') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Daily Patient Capacity</span>
            </label>
            <input type="number" wire:model="categoryDetails.daily_capacity" class="input input-bordered"
                   placeholder="Average patients per day" min="0">
            @error('categoryDetails.daily_capacity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Number of Clinical Staff</span>
            </label>
            <input type="number" wire:model="categoryDetails.clinical_staff" class="input input-bordered"
                   placeholder="Doctors, nurses, clinical officers" min="0">
            @error('categoryDetails.clinical_staff') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Operating Hours</span>
            </label>
            <input type="text" wire:model="categoryDetails.operating_hours" class="input input-bordered"
                   placeholder="e.g. Mon–Fri 8am–5pm, Sat 8am–1pm">
            @error('categoryDetails.operating_hours') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="form-control">
        <label class="label">
            <span class="label-text font-medium">Services Offered</span>
        </label>
        <textarea wire:model="categoryDetails.services_offered" class="textarea textarea-bordered h-20"
                  placeholder="e.g. General consultations, immunisation, family planning, ANC, laboratory..."></textarea>
        @error('categoryDetails.services_offered') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>
</div>
