<div class="p-0">
    <div class="text-center mb-4">
        <a href="{{ route('login') }}" class="btn btn-outline btn-primary">Back to Login</a>
    </div>
    <div class="mb-6 text-center">
        <h1 class="text-3xl font-bold text-gray-800">Welcome to Ankoler Profiler</h1>
        <p class="text-gray-600 mt-2">Please fill in the details below to create your account and get started.</p>
    </div>
    <div class="bg-gradient-to-r from-gray-50 to-blue-50  flex justify-center items-center">
        <div class="card w-full max-w-5xl bg-base-100 rounded-xl shadow-lg p-6 h-full flex flex-col justify-center">


            <x-alerts />

            @if ($errors->any())
                <div class="md:col-span-2 alert alert-error mb-6">
                    <h3 class="text-sm font-medium mb-2">Please correct the following
                        {{ $errors->count() > 1 ? 'errors' : 'error' }}:</h3>
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <!-- FORM -->
            <form wire:submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="selfRegForm" novalidate>
                @csrf
                {{-- Error Summary Section --}}
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">First Name*</label>
                    <input type="text" wire:model="form.given_name" class="input input-bordered input-sm w-full"
                        placeholder="Enter your first name" required minlength="2" maxlength="255"
                        oninvalid="this.setCustomValidity('First name is required')"
                        oninput="this.setCustomValidity('')">
                    @error('form.given_name')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">Last Name*</label>
                    <input type="text" wire:model="form.family_name" class="input input-bordered input-sm w-full"
                        placeholder="Enter your last name" required minlength="2" maxlength="255"
                        oninvalid="this.setCustomValidity('Last name is required')"
                        oninput="this.setCustomValidity('')">
                    @error('form.family_name')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">Gender*</label>
                    <select wire:model="form.gender" class="select select-bordered select-sm w-full" required
                        oninvalid="this.setCustomValidity('Gender is required')" oninput="this.setCustomValidity('')">
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    @error('form.gender')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">Date of Birth*</label>
                    <input type="date" wire:model="form.date_of_birth" class="input input-bordered input-sm w-full"
                        placeholder="Enter your Date of Birth" required
                        oninvalid="this.setCustomValidity('Date of birth is required')"
                        oninput="this.setCustomValidity('')">
                    @error('form.date_of_birth')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">Email Address*</label>
                    <input type="email" wire:model="form.email" class="input input-bordered input-sm w-full"
                        placeholder="Enter your Email Address" required
                        oninvalid="this.setCustomValidity('Valid email is required')"
                        oninput="this.setCustomValidity('')">
                    @error('form.email')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">Phone Number*</label>
                    <input type="number" wire:model="form.phone" class="input input-bordered input-sm w-full"
                        placeholder="Enter your Phone Number" required minlength="7" maxlength="20"
                        oninvalid="this.setCustomValidity('Phone number is required')"
                        oninput="this.setCustomValidity('')">
                    @error('form.phone')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">Country*</label>
                    <input type="text" wire:model="form.country" class="input input-bordered input-sm w-full"
                        placeholder="Enter your Country" required
                        oninvalid="this.setCustomValidity('Country is required')" oninput="this.setCustomValidity('')">
                    @error('form.country')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">City*</label>
                    <input type="text" wire:model="form.city" class="input input-bordered input-sm w-full"
                        placeholder="Enter your City" required oninvalid="this.setCustomValidity('City is required')"
                        oninput="this.setCustomValidity('')">
                    @error('form.city')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">Address*</label>
                    <input type="text" wire:model="form.address" class="input input-bordered input-sm w-full"
                        placeholder="Enter your Address" required
                        oninvalid="this.setCustomValidity('Address is required')" oninput="this.setCustomValidity('')">
                    @error('form.address')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">District*</label>
                    <input type="text" wire:model="form.district" class="input input-bordered input-sm w-full"
                        placeholder="Enter your District" required
                        oninvalid="this.setCustomValidity('District is required')" oninput="this.setCustomValidity('')">
                    @error('form.district')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">Department*</label>
                    <select wire:model="form.department_id" class="select select-bordered select-sm w-full" required
                        oninvalid="this.setCustomValidity('Department is required')"
                        oninput="this.setCustomValidity('')">
                        @if ($availableDepartments->isEmpty())
                            <option value="">No departments available</option>
                        @else
                            <option value="">Select Department</option>
                            @foreach ($availableDepartments as $dept)
                                <option value="{{ $dept->id }}" @if (old('form.department_id') == $dept->id) selected @endif>
                                    {{ $dept->name }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('form.department_id')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1 text-sm">Role Title*</label>
                    <input type="text" wire:model="form.role_title" class="input input-bordered input-sm w-full"
                        placeholder="Role Title" required oninvalid="this.setCustomValidity('Role title is required')"
                        oninput="this.setCustomValidity('')">
                    @error('form.role_title')
                        <span class="text-error text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div class="md:col-span-2 flex justify-center gap-4 mt-6">
                    <button type="submit" class="btn btn-success w-full">Submit Registration</button>
                </div>
            </form>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var nextBtn = document.getElementById('nextStepBtn');
                    var prevBtn = document.getElementById('previousStepBtn');
                    var nextSpinner = document.getElementById('nextSpinner');
                    var prevSpinner = document.getElementById('prevSpinner');
                    if (nextBtn) {
                        nextBtn.addEventListener('click', function(e) {
                            var form = document.getElementById('selfRegForm');
                            var valid = true;
                            Array.prototype.forEach.call(form.querySelectorAll('[required]'), function(input) {
                                if (input.offsetParent !== null && !input.checkValidity()) {
                                    input.reportValidity();
                                    valid = false;
                                }
                            });
                            if (valid) {
                                nextSpinner.classList.remove('hidden');
                                nextBtn.disabled = true;
                                if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                                    window.Livewire.dispatch('nextStep');
                                }
                            }
                        });
                    }
                    if (prevBtn) {
                        prevBtn.addEventListener('click', function(e) {
                            prevSpinner.classList.remove('hidden');
                            prevBtn.disabled = true;
                            if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                                window.Livewire.dispatch('previousStep');
                            }
                        });
                    }
                });
            </script>
        </div>
    </div>
</div>
