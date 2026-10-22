<script setup>
import { computed } from 'vue'
import { dayValues, useI18n } from '@/i18n'

const props = defineProps({
    schedules: { type: Array, required: true },
    showTeacher: { type: Boolean, default: false },
})

const { t } = useI18n()

function teacherNames(item) {
    const names = Array.isArray(item.teachers)
        ? item.teachers.map((teacher) => teacher.name).filter(Boolean)
        : []

    if (names.length > 0) {
        return names.join(', ')
    }

    return item.teacher?.name ?? t('common.no_teacher')
}

const columns = computed(() => dayValues.map((value) => ({
    value,
    label: t(`days.${value}`),
    items: props.schedules
        .filter((item) => Number(item.day_of_week) === value)
        .slice()
        .sort((left, right) => left.start_time.localeCompare(right.start_time)),
})))
</script>

<template>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-7">
        <section v-for="day in columns" :key="day.value" class="card min-h-40 p-3">
            <h2 class="text-xs font-semibold tracking-wide text-[#626F86] uppercase">{{ day.label }}</h2>
            <p v-if="day.items.length === 0" class="mt-4 text-sm text-[#626F86]">{{ t('common.empty_day') }}</p>
            <article
                v-for="item in day.items"
                :key="item.id"
                class="mt-3 rounded-[3px] border border-[#DFE1E6] bg-[#F7F8F9] p-2.5"
            >
                <p class="text-xs font-medium text-[#0C66E4]">{{ item.start_time }}–{{ item.end_time }}</p>
                <h3 class="mt-1 text-sm font-semibold">{{ item.course.title }}</h3>
                <p class="text-xs text-[#44546F]">{{ t('course_types.' + item.course.type) }} · {{ item.room }}</p>
                <p v-if="showTeacher" class="mt-1 text-sm">{{ teacherNames(item) }}</p>
            </article>
        </section>
    </div>
</template>
