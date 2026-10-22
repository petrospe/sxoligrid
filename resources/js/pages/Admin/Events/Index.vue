<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
    events: { type: Array, required: true },
})

const form = useForm({
    title: '',
    description: '',
    event_date: '',
    location: '',
})

function submit() {
    form.post('/admin/events', { onSuccess: () => form.reset() })
}

function remove(event) {
    if (window.confirm(t('confirm.delete_event', { name: event.title }))) {
        router.delete(`/admin/events/${event.id}`)
    }
}
</script>

<template>
    <Head :title="t('nav.events')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.events') }}</h1>
        <form class="card mt-6 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.title') }}<input v-model="form.title" class="field mt-1" required></label>
            <label class="text-sm">{{ t('fields.date') }}<input v-model="form.event_date" class="field mt-1" type="datetime-local" required></label>
            <label class="text-sm md:col-span-2">{{ t('fields.description') }}<textarea v-model="form.description" class="field mt-1" rows="2"></textarea></label>
            <label class="text-sm">{{ t('fields.location') }}<input v-model="form.location" class="field mt-1"></label>
            <div class="md:col-span-2"><button class="btn" :disabled="form.processing">{{ t('actions.create') }}</button></div>
        </form>
        <div class="mt-6 grid gap-3">
            <article v-for="event in events" :key="event.id" class="card flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">{{ event.title }}</h2>
                    <p class="text-sm text-[#44546F]">{{ event.event_date.replace('T', ' ') }} · {{ event.location || t('common.no_location') }} · {{ t('common.participants_count', { count: event.participants_count }) }}</p>
                </div>
                <div class="space-x-2">
                    <Link class="btn" :href="`/admin/events/${event.id}`">{{ t('actions.participants') }}</Link>
                    <button class="btn-danger" type="button" @click="remove(event)">{{ t('actions.delete') }}</button>
                </div>
            </article>
            <p v-if="events.length === 0" class="text-sm text-[#626F86]">{{ t('teacher.no_events') }}</p>
        </div>
    </AppLayout>
</template>
