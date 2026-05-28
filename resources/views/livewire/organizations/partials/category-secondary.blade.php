{{-- Secondary School-Specific Details --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">School Ownership <span class="text-red-500">*</span></span>
            </label>
            <select wire:model="categoryDetails.ownership" class="select select-bordered">
                <option value="">Select Ownership</option>
                <option value="GOVERNMENT">Government / Public</option>
                <option value="PRIVATE">Private</option>
                <option value="FAITH_BASED">Faith-Based</option>
                <option value="COMMUNITY">Community</option>
            </select>
            @error('categoryDetails.ownership') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Levels Offered</span>
            </label>
            <select wire:model="categoryDetails.levels" class="select select-bordered">
                <option value="">Select Levels</option>
                <option value="O_LEVEL">O-Level Only (S1–S4)</option>
                <option value="A_LEVEL">A-Level Only (S5–S6)</option>
                <option value="BOTH">O-Level & A-Level (S1–S6)</option>
            </select>
            @error('categoryDetails.levels') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Headteacher / Principal</span>
            </label>
            <input type="text" wire:model="categoryDetails.headteacher" class="input input-bordered"
                   placeholder="Name of headteacher / principal">
            @error('categoryDetails.headteacher') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Student Capacity</span>
            </label>
            <input type="number" wire:model="categoryDetails.student_capacity" class="input input-bordered"
                   placeholder="Maximum enrollment" min="1">
            @error('categoryDetails.student_capacity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Current Enrollment</span>
            </label>
            <input type="number" wire:model="categoryDetails.current_enrollment" class="input input-bordered"
                   placeholder="Current number of students" min="0">
            @error('categoryDetails.current_enrollment') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Number of Teachers</span>
            </label>
            <input type="number" wire:model="categoryDetails.number_of_teachers" class="input input-bordered"
                   placeholder="Total teaching staff" min="0">
            @error('categoryDetails.number_of_teachers') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="form-control">
        <label class="label">
            <span class="label-text font-medium">Subjects / Academic Programmes</span>
        </label>
        <textarea wire:model="categoryDetails.subjects" class="textarea textarea-bordered h-20"
                  placeholder="e.g. Sciences, Arts, Commerce; A-Level combinations offered..."></textarea>
        @error('categoryDetails.subjects') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="form-control">
        <label class="label">
            <span class="label-text font-medium">Facilities & Extra-Curricular</span>
        </label>
        <textarea wire:model="categoryDetails.facilities" class="textarea textarea-bordered h-20"
                  placeholder="e.g. science labs, library, computer lab, sports, music, debating club..."></textarea>
        @error('categoryDetails.facilities') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>
</div>
