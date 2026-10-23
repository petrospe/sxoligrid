<script setup>
import { computed, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t, money } = useI18n()

const props = defineProps({
    payments: { type: Array, required: true },
    students: { type: Array, required: true },
    courses: { type: Array, required: true },
    subscriptions: { type: Array, required: true },
    types: { type: Array, required: true },
    statuses: { type: Array, required: true },
})

const form = useForm({
    student_id: '',
    subscription_id: '',
    course_id: '',
    amount: '',
    type: 'installment',
    status: 'pending',
    due_date: new Date().toISOString().slice(0, 10),
})

const studentSubscriptions = computed(() => props.subscriptions.filter((item) => String(item.student_id) === String(form.student_id)))

watch(() => form.type, (type) => {
    if (type === 'drop_in') {
        form.subscription_id = ''
    }
})

watch(() => form.student_id, () => {
    if (!studentSubscriptions.value.some((item) => String(item.id) === String(form.subscription_id))) {
        form.subscription_id = ''
    }
})

watch(() => form.subscription_id, (id) => {
    const subscription = props.subscriptions.find((item) => String(item.id) === String(id))

    if (subscription?.course_id) {
        form.course_id = subscription.course_id
    }
})

function submit() {
    form.post('/admin/payments', { onSuccess: () => form.reset('amount') })
}

function changeStatus(payment, status) {
    router.patch(`/admin/payments/${payment.id}`, { status })
}

function about(payment) {
    return payment.subscription || payment.course || '—'
}
</script>

<template>
    <Head :title="t('nav.payments')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.payments') }}</h1>
        <p class="mt-2 max-w-3xl text-sm text-[#44546F]">{{ t('admin.payments_hint') }}</p>
        <form class="card mt-6 grid gap-3 md:grid-cols-3" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.student') }}
                <select v-model="form.student_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }}</option>
                </select>
                <span v-if="form.errors.student_id" class="error">{{ form.errors.student_id }}</span>
            </label>
            <label class="text-sm">{{ t('fields.type') }}
                <select v-model="form.type" class="field mt-1">
                    <option v-for="type in types" :key="type" :value="type">{{ t('payment_types.' + type) }}</option>
                </select>
            </label>
            <label v-if="form.type === 'installment'" class="text-sm">{{ t('fields.subscription') }}
                <select v-model="form.subscription_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="item in studentSubscriptions" :key="item.id" :value="item.id">
                        {{ item.title }}<span v-if="item.course"> · {{ item.course }}</span>
                    </option>
                </select>
                <span v-if="form.errors.subscription_id" class="error">{{ form.errors.subscription_id }}</span>
            </label>
            <label class="text-sm">{{ t('fields.course') }}
                <select v-model="form.course_id" class="field mt-1">
                    <option value="">{{ t('common.no_course') }}</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.amount') }}
                <input v-model="form.amount" class="field mt-1" type="number" min="0" step="0.01" required>
            </label>
            <label class="text-sm">{{ t('fields.status') }}
                <select v-model="form.status" class="field mt-1">
                    <option v-for="status in statuses" :key="status" :value="status">{{ t('payment_statuses.' + status) }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.due') }}<input v-model="form.due_date" class="field mt-1" type="date"></label>
            <div class="md:col-span-3"><button class="btn" :disabled="form.processing">{{ t('actions.record') }}</button></div>
        </form>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.student') }}</th><th>{{ t('fields.subscription') }}</th><th>{{ t('fields.amount') }}</th><th>{{ t('fields.type') }}</th><th>{{ t('fields.due') }}</th><th>{{ t('fields.status') }}</th></tr></thead>
                <tbody>
                    <tr v-for="payment in payments" :key="payment.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ payment.student }}</td>
                        <td>{{ about(payment) }}</td>
                        <td>{{ money(payment.amount) }}</td>
                        <td>{{ t('payment_types.' + payment.type) }}</td>
                        <td>{{ payment.due_date || '—' }}</td>
                        <td>
                            <select class="field" :value="payment.status" @change="changeStatus(payment, $event.target.value)">
                                <option v-for="status in statuses" :key="status" :value="status">{{ t('payment_statuses.' + status) }}</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
