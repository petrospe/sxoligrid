<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    course: { type: Object, required: true },
    types: { type: Array, required: true },
    billing_methods: { type: Array, required: true },
})

const form = useForm({
    title: props.course.title,
    description: props.course.description ?? '',
    price: props.course.price,
    type: props.course.type,
    billing_methods: props.course.billing_methods ?? ['monthly'],
})

function submit() {
    form.put(`/admin/courses/${props.course.id}`)
}
</script>

<template>
    <Head :title="course.title" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('admin.edit_course') }}</h1>
        <form class="card mt-6 grid max-w-2xl gap-3" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.title') }}
                <input v-model="form.title" class="field mt-1" required>
            </label>
            <label class="text-sm">{{ t('fields.type') }}
                <select v-model="form.type" class="field mt-1">
                    <option v-for="type in types" :key="type" :value="type">{{ t('course_types.' + type) }}</option>
                </select>
            </label>
            <fieldset class="text-sm">
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
            <label class="text-sm">{{ t('fields.description') }}
                <textarea v-model="form.description" class="field mt-1" rows="3"></textarea>
            </label>
            <label class="text-sm">{{ t('fields.price') }}
                <input v-model="form.price" class="field mt-1" type="number" min="0" step="0.01" required>
            </label>
            <button class="btn w-fit" :disabled="form.processing">{{ t('actions.save') }}</button>
        </form>
    </AppLayout>
</template>
