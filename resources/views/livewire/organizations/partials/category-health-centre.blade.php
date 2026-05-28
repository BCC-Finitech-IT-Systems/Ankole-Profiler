{{-- Health Centre-Specific Details --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Health Centre Level <span class="text-red-500">*</span></span>
            </label>
            <select wire:model="categoryDetails.hc_level" class="select select-bordered">
                <option value="">Select Level</option>
                <option value="HC_II">Health Centre II</option>
                <option value="HC_III">Health Centre III</option>
                <option value="HC_IV">Health Centre IV</option>
                <option value="GENERAL">General Health Centre</option>
            </select>
            @error('categoryDetails.hc_level') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Ownership</span>
            </label>
            <select wire:model="categoryDetails.ownership" class="select select-bordered">
                <option value="">Select Ownership</option>
                <option value="GOVERNMENT">Government</option>
                <option value="PRIVATE">Private</option>
                <option value="FAITH_BASED">Faith-Based (PNFP)</option>
                <option value="NGO">NGO / Charitable</option>
            </select>
            @error('categoryDetails.ownership') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">In-Charge / Medical Officer</span>
            </label>
            <input type="text" wire:model="categoryDetails.in_charge" class="input input-bordered"
                   placeholder="Name of officer in charge">
            @error('categoryDetails.in_charge') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Catchment Population</span>
            </label>
            <input type="number" wire:model="categoryDetails.catchment_population" class="input input-bordered"
                   placeholder="Estimated population served" min="0">
            @error('categoryDetails.catchment_population') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Number of Staff</span>
            </label>
            <input type="number" wire:model="categoryDetails.staff_count" class="input input-bordered"
                   placeholder="Total health workers" min="0">
            @error('categoryDetails.staff_count') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Maternity Beds</span>
            </label>
            <input type="number" wire:model="categoryDetails.maternity_beds" class="input input-bordered"
                   placeholder="Number of maternity beds" min="0">
            @error('categoryDetails.maternity_beds') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="form-control">
        <label class="label">
            <span class="label-text font-medium">Key Services Offered</span>
        </label>
        <textarea wire:model="categoryDetails.services_offered" class="textarea textarea-bordered h-20"
                  placeholder="e.g. OPD, ANC, immunisation, maternity, HIV/AIDS, TB, family planning..."></textarea>
        @error('categoryDetails.services_offered') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>
</div>
