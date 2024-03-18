<div x-data="{ show: false, message: '', type: '' }" x-init="() => {
    Livewire.on('show-toast', (data) => {
        show = true;
        message = data[0].message;
        type = data[0].type;
        setTimeout(() => show = false, 5000);
    });
}" x-show="show" x-cloak
    :class="{
        'bg-success': type === 'success',
        'bg-info': type === 'info',
        'bg-warning': type === 'warning',
        'bg-error': type === 'error'
    }"
    class="absolute top-4 right-4 z-20 flex items-center w-full max-w-md p-4 text-white rounded-lg shadow" role="alert">
    <div class="ml-3 text-sm font-bold" x-text="message"></div>
    <button type="button" @click="show = false"
        class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8"
        aria-label="Close">
        <span class="sr-only">Close</span>
        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
        </svg>
    </button>
</div>
