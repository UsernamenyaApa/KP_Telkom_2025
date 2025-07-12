
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
    './vendor/livewire/flux-pro/stubs/**/*.blade.php',
    './vendor/livewire/flux/stubs/**/*.blade.php',
  ],
  theme: {
    extend: {
        backgroundImage: {
            'gradient-conic': 'conic-gradient(var(--tw-gradient-stops))',
        },
        fontFamily: {
            sans: ['Instrument Sans', 'ui-sans-serif', 'system-ui', 'sans-serif', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'],
        },
        colors: {
            zinc: {
                50: '#fafafa',
                100: '#f5f5f5',
                200: '#e5e5e5',
                300: '#d4d4d4',
                400: '#a3a3a3',
                500: '#737373',
                600: '#525252',
                700: '#404040',
                800: '#262626',
                900: '#171717',
                950: '#0a0a0a',
            },
            accent: 'var(--color-neutral-800)',
            'accent-content': 'var(--color-neutral-800)',
            'accent-foreground': 'var(--color-white)',
        }
    },
  },
  plugins: [],
  darkMode: 'class',
}
