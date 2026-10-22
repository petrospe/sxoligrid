<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
    suggestions: { type: Array, required: true },
    targets: { type: Array, required: true },
    statuses: { type: Array, required: true },
})

const form = useForm({
    target_user_id: '',
    title: t('flex.title'),
    content: '',
})

function submit() {
    form.post('/admin/flex-suggestions', { onSuccess: () => form.reset('content') })
}

function scan() {
    router.post('/admin/flex-suggestions/scan')
}

function changeStatus(suggestion, status) {
    router.patch(`/admin/flex-suggestions/${suggestion.id}`, { status })
}
</script>

<template>
    <Head :title="t('nav.suggestions')" />
    <AppLayout>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">{{ t('admin.suggestions_title') }}</h1>
            <button class="btn" type="button" @click="scan">{{ t('actions.scan') }}</button>
        </div>
        <p class="mt-2 max-w-2xl text-sm text-[#44546F]">{{ t('admin.suggestions_hint') }}</p>
        <form class="card mt-6 grid gap-3" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.target') }}
                <select v-model="form.target_user_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="target in targets" :key="target.id" :value="target.id">{{ target.name }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.title') }}<input v-model="form.title" class="field mt-1" required></label>
            <label class="text-sm">{{ t('fields.content') }}<textarea v-model="form.content" class="field mt-1" rows="3" required></textarea></label>
            <button class="btn w-fit" :disabled="form.processing">{{ t('actions.record') }}</button>
        </form>
        <div class="mt-6 space-y-3">
            <article v-for="suggestion in suggestions" :key="suggestion.id" class="card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">{{ suggestion.title }}</h2>
                        <p class="text-sm text-[#626F86]">{{ t('common.to_person', { name: suggestion.target }) }}</p>
                        <p class="mt-2">{{ suggestion.content }}</p>
                    </div>
                    <select class="field w-auto" :value="suggestion.status" @change="changeStatus(suggestion, $event.target.value)">
                        <option v-for="status in statuses" :key="status" :value="status">{{ t('suggestion_statuses.' + status) }}</option>
                    </select>
                </div>
            </article>
        </div>
    </AppLayout>
</template>
