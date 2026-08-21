<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ImageUp, Save } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type GeneralSettings = {
    school_name: string;
    school_logo: string | null;
    school_address: string;
};

const props = defineProps<{
    settings: GeneralSettings;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
            {
                title: 'Pengaturan Umum',
                href: '/dashboard/pengaturan/umum',
            },
        ],
    },
});

const logoPreview = ref<string | null>(null);

const form = useForm({
    school_name: props.settings.school_name ?? '',
    school_logo: null as File | null,
    school_address: props.settings.school_address ?? '',
});

const displayedLogo = computed(() => logoPreview.value ?? props.settings.school_logo);

const setLogoFile = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    form.school_logo = file;

    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
        logoPreview.value = null;
    }

    if (file) {
        logoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post('/dashboard/pengaturan/umum', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.school_logo = null;
        },
    });
};
</script>

<template>
    <Head title="Pengaturan Umum" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-white p-4 font-['Forma_DJR_Micro',Arial,sans-serif] text-[#1a1a1a] md:p-6"
    >
        <section class="px-1 py-2 md:px-2">
            <h1 class="text-2xl leading-[1.17] font-medium text-[#1a1a1a]">
                Pengaturan Umum
            </h1>
            <p class="mt-3 max-w-2xl text-base leading-[1.5] text-[#636363]">
                Kelola identitas sekolah yang tampil di aplikasi.
            </p>
        </section>

        <form
            class="grid gap-6 rounded-2xl border border-[#e8e8e8] bg-white p-4 shadow-[0_2px_8px_rgba(26,26,26,0.08)] md:p-6 xl:grid-cols-[minmax(0,1fr)_320px]"
            @submit.prevent="submit"
        >
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="school-name">Nama sekolah</Label>
                    <Input
                        id="school-name"
                        v-model="form.school_name"
                        class="h-11 rounded-[4px] border-[#c2c2c2] bg-white text-base shadow-none focus-visible:border-[#1a1a1a] focus-visible:ring-0 md:text-sm"
                        type="text"
                        required
                        autocomplete="organization"
                        placeholder="Nama sekolah"
                    />
                    <InputError :message="form.errors.school_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="school-address">Alamat sekolah</Label>
                    <textarea
                        id="school-address"
                        v-model="form.school_address"
                        class="min-h-32 w-full resize-y rounded-[4px] border border-[#c2c2c2] bg-white px-3 py-2 text-base leading-[1.5] text-[#1a1a1a] shadow-none outline-none placeholder:text-[#636363] focus:border-[#1a1a1a] focus:ring-0 md:text-sm"
                        maxlength="1000"
                        placeholder="Alamat lengkap sekolah"
                    />
                    <InputError :message="form.errors.school_address" />
                </div>

                <div class="grid gap-2">
                    <Label for="school-logo">Logo sekolah</Label>
                    <Input
                        id="school-logo"
                        class="h-11 rounded-[4px] border-[#c2c2c2] bg-white shadow-none file:text-[#1a1a1a] focus-visible:border-[#1a1a1a] focus-visible:ring-0"
                        type="file"
                        accept="image/*"
                        @change="setLogoFile"
                    />
                    <p class="text-xs leading-[1.33] text-[#636363]">
                        Upload gambar maksimal 1MB.
                    </p>
                    <InputError :message="form.errors.school_logo" />
                </div>
            </div>

            <aside class="grid content-start gap-4 rounded-xl bg-[#f7f7f7] p-4">
                <div
                    class="flex aspect-square items-center justify-center overflow-hidden rounded-lg border border-[#e8e8e8] bg-white"
                >
                    <img
                        v-if="displayedLogo"
                        :src="displayedLogo"
                        alt="Logo sekolah"
                        class="h-full w-full object-contain p-6"
                    />
                    <div
                        v-else
                        class="grid justify-items-center gap-3 px-6 text-center text-[#636363]"
                    >
                        <ImageUp class="size-8" />
                        <span class="text-sm leading-[1.5]">
                            Logo sekolah belum diunggah
                        </span>
                    </div>
                </div>

                <div class="grid gap-1">
                    <p class="text-sm font-medium text-[#1a1a1a]">
                        {{ form.school_name || 'Nama sekolah' }}
                    </p>
                    <p class="text-sm leading-[1.5] text-[#636363]">
                        {{ form.school_address || 'Alamat sekolah' }}
                    </p>
                </div>
            </aside>

            <div
                class="flex justify-end border-t border-[#e8e8e8] pt-5 xl:col-span-2"
            >
                <Button
                    type="submit"
                    class="h-11 rounded-[4px] bg-[#024ad8] px-6 text-sm font-semibold tracking-[0.7px] text-white uppercase shadow-none hover:bg-[#0e3191]"
                    :disabled="form.processing"
                >
                    <Save />
                    Simpan
                </Button>
            </div>
        </form>
    </div>
</template>
