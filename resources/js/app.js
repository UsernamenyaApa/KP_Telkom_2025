document.addEventListener('livewire:init', () => {
    // Ambil preferensi dari localStorage, default ke 'system' & 'light-blue'
    let theme = localStorage.getItem('theme') || 'system';
    let themeColor = localStorage.getItem('theme_color') || 'light-blue';

    // Fungsi untuk menerapkan tema
    const applyTheme = () => {
        const isDarkMode =
            theme === 'dark' ||
            (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        if (isDarkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Set warna tema
        document.documentElement.setAttribute('data-theme', themeColor);
    };

    // Dengarkan event custom "saved" dari Livewire
    window.addEventListener('saved', event => {
        const detail = event.detail[0] || event.detail;

        theme = detail.appearance;
        themeColor = detail.themeColor;

        localStorage.setItem('theme', theme);
        localStorage.setItem('theme_color', themeColor);

        applyTheme();
    });

    // Terapkan tema saat halaman pertama kali dimuat
    applyTheme();

    // Dengarkan perubahan system theme (light/dark)
    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', () => {
            if (theme === 'system') {
                applyTheme();
            }
        });
});
