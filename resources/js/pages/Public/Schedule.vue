<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import WeeklyGrid from '@/Components/WeeklyGrid.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
    show_landing: { type: Boolean, required: true },
    landing: { type: Object, required: true },
    schedules: { type: Array, required: true },
})
</script>

<template>
    <Head :title="landing.title" />
    <AppLayout>
        <header v-if="show_landing" class="card mb-6">
            <img :src="landing.logo_url" :alt="landing.name" class="mx-auto max-h-72 w-auto">
            <p class="mt-5 text-sm font-medium text-[#0C66E4]">{{ landing.name }}</p>
            <h1 class="mt-1 text-2xl font-semibold">{{ landing.title }}</h1>
            <p class="mt-1 text-[#44546F]">{{ landing.subtitle }}</p>
            <p class="mt-4 max-w-3xl whitespace-pre-line">{{ landing.description }}</p>
            <p class="mt-3 max-w-3xl whitespace-pre-line text-[#44546F]">{{ landing.details }}</p>
        </header>
        <header class="mb-6">
            <h2 class="text-xl font-semibold">{{ t('schedule_page.heading') }}</h2>
            <p class="mt-1 max-w-2xl text-[#44546F]">
                {{ t('schedule_page.intro') }}
            </p>
        </header>
        <WeeklyGrid :schedules="schedules" />
    </AppLayout>
</template>
