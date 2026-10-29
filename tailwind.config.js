const tokenColors = prefix => Object.fromEntries(
    [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].map(
        shade => [shade, `rgb(var(--tm-${prefix}-${shade}) / <alpha-value>)`],
    ),
);

export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'sans-serif'],
            },
            colors: {
                brand: tokenColors('brand'),
                slate: tokenColors('neutral'),
            },
        },
    },
    plugins: [],
};
