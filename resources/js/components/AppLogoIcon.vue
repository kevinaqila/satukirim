<script setup lang="ts">
import { useId, type HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    class?: HTMLAttributes['class'];
    className?: HTMLAttributes['class'];
    variant?: 'color' | 'monochrome' | 'white';
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'color',
});

const uid = useId();
const petalId = `satukirim-petal-${uid}`;
const clipId = `satukirim-clip-${uid}`;
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 100 73"
        fill="none"
        :class="['overflow-visible shrink-0', props.class, props.className]"
        v-bind="$attrs"
    >
        <defs>
            <path
                :id="petalId"
                d="M 33 0 C 15 0, 0 15, 0 36.5 V 60 C 0 68, 5 73, 13 73 H 42 C 57 73, 67 56, 67 36.5 C 67 16, 53 0, 33 0 Z"
            />
            <clipPath :id="clipId">
                <use :href="`#${petalId}`" />
            </clipPath>
        </defs>

        <template v-if="props.variant === 'color'">
            <use :href="`#${petalId}`" fill="#A23F5C" />
            <use :href="`#${petalId}`" transform="rotate(180 50 36.5)" fill="#592244" />
            <g :clip-path="`url(#${clipId})`">
                <use :href="`#${petalId}`" transform="rotate(180 50 36.5)" fill="#79304E" />
            </g>
        </template>

        <template v-else-if="props.variant === 'monochrome'">
            <use :href="`#${petalId}`" fill="currentColor" fill-opacity="0.85" />
            <use :href="`#${petalId}`" transform="rotate(180 50 36.5)" fill="currentColor" fill-opacity="0.95" />
            <g :clip-path="`url(#${clipId})`">
                <use :href="`#${petalId}`" transform="rotate(180 50 36.5)" fill="currentColor" fill-opacity="0.5" />
            </g>
        </template>

        <template v-else>
            <use :href="`#${petalId}`" fill="#FFFFFF" fill-opacity="0.92" />
            <use :href="`#${petalId}`" transform="rotate(180 50 36.5)" fill="#FFFFFF" fill-opacity="0.65" />
            <g :clip-path="`url(#${clipId})`">
                <use :href="`#${petalId}`" transform="rotate(180 50 36.5)" fill="#FFFFFF" fill-opacity="1" />
            </g>
        </template>
    </svg>
</template>
