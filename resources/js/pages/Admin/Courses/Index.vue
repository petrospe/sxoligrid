<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import CourseBillingFields from '@/Components/CourseBillingFields.vue'
import { billingPayload, emptyPrices } from '@/billing'
import { useI18n } from '@/i18n'

const props = defineProps({
    courses: { type: Array, required: true },
    types: { type: Array, required: true },
    billing_methods: { type: Array, required: true },
    discounts: { type: Array, required: true },
})

const { t, money } = useI18n()

const form = useForm({
    title: '',
    description: '',
    type: 'group',
    billing_methods: ['monthly'],
    prices: emptyPrices(props.billing_methods),
})

const discountForm = useForm({
    discounts: props.discounts.map((tier) => ({
        min_courses: tier.min_courses,
        percent: tier.percent,
    })),
})

function submit() {
    form.transform(billingPayload).post('/admin/courses', {
        onSuccess: () => {
            form.reset('title', 'description')
            form.billing_methods = ['monthly']
            form.prices = emptyPrices(props.billing_methods)
        },
    })
}

function addTier() {
    const used = discountForm.discounts.map((tier) => Number(tier.min_courses))
    let minCourses = 2

    while (used.includes(minCourses)) {
        minCourses += 1
    }

    discountForm.discounts.push({ min_courses: minCourses, percent: 10 })
}

function useExample() {
    discountForm.discounts = [
        { min_courses: 2, percent: 10 },
        { min_courses: 3, percent: 15 },
    ]
}

function removeTier(index) {
    discountForm.discounts.splice(index, 1)
}

function saveDiscounts() {
    discountForm.put('/admin/billing-discounts', {
        preserveScroll: true,
        onSuccess: (page) => {
            discountForm.defaults({
                discounts: page.props.discounts.map((tier) => ({
                    min_courses: tier.min_courses,
                    percent: tier.percent,
                })),
            })
            discountForm.reset()
        },
    })
}

function remove(course) {
    if (window.confirm(t('confirm.delete_course', { name: course.title }))) {
        router.delete(`/admin/courses/${course.id}`)
    }
}
</script>

<template>
    <Head :title="t('nav.courses')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.courses') }}</h1>
        <form class="card mt-6 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.title') }}
                <input v-model="form.title" class="field mt-1" required>
                <span v-if="form.errors.title" class="error">{{ form.errors.title }}</span>
            </label>
            <label class="text-sm">{{ t('fields.type') }}
                <select v-model="form.type" class="field mt-1">
                    <option v-for="type in types" :key="type" :value="type">{{ t('course_types.' + type) }}</option>
                </select>
            </label>
            <CourseBillingFields :form="form" :methods="billing_methods" />
            <label class="text-sm md:col-span-2">{{ t('fields.description') }}
                <textarea v-model="form.description" class="field mt-1" rows="2"></textarea>
            </label>
            <div class="md:col-span-2">
                <button class="btn" :disabled="form.processing">{{ t('actions.create') }}</button>
            </div>
        </form>

        <form class="card mt-6" @submit.prevent="saveDiscounts">
            <h2 class="font-semibold">{{ t('admin.discount_title') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-[#44546F]">{{ t('admin.discount_hint') }}</p>
            <div class="mt-4 grid gap-3">
                <div v-for="(tier, index) in discountForm.discounts" :key="index" class="flex flex-wrap items-end gap-3">
                    <label class="text-sm">{{ t('admin.discount_from') }}
                        <input v-model="tier.min_courses" class="field mt-1 max-w-28" type="number" min="2" max="20" required>
                    </label>
                    <span class="pb-2 text-sm text-[#44546F]">{{ t('admin.discount_courses') }}</span>
                    <label class="text-sm">{{ t('fields.percent') }}
                        <input v-model="tier.percent" class="field mt-1 max-w-28" type="number" min="0" max="100" step="0.01" required>
                    </label>
                    <button class="btn-danger" type="button" @click="removeTier(index)">{{ t('actions.remove') }}</button>
                    <span v-if="discountForm.errors[`discounts.${index}.min_courses`]" class="error">{{ discountForm.errors[`discounts.${index}.min_courses`] }}</span>
                    <span v-if="discountForm.errors[`discounts.${index}.percent`]" class="error">{{ discountForm.errors[`discounts.${index}.percent`] }}</span>
                </div>
            </div>
            <p v-if="discountForm.errors.discounts" class="error">{{ discountForm.errors.discounts }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <button class="btn-soft" type="button" @click="addTier">{{ t('admin.discount_add') }}</button>
                <button v-if="discountForm.discounts.length === 0" class="btn-soft" type="button" @click="useExample">{{ t('admin.discount_example_action') }}</button>
                <button class="btn" :disabled="discountForm.processing">{{ t('actions.save') }}</button>
            </div>
        </form>

        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.title') }}</th><th>{{ t('fields.type') }}</th><th>{{ t('fields.billing') }}</th><th>{{ t('fields.price') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="course in courses" :key="course.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3 font-medium">{{ course.title }}</td>
                        <td>{{ t('course_types.' + course.type) }}</td>
                        <td>
                            <div v-for="method in course.billing_methods" :key="method" class="py-0.5">{{ t('billing_methods.' + method) }}</div>
                        </td>
                        <td>
                            <div v-for="method in course.billing_methods" :key="method" class="py-0.5">{{ money(course.prices[method]) }}</div>
                        </td>
                        <td class="space-x-2 text-right">
                            <Link class="btn-soft" :href="`/admin/courses/${course.id}/edit`">{{ t('actions.edit') }}</Link>
                            <button class="btn-danger" type="button" @click="remove(course)">{{ t('actions.delete') }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
