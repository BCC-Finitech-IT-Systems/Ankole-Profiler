{{-- Church-Specific Details --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Denomination <span class="text-red-500">*</span></span>
            </label>
            <select wire:model="categoryDetails.denomination" class="select select-bordered">
                <option value="">Select Denomination</option>
                <option value="CATHOLIC">Catholic</option>
                <option value="ANGLICAN">Anglican</option>
                <option value="PENTECOSTAL">Pentecostal</option>
                <option value="ORTHODOX">Orthodox</option>
                <option value="BAPTIST">Baptist</option>
                <option value="METHODIST">Methodist</option>
                <option value="PRESBYTERIAN">Presbyterian</option>
                <option value="LUTHERAN">Lutheran</option>
                <option value="ADVENTIST">Seventh Day Adventist</option>
                <option value="EVANGELICAL">Evangelical</option>
                <option value="OTHER">Other</option>
            </select>
            @error('categoryDetails.denomination') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Church Type</span>
            </label>
            <select wire:model="categoryDetails.church_type" class="select select-bordered">
                <option value="">Select Type</option>
                <option value="CATHEDRAL">Cathedral</option>
                <option value="PARISH">Parish Church</option>
                <option value="MISSION">Mission Station</option>
                <option value="CHAPEL">Chapel</option>
                <option value="OUTREACH">Outreach Point</option>
            </select>
            @error('categoryDetails.church_type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Senior Pastor / Priest Name</span>
            </label>
            <input type="text" wire:model="categoryDetails.priest_name" class="input input-bordered"
                   placeholder="Rev. / Pr. / Fr. name">
            @error('categoryDetails.priest_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Congregation Size</span>
            </label>
            <input type="number" wire:model="categoryDetails.congregation_size" class="input input-bordered"
                   placeholder="Approximate number of members" min="0">
            @error('categoryDetails.congregation_size') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Diocese / Archdiocese</span>
            </label>
            <input type="text" wire:model="categoryDetails.diocese" class="input input-bordered"
                   placeholder="e.g. Diocese of Ankole">
            @error('categoryDetails.diocese') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Year Established</span>
            </label>
            <input type="number" wire:model="categoryDetails.year_established" class="input input-bordered"
                   placeholder="e.g. 1965" min="1800" max="{{ date('Y') }}">
            @error('categoryDetails.year_established') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="form-control">
        <label class="label">
            <span class="label-text font-medium">Regular Services & Programmes</span>
        </label>
        <textarea wire:model="categoryDetails.services" class="textarea textarea-bordered h-20"
                  placeholder="e.g. Sunday Service 9am, Wednesday Bible Study, Youth Fellowship, Choir..."></textarea>
        @error('categoryDetails.services') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="form-control">
        <label class="label">
            <span class="label-text font-medium">Outreach & Community Programmes</span>
        </label>
        <textarea wire:model="categoryDetails.outreach" class="textarea textarea-bordered h-20"
                  placeholder="Describe community service, outreach, or social programmes run by the church"></textarea>
        @error('categoryDetails.outreach') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>
</div>
