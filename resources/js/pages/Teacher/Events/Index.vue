<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
    events: { type: Array, required: true },
})
</script>

<template>
    <Head :title="t('nav.events')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.events') }}</h1>
        <div class="mt-6 space-y-3">
            <article v-for="event in events" :key="event.id" class="card flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">{{ event.title }}</h2>
                    <p class="text-sm text-[#44546F]">{{ event.event_date }} · {{ event.location || t('common.no_location') }} · {{ t('common.participants_count', { count: event.participants_count }) }}</p>
                </div>
                <Link class="btn" :href="`/teacher/events/${event.id}`">{{ t('actions.roles') }}</Link>
            </article>
            <p v-if="events.length === 0" class="text-sm text-[#626F86]">{{ t('teacher.no_events') }}</p>
        </div>
    </AppLayout>
</template>
