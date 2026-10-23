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
    types: { type: Array, required: true },
    statuses: { type: Array, required: true },
    quotes: { type: Array, required: true },
})

const billingByPayment = {
    monthly_subscription: 'monthly',
    quarterly: 'quarterly',
    semester: 'semester',
    offer_package: 'offer_package',
    drop_in_ticket: 'drop_in',
}

const form = useForm({
    student_id: '',
    course_id: '',
    amount: '',
    type: 'monthly_subscription',
    status: 'pending',
    due_date: new Date().toISOString().slice(0, 10),
})

const suggestion = computed(() => {
    const course = props.courses.find((item) => String(item.id) === String(form.course_id))
    const method = billingByPayment[form.type]

    if (!course || !method || course.prices?.[method] === undefined || course.prices?.[method] === null) {
        return null
    }

    const list = Number(course.prices[method])

    if (method !== 'monthly') {
        return { list, amount: list, percent: 0 }
    }

    const quote = props.quotes.find((item) => String(item.student_id) === String(form.student_id))
    const line = quote?.lines.find((item) => String(item.course_id) === String(course.id) && item.billing_method === 'monthly')

    if (line) {
        return {
            list: Number(line.list_price),
            amount: Number(line.amount),
            percent: line.discounted ? Number(quote.percent) : 0,
        }
    }

    return { list, amount: list, percent: 0 }
})

watch(suggestion, (value) => {
    if (value) {
        form.amount = value.amount
    }
})

function submit() {
    form.post('/admin/payments', { onSuccess: () => form.reset('amount') })
}

function changeStatus(payment, status) {
    router.patch(`/admin/payments/${payment.id}`, { status })
}
</script>

<template>
    <Head :title="t('nav.payments')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.payments') }}</h1>
        <form class="card mt-6 grid gap-3 md:grid-cols-3" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.student') }}
                <select v-model="form.student_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.course') }}
                <select v-model="form.course_id" class="field mt-1">
                    <option value="">{{ t('common.general_charge') }}</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.amount') }}
                <input v-model="form.amount" class="field mt-1" type="number" min="0" step="0.01" required>
                <span v-if="suggestion && suggestion.percent > 0" class="mt-1 block text-[#44546F]">
                    {{ t('admin.suggested_price', { list: money(suggestion.list), percent: suggestion.percent, amount: money(suggestion.amount) }) }}
                </span>
            </label>
            <label class="text-sm">{{ t('fields.type') }}
                <select v-model="form.type" class="field mt-1">
                    <option v-for="type in types" :key="type" :value="type">{{ t('payment_types.' + type) }}</option>
                </select>
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
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.student') }}</th><th>{{ t('fields.course') }}</th><th>{{ t('fields.amount') }}</th><th>{{ t('fields.type') }}</th><th>{{ t('fields.due') }}</th><th>{{ t('fields.status') }}</th></tr></thead>
                <tbody>
                    <tr v-for="payment in payments" :key="payment.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ payment.student }}</td>
                        <td>{{ payment.course || '—' }}</td>
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
