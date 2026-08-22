import { ref, onMounted } from 'vue';

const isDark = ref(false);

export function useDarkMode() {
    const initDarkMode = () => {
        if (typeof window === 'undefined') {
return;
}

        const theme = localStorage.getItem('theme');

        if (
            theme === 'dark' ||
            (!theme &&
                window.matchMedia('(prefers-color-scheme: dark)').matches)
        ) {
            isDark.value = true;
            document.documentElement.classList.add('dark');
        } else {
            isDark.value = false;
            document.documentElement.classList.remove('dark');
        }
    };

    const toggleDarkMode = () => {
        isDark.value = !isDark.value;

        if (typeof window !== 'undefined') {
            if (isDark.value) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        }
    };

    const setDarkMode = (val: boolean) => {
        isDark.value = val;

        if (typeof window !== 'undefined') {
            if (val) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        }
    };

    onMounted(() => {
        initDarkMode();
    });

    return {
        isDark,
        toggleDarkMode,
        setDarkMode,
        initDarkMode,
    };
}
