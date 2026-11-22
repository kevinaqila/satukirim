<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

// Compute whether we should show the avatar image
const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);
</script>

<template>
    <Avatar :key="user.avatar || 'fallback'" class="h-8 w-8 overflow-hidden rounded-lg shrink-0">
        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" class="object-cover" />
        <AvatarFallback class="rounded-lg bg-stone-200 text-stone-400">
            <svg
                class="size-5"
                fill="currentColor"
                viewBox="0 0 20 20"
                aria-hidden="true"
            >   
                <path
                    fill-rule="evenodd"
                    d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                    clip-rule="evenodd"
                />
            </svg>
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight min-w-0">
        <span class="truncate font-medium text-stone-900" :title="user.name">{{ user.name }}</span>
        <span class="truncate text-xs text-stone-500" :title="user.email">{{
            user.email
        }}</span>
    </div>
</template>
