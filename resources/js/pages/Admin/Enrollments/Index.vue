<script setup>
import { watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const props = defineProps({
    enrollments: { type: Array, required: true },
    students: { type: Array, required: true },
    courses: { type: Array, required: true },
    statuses: { type: Array, required: true },
    quotes: { type: Array, required: true },
})

const { t, money } = useI18n()

const form = useForm({
    student_id: '',
    course_id: '',
    status: 'active',
    billing_method: '',
})

function courseById(id) {
    return props.courses.find((course) => String(course.id) === String(id))
}

function methodsFor(id) {
    return courseById(id)?.billing_methods ?? []
}

function priceOf(id, method) {
    return courseById(id)?.prices?.[method]
}

watch(() => form.course_id, (id) => {
    const methods = methodsFor(id)

    if (!methods.includes(form.billing_method)) {
        form.billing_method = methods[0] ?? ''
    }
})

function submit() {
    form.post('/admin/enrollments', { onSuccess: () => form.reset('student_id', 'course_id', 'billing_method') })
}

function changeStatus(enrollment, status) {
    router.patch(`/admin/enrollments/${enrollment.id}`, { status })
}

function changeBilling(enrollment, billingMethod) {
    router.patch(`/admin/enrollments/${enrollment.id}`, { billing_method: billingMethod })
}

function remove(enrollment) {
    if (window.confirm(t('confirm.delete_enrollment'))) {
        router.delete(`/admin/enrollments/${enrollment.id}`)
    }
}
</script>

<template>
    <Head :title="t('nav.enrollments')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.enrollments') }}</h1>
        <form class="card mt-6 grid gap-3 md:grid-cols-4" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.student') }}
                <select v-model="form.student_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }}</option>
                </select>
                <span v-if="form.errors.student_id" class="error">{{ form.errors.student_id }}</span>
            </label>
            <label class="text-sm">{{ t('fields.course') }}
                <select v-model="form.course_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.billing') }}
                <select v-model="form.billing_method" class="field mt-1" required>
                    <option v-for="method in methodsFor(form.course_id)" :key="method" :value="method">
                        {{ t('billing_methods.' + method) }} · {{ money(priceOf(form.course_id, method)) }}
                    </option>
                </select>
                <span v-if="form.errors.billing_method" class="error">{{ form.errors.billing_method }}</span>
            </label>
            <label class="text-sm">{{ t('fields.status') }}
                <select v-model="form.status" class="field mt-1">
                    <option v-for="status in statuses" :key="status" :value="status">{{ t('enrollment_statuses.' + status) }}</option>
                </select>
            </label>
            <div class="md:col-span-4"><button class="btn" :disabled="form.processing">{{ t('admin.enroll') }}</button></div>
        </form>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.student') }}</th><th>{{ t('fields.course') }}</th><th>{{ t('fields.billing') }}</th><th>{{ t('fields.price') }}</th><th>{{ t('fields.status') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="enrollment in enrollments" :key="enrollment.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ enrollment.student.name }}</td>
                        <td>{{ enrollment.course.title }}</td>
                        <td>
                            <select class="field" :value="enrollment.billing_method" @change="changeBilling(enrollment, $event.target.value)">
                                <option v-for="method in enrollment.course.billing_methods" :key="method" :value="method">
                                    {{ t('billing_methods.' + method) }}
                                </option>
                            </select>
                        </td>
                        <td>{{ money(enrollment.list_price) }}</td>
                        <td>
                            <select class="field" :value="enrollment.status" @change="changeStatus(enrollment, $event.target.value)">
                                <option v-for="status in statuses" :key="status" :value="status">{{ t('enrollment_statuses.' + status) }}</option>
                            </select>
                        </td>
                        <td class="text-right"><button class="btn-danger" type="button" @click="remove(enrollment)">{{ t('actions.delete') }}</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <section class="card mt-6">
            <h2 class="font-semibold">{{ t('admin.monthly_tuition') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-[#44546F]">{{ t('admin.monthly_tuition_hint') }}</p>
            <p v-if="quotes.length === 0" class="mt-4 text-sm text-[#626F86]">{{ t('admin.no_monthly_tuition') }}</p>
            <div v-for="quote in quotes" :key="quote.student_id" class="mt-4 border-t border-[#DFE1E6] pt-4">
                <p class="font-medium">{{ quote.student }}</p>
                <p class="text-sm text-[#44546F]">
                    {{ t('admin.active_course_count', { count: quote.course_count }) }}
                    ·
                    <span v-if="quote.percent > 0">{{ t('admin.discount_applied', { percent: quote.percent }) }}</span>
                    <span v-else>{{ t('admin.no_discount') }}</span>
                </p>
                <div v-for="line in quote.lines" :key="line.course_id" class="mt-1 flex flex-wrap justify-between gap-2 text-sm">
                    <span>{{ line.course }} · {{ t('billing_methods.' + line.billing_method) }}</span>
                    <span v-if="line.discounted">{{ money(line.list_price) }} → {{ money(line.amount) }}</span>
                    <span v-else>{{ money(line.amount) }}</span>
                </div>
                <p v-if="quote.lines.some((line) => line.billing_method === 'monthly')" class="mt-2 text-right text-sm font-medium">
                    {{ t('admin.monthly_total') }} {{ money(quote.monthly_due) }}
                </p>
            </div>
        </section>
    </AppLayout>
</template>
