<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
    suggestions: { type: Array, required: true },
})

function decide(suggestion, status) {
    router.patch(`/teacher/flex-suggestions/${suggestion.id}`, { status })
}
</script>

<template>
    <Head :title="t('nav.suggestions')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('teacher.suggestions_heading') }}</h1>
        <div class="mt-6 space-y-3">
            <article v-for="suggestion in suggestions" :key="suggestion.id" class="card">
                <h2 class="font-semibold">{{ suggestion.title }}</h2>
                <p class="mt-2">{{ suggestion.content }}</p>
                <p class="mt-2 text-sm text-[#626F86]">{{ t('suggestion_statuses.' + suggestion.status) }}</p>
                <div v-if="suggestion.status === 'unread'" class="mt-3 flex gap-2">
                    <button class="btn" type="button" @click="decide(suggestion, 'accepted')">{{ t('teacher.accept') }}</button>
                    <button class="btn-danger" type="button" @click="decide(suggestion, 'rejected')">{{ t('teacher.reject') }}</button>
                </div>
            </article>
            <p v-if="suggestions.length === 0" class="text-sm text-[#626F86]">{{ t('teacher.no_suggestions') }}</p>
        </div>
    </AppLayout>
</template>
