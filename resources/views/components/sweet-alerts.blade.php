<!-- resources/views/components/sweet-alerts.blade.php -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to show SweetAlert
    function showSweetAlert(data) {
        const config = {
            title: data.title || 'Notification',
            text: data.message || '',
            icon: data.type || 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6',
            timer: data.timer || null,
            timerProgressBar: data.timer ? true : false,
            showConfirmButton: !data.timer,
        };

        // Customize colors based on type
        switch (data.type) {
            case 'success':
                config.confirmButtonColor = '#10b981';
                config.timer = config.timer || 3000;
                break;
            case 'error':
                config.confirmButtonColor = '#ef4444';
                break;
            case 'warning':
                config.confirmButtonColor = '#f59e0b';
                break;
            case 'info':
                config.confirmButtonColor = '#3b82f6';
                config.timer = config.timer || 4000;
                break;
        }

        Swal.fire(config);
    }
    window.showSweetAlert = showSweetAlert;

    // Check for Laravel session flash messages
    @if(session('success'))
        showSweetAlert({!! json_encode(session('success')) !!});
    @endif

    @if(session('error'))
        showSweetAlert({!! json_encode(session('error')) !!});
    @endif

    @if(session('warning'))
        showSweetAlert({!! json_encode(session('warning')) !!});
    @endif

    @if(session('info'))
        showSweetAlert({!! json_encode(session('info')) !!});
    @endif

    // Listen for Livewire dispatched alerts
    window.addEventListener('sweet-alert', function(event) {
        showSweetAlert(event.detail);
    });

    // Global error handler for AJAX requests
    window.addEventListener('error', function(event) {
        if (event.error && event.error.response) {
            const response = event.error.response;
            if (response.status >= 400) {
                showSweetAlert({
                    type: 'error',
                    title: 'Request Failed',
                    message: response.data?.message || 'An error occurred while processing your request.'
                });
            }
        }
    });

    // Livewire error handler
    document.addEventListener('livewire:init', () => {
        Livewire.on('livewire:request', () => {
            // Optional: Show loading indicator
        });

        Livewire.on('livewire:finished', () => {
            // Optional: Hide loading indicator
        });

        Livewire.on('livewire:error', (error) => {
            showSweetAlert({
                type: 'error',
                title: 'Application Error',
                message: 'Something went wrong. Please refresh the page and try again.'
            });
        });
    });

    // Form validation error handler
    window.showValidationErrors = function(errors) {
        let errorMessages = [];
        
        if (typeof errors === 'object') {
            Object.keys(errors).forEach(key => {
                if (Array.isArray(errors[key])) {
                    errorMessages = errorMessages.concat(errors[key]);
                } else {
                    errorMessages.push(errors[key]);
                }
            });
        } else if (typeof errors === 'string') {
            errorMessages.push(errors);
        }

        if (errorMessages.length > 0) {
            showSweetAlert({
                type: 'warning',
                title: 'Validation Error',
                message: errorMessages.join('<br>'),
                html: true
            });
        }
    };

    // Success message helper
    window.showSuccessAlert = function(message, title = 'Success!') {
        showSweetAlert({
            type: 'success',
            title: title,
            message: message,
            timer: 3000
        });
    };

    // Error message helper
    window.showErrorAlert = function(message, title = 'Error!') {
        showSweetAlert({
            type: 'error',
            title: title,
            message: message
        });
    };

    // Warning message helper
    window.showWarningAlert = function(message, title = 'Warning!') {
        showSweetAlert({
            type: 'warning',
            title: title,
            message: message
        });
    };

    // Info message helper
    window.showInfoAlert = function(message, title = 'Information') {
        showSweetAlert({
            type: 'info',
            title: title,
            message: message,
            timer: 4000
        });
    };

    // Confirmation dialog helper
    window.showConfirmDialog = function(options) {
        const defaultOptions = {
            title: 'Are you sure?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, continue!',
            cancelButtonText: 'Cancel'
        };

        const config = { ...defaultOptions, ...options };

        return Swal.fire(config);
    };
});
</script>
<script>
// Livewire 3 components report outcomes with $this->dispatch('alert', [...])
// or $this->dispatch('swal', [...]); positional params arrive as [payload].
(function () {
    const payload = (params) => Array.isArray(params) ? params[0] : params;
    const register = () => {
        Livewire.on('alert', (params) => window.showSweetAlert(payload(params) || {}));
        Livewire.on('swal', (params) => Swal.fire(payload(params) || {}));
    };
    if (window.Livewire) {
        register();
    } else {
        document.addEventListener('livewire:init', register);
    }
})();
</script>

<style>
/* Custom SweetAlert styling to match your app theme */
.swal2-popup {
    font-family: 'Inter', sans-serif !important;
}

.swal2-title {
    font-weight: 600 !important;
}

.swal2-content {
    font-size: 14px !important;
}

.swal2-confirm {
    border-radius: 6px !important;
    font-weight: 500 !important;
    padding: 8px 20px !important;
}

.swal2-cancel {
    border-radius: 6px !important;
    font-weight: 500 !important;
    padding: 8px 20px !important;
}

/* Toast notifications for quick messages */
.swal2-toast {
    border-radius: 8px !important;
}
</style>