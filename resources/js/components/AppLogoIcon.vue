<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M9 4.5A2.5 2.5 0 0 1 11.5 2h1A2.5 2.5 0 0 1 15 4.5V6h3.5A2.5 2.5 0 0 1 21 8.5v9a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5v-9A2.5 2.5 0 0 1 5.5 6H9V4.5ZM13.5 6h-3V4.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V6ZM5 10.9V8.5a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v2.4a17.9 17.9 0 0 1-7 1.6 17.9 17.9 0 0 1-7-1.6Zm0 2.17v4.43a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-4.43a19.9 19.9 0 0 1-6 1.32V15a1 1 0 1 1-2 0v-.61a19.9 19.9 0 0 1-6-1.32Z"
        />
    </svg>
</template>
