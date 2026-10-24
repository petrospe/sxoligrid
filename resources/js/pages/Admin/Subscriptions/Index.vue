<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import SuggestionField from '@/Components/SuggestionField.vue'
import { useI18n } from '@/i18n'

defineProps({
    subscriptions: { type: Array, required: true },
    students: { type: Array, required: true },
    courses: { type: Array, required: true },
    titles: { type: Array, required: true },
})

const { t, money } = useI18n()

const form = useForm({
    student_id: '',
    course_id: '',
    title: '',
    total_amount: '',
    starts_at: new Date().toISOString().slice(0, 10),
    ends_at: '',
})

function submit() {
    form.post('/admin/subscriptions', {
        onSuccess: () => form.reset('student_id', 'course_id', 'title', 'total_amount', 'ends_at'),
    })
}
</script>

<template>
    <Head :title="t('nav.subscriptions')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('admin.subscriptions_title') }}</h1>
        <p class="mt-2 max-w-3xl text-sm text-[#44546F]">{{ t('admin.subscriptions_hint') }}</p>
        <form class="card mt-6 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.student') }}
                <select v-model="form.student_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }}</option>
                </select>
                <span v-if="form.errors.student_id" class="error">{{ form.errors.student_id }}</span>
            </label>
            <label class="text-sm">{{ t('fields.course') }}
                <select v-model="form.course_id" class="field mt-1">
                    <option value="">{{ t('common.no_course') }}</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
                </select>
            </label>
            <label class="text-sm md:col-span-2">{{ t('fields.title') }}
                <SuggestionField v-model="form.title" :options="titles" :label="t('fields.title')" />
                <span v-if="form.errors.title" class="error">{{ form.errors.title }}</span>
            </label>
            <label class="text-sm">{{ t('fields.total') }}
                <input v-model="form.total_amount" class="field mt-1" type="number" min="0" step="0.01" required>
                <span v-if="form.errors.total_amount" class="error">{{ form.errors.total_amount }}</span>
            </label>
            <label class="text-sm">{{ t('fields.starts') }}
                <input v-model="form.starts_at" class="field mt-1" type="date" required>
            </label>
            <label class="text-sm">{{ t('fields.until') }}
                <input v-model="form.ends_at" class="field mt-1" type="date" required>
                <span v-if="form.errors.ends_at" class="error">{{ form.errors.ends_at }}</span>
            </label>
            <div class="md:col-span-2"><button class="btn" :disabled="form.processing">{{ t('actions.save') }}</button></div>
        </form>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[880px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.student') }}</th><th>{{ t('fields.title') }}</th><th>{{ t('fields.course') }}</th><th>{{ t('fields.total') }}</th><th>{{ t('fields.paid') }}</th><th>{{ t('fields.balance') }}</th><th>{{ t('fields.from') }}</th><th>{{ t('fields.until') }}</th><th>{{ t('fields.status') }}</th></tr></thead>
                <tbody>
                    <tr v-for="item in subscriptions" :key="item.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ item.student }}</td>
                        <td>{{ item.title }}</td>
                        <td>{{ item.course || '—' }}</td>
                        <td>{{ money(item.total_amount) }}</td>
                        <td>{{ money(item.paid_amount) }}</td>
                        <td>{{ money(item.remaining_amount) }}</td>
                        <td>{{ item.starts_at }}</td>
                        <td>{{ item.ends_at || '—' }}</td>
                        <td>{{ t('subscription_statuses.' + item.status) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
