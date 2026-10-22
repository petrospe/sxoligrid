<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    package: { type: Object, required: true },
})

const form = useForm({
    title: props.package.title,
    price: props.package.price,
    duration_days: props.package.duration_days ?? '',
    sessions_count: props.package.sessions_count ?? '',
})

function submit() {
    form.put(`/admin/packages/${props.package.id}`)
}
</script>

<template>
    <Head :title="package.title" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('admin.edit_package') }}</h1>
        <form class="card mt-6 grid max-w-xl gap-3" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.title') }}<input v-model="form.title" class="field mt-1" required></label>
            <label class="text-sm">{{ t('fields.price') }}<input v-model="form.price" class="field mt-1" type="number" min="0" step="0.01" required></label>
            <label class="text-sm">{{ t('fields.duration_days') }}<input v-model="form.duration_days" class="field mt-1" type="number" min="1"></label>
            <label class="text-sm">{{ t('fields.sessions') }}<input v-model="form.sessions_count" class="field mt-1" type="number" min="1"></label>
            <button class="btn w-fit" :disabled="form.processing">{{ t('actions.save') }}</button>
        </form>
    </AppLayout>
</template>
