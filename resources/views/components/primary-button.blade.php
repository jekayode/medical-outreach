<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center min-h-11 min-w-11 px-5 py-2.5 bg-brand-primary border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-wide hover:bg-brand-hover focus:bg-brand-hover active:bg-brand-active focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
