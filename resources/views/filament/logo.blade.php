<div class="flex items-center gap-3 px-4 py-2" x-data>
    <div class="flex-shrink-0">
        <svg width="32" height="32" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8">
            <path d="M32 4L6 14V30C6 45.5 17.2 59.8 32 63.4C46.8 59.8 58 45.5 58 30V14L32 4Z" fill="#4e73df" />
            <path d="M32 4L6 14V30C6 45.5 17.2 59.8 32 63.4V4Z" fill="#2e59d9" />
            <path d="M43.3 22.6L29.2 36.8L20.7 28.3L16.5 32.5L29.2 45.2L47.5 26.8L43.3 22.6Z" fill="white" />
        </svg>
    </div>
    <span x-show="$store.sidebar.isOpen" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0"
        class="text-xl font-black tracking-wider text-white" style="font-family: 'Nunito', sans-serif;">
        ADASSOFT
    </span>
</div>