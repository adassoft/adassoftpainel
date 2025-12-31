import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                // Definindo as cores do SB Admin 2 / Legacy
                primary: {
                    50: '#f0f3fd',
                    100: '#e1e7fa',
                    200: '#c3cef5',
                    300: '#a5b5f0',
                    400: '#879ceb',
                    500: '#4e73df',
                    600: '#4667c9',
                    700: '#3e5bb3',
                    800: '#364f9c',
                    900: '#2e4386',
                    950: '#2b3d4f', // Novo fundo de menu
                },
                success: {
                    DEFAULT: '#1cc88a',
                    500: '#1cc88a',
                },
                info: {
                    DEFAULT: '#36b9cc',
                    500: '#36b9cc',
                },
                warning: {
                    DEFAULT: '#f6c23e',
                    500: '#f6c23e',
                },
                danger: {
                    DEFAULT: '#e74a3b',
                    500: '#e74a3b',
                },
                secondary: {
                    DEFAULT: '#858796',
                    500: '#858796',
                }
            },
            fontFamily: {
                sans: ['Nunito', 'sans-serif'],
            },
        },
    },
}
