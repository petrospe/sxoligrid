<script setup>
import { useI18n } from '@/i18n'

defineProps({
    methods: { type: Array, required: true },
    form: { type: Object, required: true },
})

const { t } = useI18n()
</script>

<template>
    <fieldset class="text-sm md:col-span-2">
        <legend>{{ t('fields.billing') }}</legend>
        <p class="mt-1 text-[#44546F]">{{ t('admin.billing_hint') }}</p>
        <div class="mt-3 grid gap-2">
            <div v-for="method in methods" :key="method" class="flex flex-wrap items-center gap-3">
                <label class="flex w-48 items-center gap-2">
                    <input v-model="form.billing_methods" :value="method" class="h-4 w-4" type="checkbox">
                    {{ t('billing_methods.' + method) }}
                </label>
                <label v-if="form.billing_methods.includes(method)" class="flex items-center gap-2">
                    <span class="text-[#626F86]">{{ t('fields.price') }}</span>
                    <input v-model="form.prices[method]" class="field max-w-32" type="number" min="0" step="0.01" required>
                </label>
                <span v-if="form.errors['prices.' + method]" class="error">{{ form.errors['prices.' + method] }}</span>
            </div>
        </div>
        <span v-if="form.errors.billing_methods" class="error">{{ form.errors.billing_methods }}</span>
        <span v-if="form.errors.prices" class="error">{{ form.errors.prices }}</span>
    </fieldset>
</template>
