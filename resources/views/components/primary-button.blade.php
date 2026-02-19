<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 btn-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-misdinar-primary focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
