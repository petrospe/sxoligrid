<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t, money } = useI18n()

defineProps({
    subscriptions: { type: Array, required: true },
    students: { type: Array, required: true },
    packages: { type: Array, required: true },
    paymentStatuses: { type: Array, required: true },
})

const form = useForm({
    student_id: '',
    package_id: '',
    starts_at: new Date().toISOString().slice(0, 10),
    payment_status: 'paid',
})

function submit() {
    form.post('/admin/subscriptions', { onSuccess: () => form.reset('student_id', 'package_id') })
}
</script>

<template>
    <Head :title="t('nav.subscriptions')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('admin.subscriptions_title') }}</h1>
        <p class="mt-2 text-sm text-[#44546F]">{{ t('admin.subscriptions_hint') }}</p>
        <form class="card mt-6 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.student') }}
                <select v-model="form.student_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.package') }}
                <select v-model="form.package_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="item in packages" :key="item.id" :value="item.id">{{ item.title }} · {{ money(item.price) }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.starts') }}<input v-model="form.starts_at" class="field mt-1" type="date" required></label>
            <label class="text-sm">{{ t('fields.payment') }}
                <select v-model="form.payment_status" class="field mt-1">
                    <option v-for="status in paymentStatuses" :key="status" :value="status">{{ t('payment_statuses.' + status) }}</option>
                </select>
            </label>
            <div class="md:col-span-2"><button class="btn" :disabled="form.processing">{{ t('admin.assign') }}</button></div>
        </form>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.student') }}</th><th>{{ t('fields.package') }}</th><th>{{ t('fields.balance') }}</th><th>{{ t('fields.from') }}</th><th>{{ t('fields.until') }}</th><th>{{ t('fields.status') }}</th></tr></thead>
                <tbody>
                    <tr v-for="item in subscriptions" :key="item.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ item.student }}</td>
                        <td>{{ item.package }}</td>
                        <td>{{ item.remaining_sessions ?? t('common.unlimited') }}</td>
                        <td>{{ item.starts_at }}</td>
                        <td>{{ item.expires_at ?? '—' }}</td>
                        <td>{{ t('subscription_statuses.' + item.status) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
