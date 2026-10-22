<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t, money } = useI18n()

defineProps({
    courses: { type: Array, required: true },
    types: { type: Array, required: true },
    billing_methods: { type: Array, required: true },
})

const form = useForm({
    title: '',
    description: '',
    price: '',
    type: 'group',
    billing_methods: ['monthly'],
})

function billingLabel(course) {
    return (course.billing_methods ?? []).map((method) => t('billing_methods.' + method)).join(', ')
}

function submit() {
    form.post('/admin/courses', { onSuccess: () => form.reset('title', 'description', 'price') })
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
            <fieldset class="text-sm md:col-span-2">
                <legend>{{ t('fields.billing') }}</legend>
                <p class="mt-1 text-[#44546F]">{{ t('admin.billing_hint') }}</p>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2">
                    <label v-for="method in billing_methods" :key="method" class="flex items-center gap-2">
                        <input v-model="form.billing_methods" :value="method" class="h-4 w-4" type="checkbox">
                        {{ t('billing_methods.' + method) }}
                    </label>
                </div>
                <span v-if="form.errors.billing_methods" class="error">{{ form.errors.billing_methods }}</span>
            </fieldset>
            <label class="text-sm md:col-span-2">{{ t('fields.description') }}
                <textarea v-model="form.description" class="field mt-1" rows="2"></textarea>
            </label>
            <label class="text-sm">{{ t('fields.price') }}
                <input v-model="form.price" class="field mt-1" type="number" min="0" step="0.01" required>
                <span v-if="form.errors.price" class="error">{{ form.errors.price }}</span>
            </label>
            <div class="md:col-span-2">
                <button class="btn" :disabled="form.processing">{{ t('actions.create') }}</button>
            </div>
        </form>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.title') }}</th><th>{{ t('fields.type') }}</th><th>{{ t('fields.billing') }}</th><th>{{ t('fields.price') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="course in courses" :key="course.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3 font-medium">{{ course.title }}</td>
                        <td>{{ t('course_types.' + course.type) }}</td>
                        <td>{{ billingLabel(course) }}</td>
                        <td>{{ money(course.price) }}</td>
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
