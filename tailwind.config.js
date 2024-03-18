import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './src/**/*.{html,js}',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                header: ['Source Serif Pro', 'serif'], // Use 'header' as the class for the header font
                body: ['Poppins', 'sans'], // Use 'body' as the class for the body font
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'primary': '#8c0000',
                'secondary': '#DC9400',
                'accent': '#FFE7AD',
                'background': '#FCF9F6',
                'info': '#2792ED',
                'success': '#27AE60',
                'warning': '#FFBE0F',
                'error': '#EB5757',
                'black': '#1D1D1D',
                'grey': '#6E6B7B',
                'white': '#FAFAFA',
            },
            maxHeight: {
                '128': '32rem',
                '148': '40rem',
            },
            minHeight:{
                '128': '32rem',
                '148': '40rem',
            },
            fontSize: {
                xs: '0.6rem',
              }
        },
    },
    plugins: [],
};



