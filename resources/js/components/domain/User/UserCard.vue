<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail, MapPin, Pencil } from 'lucide-vue-next';
import UserStatusBadge from '@/components/domain/User/UserStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import * as userRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';

defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        avatar?: string | null;
        is_active: boolean;
        branch?: { id: number; name: string } | null;
        roles?: Array<{ id: number; name: string }>;
    };
}>();

const clinic = window.location.hostname.split('.')[0];

function initials(name: string) {
    return name
        .split(' ')
        .filter(Boolean)
        .map((word) => word[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}
</script>

<template>
    <Card>
        <CardContent class="flex items-start gap-4 p-4">
            <Avatar class="h-12 w-12">
                <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                <AvatarFallback>{{ initials(user.name) }}</AvatarFallback>
            </Avatar>

            <div class="min-w-0 flex-1 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <Link :href="userRoutes.show({ clinic, user: user.id }).url" class="font-medium text-foreground hover:underline">
                            {{ user.name }}
                        </Link>
                        <div class="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                            <Mail class="h-3.5 w-3.5" />
                            <span class="truncate">{{ user.email }}</span>
                        </div>
                    </div>
                    <UserStatusBadge :active="user.is_active" />
                </div>

                <div class="flex flex-wrap gap-2 text-xs text-muted-foreground">
                    <span v-if="user.branch" class="inline-flex items-center gap-1">
                        <MapPin class="h-3.5 w-3.5" />
                        {{ user.branch.name }}
                    </span>
                    <span v-for="role in user.roles ?? []" :key="role.id" class="rounded border px-2 py-0.5">
                        {{ role.name }}
                    </span>
                </div>
            </div>

            <Button variant="ghost" size="icon" as-child>
                <Link :href="userRoutes.edit({ clinic, user: user.id }).url" title="Editar usuario">
                    <Pencil class="h-4 w-4" />
                </Link>
            </Button>
        </CardContent>
    </Card>
</template>
