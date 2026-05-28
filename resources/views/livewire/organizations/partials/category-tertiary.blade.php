{{-- Tertiary Institution-Specific Details --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Institution Type <span class="text-red-500">*</span></span>
            </label>
            <select wire:model="categoryDetails.institution_type" class="select select-bordered">
                <option value="">Select Type</option>
                <option value="UNIVERSITY">University</option>
                <option value="UNIVERSITY_COLLEGE">University College</option>
                <option value="COLLEGE">College of Education</option>
                <option value="TECHNICAL">Technical Institute</option>
                <option value="VOCATIONAL">Vocational Training Institute</option>
                <option value="NURSING">Nursing / Allied Health School</option>
                <option value="THEOLOGY">Theological Seminary / College</option>
                <option value="OTHER">Other Tertiary</option>
            </select>
            @error('categoryDetails.institution_type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Ownership</span>
            </label>
            <select wire:model="categoryDetails.ownership" class="select select-bordered">
                <option value="">Select Ownership</option>
                <option value="PUBLIC">Public / Government</option>
                <option value="PRIVATE">Private</option>
                <option value="FAITH_BASED">Faith-Based</option>
                <option value="COMMUNITY">Community</option>
            </select>
            @error('categoryDetails.ownership') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Vice Chancellor / Principal</span>
            </label>
            <input type="text" wire:model="categoryDetails.principal" class="input input-bordered"
                   placeholder="Name of head of institution">
            @error('categoryDetails.principal') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Student Enrolment</span>
            </label>
            <input type="number" wire:model="categoryDetails.student_capacity" class="input input-bordered"
                   placeholder="Total enrolled students" min="0">
            @error('categoryDetails.student_capacity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Academic Staff</span>
            </label>
            <input type="number" wire:model="categoryDetails.academic_staff" class="input input-bordered"
                   placeholder="Number of academic / teaching staff" min="0">
            @error('categoryDetails.academic_staff') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Accrediting Body</span>
            </label>
            <input type="text" wire:model="categoryDetails.accrediting_body" class="input input-bordered"
                   placeholder="e.g. NCHE, UBTEB, MoES">
            @error('categoryDetails.accrediting_body') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="form-control">
        <label class="label">
            <span class="label-text font-medium">Faculties / Departments / Programmes</span>
        </label>
        <textarea wire:model="categoryDetails.programmes" class="textarea textarea-bordered h-20"
                  placeholder="List the faculties, departments, or programmes offered"></textarea>
        @error('categoryDetails.programmes') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="form-control">
        <label class="label">
            <span class="label-text font-medium">Research & Community Engagement</span>
        </label>
        <textarea wire:model="categoryDetails.research" class="textarea textarea-bordered h-20"
                  placeholder="Ongoing research areas, community outreach or partnerships"></textarea>
        @error('categoryDetails.research') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>
</div>
