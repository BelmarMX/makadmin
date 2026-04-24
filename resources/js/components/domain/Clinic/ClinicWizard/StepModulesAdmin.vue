<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    form: {
        modules: string[];
        admin: { name: string; email: string; phone: string };
        errors: Record<string, string>;
    };
    modules: Array<{
        key: string;
        label: string;
        description: string;
        icon: string;
        dependsOn: string[];
    }>;
}>();

function toggleModule(key: string, checked: boolean) {
    if (checked) {
        if (!props.form.modules.includes(key)) {
            props.form.modules.push(key);
        }
    } else {
        const idx = props.form.modules.indexOf(key);
        if (idx > -1) props.form.modules.splice(idx, 1);
    }
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <p class="mb-3 font-medium text-foreground">Módulos a activar <span class="text-destructive">*</span></p>
            <p v-if="props.form.errors.modules" class="mb-2 text-xs text-destructive">{{ props.form.errors.modules }}</p>
            <div class="grid grid-cols-2 gap-2">
                <label
                    v-for="m in props.modules"
                    :key="m.key"
                    :class="[
                        'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors',
                        props.form.modules.includes(m.key) ? 'border-primary/50 bg-primary/5' : 'border-border hover:border-muted-foreground/30',
                    ]"
                >
                    <Checkbox
                        :id="m.key"
                        :model-value="props.form.modules.includes(m.key)"
                        class="mt-0.5"
                        @update:model-value="(v) => toggleModule(m.key, Boolean(v))"
                    />
                    <div class="flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-medium">{{ m.label }}</span>
                            <Badge v-if="m.dependsOn.length" variant="outline" class="text-xs">
                                +dep
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ m.description }}</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="space-y-4 border-t border-border pt-5">
            <p class="font-medium text-foreground">Admin inicial de la clínica</p>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="admin_name">Nombre <span class="text-destructive">*</span></Label>
                    <Input id="admin_name" v-model="props.form.admin.name" placeholder="María García" />
                    <p v-if="props.form.errors['admin.name']" class="text-xs text-destructive">{{ props.form.errors['admin.name'] }}</p>
                </div>
                <div class="space-y-2">
                    <Label for="admin_email">Email <span class="text-destructive">*</span></Label>
                    <Input id="admin_email" v-model="props.form.admin.email" type="email" placeholder="admin@mivet.com" />
                    <p v-if="props.form.errors['admin.email']" class="text-xs text-destructive">{{ props.form.errors['admin.email'] }}</p>
                </div>
            </div>
            <div class="space-y-2">
                <Label for="admin_phone">Teléfono del admin</Label>
                <Input id="admin_phone" v-model="props.form.admin.phone" type="tel" />
            </div>
        </div>
    </div>
</template>
