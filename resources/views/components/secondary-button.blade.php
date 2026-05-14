<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center min-h-11 min-w-11 px-5 py-2.5 bg-white border border-gray-300 rounded-md font-semibold text-sm text-gray-700 uppercase tracking-wide shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
