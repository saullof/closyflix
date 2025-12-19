<picture>
    <source srcset="{{ Str::replaceLast('.', '.webp', $src) }}" type="image/webp">
    <img src="{{ $src }}" alt="{{ $alt ?? '' }}" {{ $attributes }}>
</picture>
