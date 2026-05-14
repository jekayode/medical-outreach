@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'min-h-11 text-base border-gray-300 focus:border-brand-primary focus:ring-brand-primary rounded-md shadow-sm sm:text-sm']) }}>
