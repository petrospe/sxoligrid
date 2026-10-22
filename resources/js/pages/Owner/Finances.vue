<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t, money } = useI18n()

const props = defineProps({
    summary: { type: Object, required: true },
    expenses: { type: Array, required: true },
    payrolls: { type: Array, required: true },
    teachers: { type: Array, required: true },
    categories: { type: Array, required: true },
    payrollTypes: { type: Array, required: true },
    payrollStatuses: { type: Array, required: true },
})

const expense = useForm({
    title: '',
    amount: '',
    category: 'rent',
    expense_date: `${props.summary.month}-01`,
})

const payroll = useForm({
    teacher_id: '',
    type: 'monthly_salary',
    base_amount: '',
    payment_period: props.summary.month,
    status: 'pending',
})

function changeMonth(event) {
    router.get('/owner/finances', { month: event.target.value })
}

function saveExpense() {
    expense.post('/owner/expenses', { onSuccess: () => expense.reset('title', 'amount') })
}

function savePayroll() {
    payroll.post('/owner/payrolls', { onSuccess: () => payroll.reset('teacher_id', 'base_amount') })
}

function removeExpense(item) {
    router.delete(`/owner/expenses/${item.id}`)
}

function setPayrollStatus(item, status) {
    router.patch(`/owner/payrolls/${item.id}`, { status })
}

function removePayroll(item) {
    router.delete(`/owner/payrolls/${item.id}`)
}

const cards = [
    ['income', t('owner.income')],
    ['expenses', t('owner.expenses')],
    ['payroll_paid', t('owner.payroll_paid')],
    ['payroll_pending', t('owner.payroll_pending')],
    ['net_profit', t('owner.net_profit')],
]
</script>

<template>
    <Head :title="t('nav.finances')" />
    <AppLayout>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">{{ t('nav.finances') }}</h1>
                <p class="mt-2 text-sm text-[#44546F]">{{ t('owner.hint') }}</p>
            </div>
            <label class="text-sm">{{ t('fields.month') }}
                <input class="field mt-1" type="month" :value="summary.month" @change="changeMonth">
            </label>
        </div>
        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <article v-for="[key, title] in cards" :key="key" class="card">
                <p class="text-sm text-[#626F86]">{{ title }}</p>
                <p class="mt-2 text-2xl font-semibold" :class="key === 'net_profit' && summary.net_profit < 0 ? 'text-[#AE2E24]' : ''">{{ money(summary[key]) }}</p>
            </article>
        </div>

        <h2 class="mt-8 text-xl font-semibold">{{ t('owner.expenses') }}</h2>
        <form class="card mt-3 grid gap-3 md:grid-cols-4" @submit.prevent="saveExpense">
            <label class="text-sm">{{ t('fields.title') }}<input v-model="expense.title" class="field mt-1" required></label>
            <label class="text-sm">{{ t('fields.amount') }}<input v-model="expense.amount" class="field mt-1" type="number" min="0" step="0.01" required></label>
            <label class="text-sm">{{ t('fields.category') }}
                <select v-model="expense.category" class="field mt-1">
                    <option v-for="category in categories" :key="category" :value="category">{{ t('expense_categories.' + category) }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.date') }}<input v-model="expense.expense_date" class="field mt-1" type="date" required></label>
            <div class="md:col-span-4"><button class="btn" :disabled="expense.processing">{{ t('actions.record') }}</button></div>
        </form>
        <div class="card mt-3 overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.title') }}</th><th>{{ t('fields.category') }}</th><th>{{ t('fields.date') }}</th><th>{{ t('fields.amount') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in expenses" :key="item.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ item.title }}</td>
                        <td>{{ t('expense_categories.' + item.category) }}</td>
                        <td>{{ item.expense_date }}</td>
                        <td>{{ money(item.amount) }}</td>
                        <td class="text-right"><button class="btn-danger" type="button" @click="removeExpense(item)">{{ t('actions.delete') }}</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="mt-8 text-xl font-semibold">{{ t('owner.payroll_heading') }}</h2>
        <form class="card mt-3 grid gap-3 md:grid-cols-3" @submit.prevent="savePayroll">
            <label class="text-sm">{{ t('fields.teacher') }}
                <select v-model="payroll.teacher_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.type') }}
                <select v-model="payroll.type" class="field mt-1">
                    <option v-for="type in payrollTypes" :key="type" :value="type">{{ t('payroll_types.' + type) }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.amount') }}<input v-model="payroll.base_amount" class="field mt-1" type="number" min="0" step="0.01" required></label>
            <label class="text-sm">{{ t('fields.period') }}<input v-model="payroll.payment_period" class="field mt-1" type="month" required></label>
            <label class="text-sm">{{ t('fields.status') }}
                <select v-model="payroll.status" class="field mt-1">
                    <option v-for="status in payrollStatuses" :key="status" :value="status">{{ t('payroll_statuses.' + status) }}</option>
                </select>
            </label>
            <div class="md:col-span-3"><button class="btn" :disabled="payroll.processing">{{ t('actions.record') }}</button></div>
        </form>
        <div class="card mt-3 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.teacher') }}</th><th>{{ t('fields.type') }}</th><th>{{ t('fields.amount') }}</th><th>{{ t('fields.status') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in payrolls" :key="item.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ item.teacher }}</td>
                        <td>{{ t('payroll_types.' + item.type) }}</td>
                        <td>{{ money(item.base_amount) }}</td>
                        <td>
                            <select class="field" :value="item.status" @change="setPayrollStatus(item, $event.target.value)">
                                <option v-for="status in payrollStatuses" :key="status" :value="status">{{ t('payroll_statuses.' + status) }}</option>
                            </select>
                        </td>
                        <td class="text-right"><button class="btn-danger" type="button" @click="removePayroll(item)">{{ t('actions.delete') }}</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
