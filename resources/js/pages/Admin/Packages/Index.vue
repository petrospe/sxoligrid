<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t, money } = useI18n()

defineProps({
    packages: { type: Array, required: true },
})

const form = useForm({
    title: '',
    price: '',
    duration_days: '',
    sessions_count: '',
})

function submit() {
    form.post('/admin/packages', { onSuccess: () => form.reset() })
}

function remove(item) {
    if (window.confirm(t('confirm.delete_package', { name: item.title }))) {
        router.delete(`/admin/packages/${item.id}`)
    }
}
</script>

<template>
    <Head :title="t('nav.packages')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.packages') }}</h1>
        <p class="mt-2 max-w-2xl text-sm text-[#44546F]">{{ t('admin.packages_hint') }}</p>
        <form class="card mt-6 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
            <label class="text-sm md:col-span-2">{{ t('fields.title') }}<input v-model="form.title" class="field mt-1" required></label>
            <label class="text-sm">{{ t('fields.price') }}<input v-model="form.price" class="field mt-1" type="number" min="0" step="0.01" required></label>
            <label class="text-sm">{{ t('fields.duration_days') }}<input v-model="form.duration_days" class="field mt-1" type="number" min="1"></label>
            <label class="text-sm">{{ t('fields.sessions') }}<input v-model="form.sessions_count" class="field mt-1" type="number" min="1"></label>
            <div><button class="btn" :disabled="form.processing">{{ t('actions.create') }}</button></div>
        </form>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.title') }}</th><th>{{ t('fields.price') }}</th><th>{{ t('fields.days_count') }}</th><th>{{ t('fields.sessions_short') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in packages" :key="item.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3 font-medium">{{ item.title }}</td>
                        <td>{{ money(item.price) }}</td>
                        <td>{{ item.duration_days ?? '—' }}</td>
                        <td>{{ item.sessions_count ?? t('common.unlimited') }}</td>
                        <td class="space-x-2 text-right">
                            <Link class="btn-soft" :href="`/admin/packages/${item.id}/edit`">{{ t('actions.edit') }}</Link>
                            <button class="btn-danger" type="button" @click="remove(item)">{{ t('actions.delete') }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
