<script setup>
defineProps({
    name: { type: String, required: true },
})

const icons = {
    calendar: ['M7 3v3', 'M17 3v3', 'M4 9h16', 'M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z'],
    login: ['M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2', 'M14 12H3', 'M6 9l-3 3 3 3'],
    dashboard: ['M4 4h7v7H4z', 'M13 4h7v4h-7z', 'M13 10h7v10h-7z', 'M4 13h7v7H4z'],
    users: ['M16 19v-1a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v1', 'M10 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6z', 'M20 19v-1a3 3 0 0 0-2.2-2.9', 'M16 6.1a3 3 0 0 1 0 5.8'],
    book: ['M5 4h11a3 3 0 0 1 3 3v13H8a3 3 0 0 0-3 3V4z', 'M5 4a3 3 0 0 0-3 3v13', 'M9 8h6'],
    'user-plus': ['M15 19v-1a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v1', 'M9 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6z', 'M19 8v6', 'M16 11h6'],
    box: ['M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z', 'M12 12l8-4.5', 'M12 12v9', 'M12 12L4 7.5'],
    refresh: ['M20 12a8 8 0 0 1-13.5 5.8L4 16', 'M4 12a8 8 0 0 1 13.5-5.8L20 8', 'M4 16v-4h4', 'M20 8v4h-4'],
    card: ['M3 7h18v10H3z', 'M3 11h18', 'M7 15h4'],
    flag: ['M5 21V4', 'M5 4h11l-1.5 3L16 10H5'],
    user: ['M16 19v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1', 'M12 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z'],
    bulb: ['M9 18h6', 'M10 21h4', 'M12 3a6 6 0 0 0-3 11c.5.5 1 1.2 1 2h4c0-.8.5-1.5 1-2a6 6 0 0 0-3-11z'],
    chart: ['M4 19V5', 'M4 19h16', 'M8 16v-5', 'M12 16V8', 'M16 16v-3'],
    settings: ['M4 7h16', 'M9 4v6', 'M4 12h16', 'M15 9v6', 'M4 17h16', 'M8 14v6'],
}
</script>

<template>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4 w-4 shrink-0" aria-hidden="true">
        <path
            v-for="(path, index) in icons[name] ?? icons.dashboard"
            :key="index"
            stroke-linecap="round"
            stroke-linejoin="round"
            :d="path"
        />
    </svg>
</template>
